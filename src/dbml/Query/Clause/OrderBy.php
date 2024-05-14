<?php

namespace ryunosuke\dbml\Query\Clause;

use Doctrine\DBAL\Types\PhpIntegerMappingType;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\SelectBuilder;
use function ryunosuke\dbml\array_maps;
use function ryunosuke\dbml\random_range;

/**
 * ORDER BY 句クラス
 *
 * このクラスのインスタンスを orderBy すると、特殊な ORDER BY として扱われる。
 *
 * 現在の用途としては「ランダム取得」である。
 * 原則として「limit 件数」を返すことを期待してはならない。
 * 多く返すことはないが、少なく返すことはある。
 *
 * 将来的には現在 SelectBuilder に生えている byPrimary や bySecure などをここに移して管理する。
 *
 * ```php
 * $db->select('tablename.columname')->orderBy(OrderBy::randomOrder());
 * // SELECT columnname FROM tablename ORDER BY RAND()
 * ```
 */
class OrderBy
{
    public const CTE_TABLE       = '__dbml_cte_table';
    public const CTE_TABLE_ALIAS = '__dbml_cte_table_alias';
    public const CTE_AUTO_PKEY   = Database::AUTO_DEPEND_KEY . '_cte';

    public function __invoke(SelectBuilder $builder) { }

    /**
     * 状態や統計に基づいてランダム化する
     *
     * ただし現状は randomOrder, randomPKMinMax, randomPK のみ（他は癖が強すぎる）。
     *
     * @return static
     */
    public static function random()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $froms = $builder->getFromPart();
                $alias = array_key_first($froms);
                $table = $froms[$alias]['table'];
                $where = $builder->getQueryPart('where');
                $limit = $builder->getQueryPart('limit');
                $pkcols = $builder->getDatabase()->getSchema()->getTablePrimaryColumns($table);
                $pkkey = array_key_first($pkcols);

                // limit がないならどうせ全件走査, join があると PK系は使えない（駆動表の主キーで取得するので偏りが生まれる）, 複合主キーなら行値式が必要
                if ($limit === null || count($froms) > 1 || (count($pkcols) > 1 && !$builder->getDatabase()->getCompatiblePlatform()->supportsRowConstructor())) {
                    return self::randomOrder()($builder);
                }
                // 数値系単一主キーなら minmax で引っ張れるが、where があると歯抜けが発生しまくるので除外
                if (!$where && count($pkcols) === 1 && $pkcols[$pkkey]->getType() instanceof PhpIntegerMappingType) {
                    return self::randomPKMinMax()($builder);
                }
                return self::randomPK()($builder);
            }
        };
    }

    /**
     * シンプルに ORDER BY RANDOM() する
     *
     * - pros: 良い意味で速度のブレが少ない（状態や引数に依存して遅くなったりしない）
     * - cons: 悪い意味で速度のブレが少ない（状態や引数に依存して速くなったりしない）
     *
     * @return static
     */
    public static function randomOrder()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                return $builder->orderByRandom();
            }
        };
    }

    /**
     * N/COUNT の確率で WHERE する
     *
     * - pros: そこそこ速い
     * - cons: 指定件数以下になりやすい・等確率でない・1クエリで完結しない・速度が安定しない（速いときは速いが遅いときは遅い）
     *
     * @return static
     */
    public static function randomWhere()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $random = $builder->getDatabase()->getCompatiblePlatform()->getRandomExpression(null);
                $count = (int) $builder->countize()->value() ?: -1;
                $where = new Expression("$random <= (? / ?)", [$builder->getQueryPart('limit') ?? PHP_INT_MAX, $count]);
                return $builder->andWhere($where)->orderByRandom();
            }
        };
    }

    /**
     * OFFSET をずらして UNION する
     *
     * - pros: それなりに速い
     * - cons: 要CTE・クエリが大幅に書き換えられる・1クエリで完結しない・速度が安定しない（速いときは速いが遅いときは遅い）
     *
     * @return static
     */
    public static function randomOffset()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $count = (int) $builder->countize()->value();
                $offsets = $count ? random_range(0, $count - 1, $builder->getQueryPart('limit') ?? PHP_INT_MAX) : [];
                $base = $builder->getDatabase()->createSelectBuilder()->from(self::CTE_TABLE);
                $queries = array_maps($offsets, fn($offset) => (clone $base)->limit(1, $offset)) ?: $base;
                $that = (clone $builder)->resetQueryPart(['orderBy', 'offset', 'limit']);
                return $builder->getDatabase()->union($queries)->with(self::CTE_TABLE, $that)->orderByRandom();
            }
        };
    }

    /**
     * 主キーで IN（SQL 内部で pk を subquery）する
     *
     * - pros: それなりに速い
     * - cons: 要CTE・JOINに弱い
     *
     * @return static
     */
    public static function randomPK()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $froms = $builder->getFromPart();
                $alias = array_key_first($froms);
                $table = $froms[$alias]['table'];
                $limit = $builder->getQueryPart('limit');
                $pkcols = $builder->getDatabase()->getSchema()->getTablePrimaryColumns($table);

                $pkcolumns = [];
                $pkaliases = [];
                foreach ($pkcols as $name => $column) {
                    $aliasname = self::CTE_AUTO_PKEY . "_{$alias}_{$name}";
                    $pkcolumns[] = $aliasname;
                    $pkaliases[] = new Select($aliasname, "$alias.$name", null, true);
                }
                $pkkeys = implode(',', $pkcolumns);
                $pkkeys = count($pkcols) > 1 ? "($pkkeys)" : $pkkeys;
                $pkwhere = $builder->getDatabase()->createSelectBuilder()->from(self::CTE_TABLE)->select(...$pkcolumns)->orderByRandom();
                if ($limit) {
                    $pkwhere->limit($limit)->wrap('SELECT * FROM', self::CTE_TABLE_ALIAS); // for mysql (This version of MySQL doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery)
                }
                $that = (clone $builder)->resetQueryPart(['orderBy', 'offset', 'limit'])->addSelect(...$pkaliases);
                return $builder->resetQueryPart()
                    ->with(self::CTE_TABLE, $that)
                    ->from(self::CTE_TABLE)
                    ->select('*')
                    ->where([$pkkeys => $pkwhere])
                    ->orderByRandom();
            }
        };
    }

    /**
     * 主キーで IN（php で minmax pk を生成して IN）する
     *
     * - pros: かなり速い
     * - cons: 主キーが数値前提・JOINに弱い・歯抜けが発生する・1クエリで完結しない
     *
     * @return static
     */
    public static function randomPKMinMax()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $froms = $builder->getFromPart();
                $alias = array_key_first($froms);
                $table = $froms[$alias]['table'];
                $limit = $builder->getQueryPart('limit');
                $pkcols = $builder->getDatabase()->getSchema()->getTablePrimaryColumns($table);
                $pkkey = array_key_first($pkcols);

                $that = (clone $builder)->cast('array')->resetQueryPart(['select', 'orderBy', 'offset', 'limit'])->select("$alias.$pkkey");
                [$min, $max] = array_values($that->aggregate(['MIN', 'MAX'])->tuple());
                $pkvals = random_range($min ?? 0, $max ?? 0, $limit === null ? PHP_INT_MAX : $limit * 2); // 歯抜けを考慮して2倍程度取る
                return $builder->andWhere(["$alias.$pkkey" => $pkvals])->orderByRandom();
            }
        };
    }

    /**
     * 主キーで IN（php で minmax pk を生成して UNION）する
     *
     * - pros: かなり速い・歯抜けが発生しない
     * - cons: 要CTE・主キーが数値前提・JOINに弱い・偏りが激しい・クエリが大幅に書き換えられる・1クエリで完結しない
     *
     * @return static
     */
    public static function randomPKMinMax2()
    {
        return new class extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $froms = $builder->getFromPart();
                $alias = array_key_first($froms);
                $table = $froms[$alias]['table'];
                $limit = $builder->getQueryPart('limit');
                $pkcols = $builder->getDatabase()->getSchema()->getTablePrimaryColumns($table);
                $pkkey = array_key_first($pkcols);

                $that = (clone $builder)->resetQueryPart(['orderBy', 'offset', 'limit']);
                [$min, $max] = array_values((clone $that)->cast('array')->resetQueryPart('select')->select("$alias.$pkkey")->aggregate(['MIN', 'MAX'])->tuple());
                $pkvals = random_range($min ?? 0, $max ?? 0, $limit ?? PHP_INT_MAX);
                $base = $builder->getDatabase()->createSelectBuilder()->from(self::CTE_TABLE)->limit(1);
                $queries = array_maps($pkvals, fn($pkval) => (clone $base)->where(["$pkkey >= ?" => $pkval]));
                return $builder->getDatabase()->union($queries)->with(self::CTE_TABLE, $that)->orderByRandom();
            }
        };
    }
}
