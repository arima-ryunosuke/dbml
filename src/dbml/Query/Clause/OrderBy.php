<?php

namespace ryunosuke\dbml\Query\Clause;

use Doctrine\DBAL\Types\PhpIntegerMappingType;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\SelectBuilder;
use function ryunosuke\dbml\array_maps;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\random_range;
use function ryunosuke\dbml\str_exists;

/**
 * ORDER BY 句クラス
 *
 * このクラスのインスタンスを orderBy すると、特殊な ORDER BY として扱われる。
 *
 * ランダム系は原則として「limit 件数」を返すことを期待してはならない。
 * 多く返すことはないが、少なく返すことはある。
 *
 * ```php
 * $db->select('tablename.columname')->orderBy(OrderBy::randomOrder());
 * // SELECT columnname FROM tablename ORDER BY RAND()
 * ```
 */
class OrderBy extends AbstractClause
{
    public const CTE_TABLE       = '__dbml_cte_table';
    public const CTE_TABLE_ALIAS = '__dbml_cte_table_alias';
    public const CTE_AUTO_PKEY   = Database::AUTO_DEPEND_KEY . '_cte';

    public function __construct(private bool $rewritable, public ?bool $asc = null, public ?string $nullsOrder = null)
    {
    }

    public function __invoke(SelectBuilder $builder) { }

    public function isRewritable(): bool
    {
        return $this->rewritable;
    }

    /**
     * 主キーで ORDER BY する
     */
    public static function primary(?bool $asc = null): static
    {
        return new class(false, $asc) extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $sqlParts = $builder->getQueryPart(null);

                if (!$frompart = $sqlParts['from']) {
                    return [];
                }

                $fromtable = reset($frompart);
                if (!$builder->getDatabase()->getSchema()->hasTable($fromtable['table'])) {
                    return [];
                }

                $primary = $builder->getDatabase()->getSchema()->getTablePrimaryKey($fromtable['table']);
                if ($primary === null) {
                    return [];
                }

                $tablename = $fromtable['alias'] ?: $fromtable['table'];
                return array_map(fn($c) => "$tablename.$c", $primary->getColumns());
            }
        };
    }

    /**
     * 実在するカラムやエイリアスをチェックするセキュアな ORDER BY
     *
     * - from,join 句にあるテーブルカラムの実名
     * - select 句にあるエイリアス名
     * - Expression インスタンス
     *
     * 以外は何もせずスルーされる。
     * その性質上、事前に実行しておくことは出来ない。
     *
     * ```php
     * // test に id カラムは存在するので order by id される
     * $db->select('test')->orderBySecure('id');
     *
     * // 修飾しても OK
     * $db->select('test')->orderBySecure('test.id');
     *
     * // Expression インスタンスは無条件で OK
     * $db->select('test.id AS hoge')->orderBySecure(Expression::new('NOW()'));
     *
     * // test に hoge カラムは存在しないが id のエイリアスなので OK
     * $db->select('test.id AS hoge')->orderBySecure('hoge');
     *
     * // test に fuga カラムは存在せず、エイリアスもないため、このメソッドは何も行わない
     * $db->select('test.id as hoge')->orderBySecure('fuga');
     * ```
     *
     * エラーや例外が出ないので挙動が分かりにくいが、下手にエラーを出すと「攻撃が可能そう」に見えてしまうのでこのような動作にしている。
     */
    public static function secure($columns, ?bool $asc = null, ?string $nullsOrder = null): static
    {
        return new class(arrayize($columns), $asc, $nullsOrder) extends OrderBy {
            public function __construct(private array $columns, ?bool $asc = null, ?string $nullsOrder = null)
            {
                parent::__construct(false, $asc, $nullsOrder);
            }

            public function __invoke(SelectBuilder $builder)
            {
                $secure_columns = [];
                foreach ($this->columns as $k => $column) {
                    if (is_int($k)) {
                        $order = null;
                    }
                    else {
                        $order = $column;
                        $column = $k;
                    }
                    if ($this->isSecureColumn($builder, $column)) {
                        $secure_columns[] = [$column, $order];
                    }
                }
                return $secure_columns;
            }

            private static function isSecureColumn(SelectBuilder $builder, $column, $alias = null)
            {
                $sqlParts = $builder->getQueryPart(null);
                $database = $builder->getDatabase();
                $schema = $database->getSchema();

                // Expression は信頼できる
                if ($column instanceof Expression) {
                    return true;
                }

                // 文字列は select,from,join 句を調べて一致するもののみ
                if (is_string($column)) {
                    // テーブル記法は再帰
                    if (str_exists($column, ['+', '-'])) {
                        foreach (preg_split('#([+-])#', $column, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                            if (!self::isSecureColumn($builder, $part)) {
                                return false;
                            }
                        }
                        return true;
                    }

                    // SELECT 句と比較
                    foreach ($sqlParts['select'] as $select) {
                        // エイリアス指定ならそれを使う
                        if ($select instanceof Select) {
                            $select = $select->getAlias();
                        }

                        // 完全に一致するならそれはセキュア
                        if ($column === $select) {
                            return true;
                        }
                    }

                    // 修飾をバラす
                    [$modifier, $col] = array_pad(explode('.', $column, 2), -2, null);

                    // FROM 句と比較
                    foreach ($builder->getFromPart() as $table) {
                        // SelectBuilder なら更に再帰
                        if ($table['table'] instanceof SelectBuilder) {
                            if (self::isSecureColumn($table['table'], $column, $table['alias'])) {
                                return true;
                            }
                            continue;
                        }
                        // テーブルが存在しない場合は仮想テーブルだったり cte だったりする
                        if (!$schema->hasTable($table['table'])) {
                            if ($vtable = $database->getVirtualTable($table['table'])) {
                                // と思ったが build 時点で組み込まれているのでここここにくることはない（備忘のためにコードは残す）
                                assert($vtable); // @codeCoverageIgnore
                            }
                            if ($cte = $sqlParts['with'][$table['table']] ?? null) {
                                if ($cte instanceof SelectBuilder && self::isSecureColumn($cte, $column, $table['alias'])) {
                                    return true;
                                }
                            }
                            continue;
                        }
                        // 修飾されていたならテーブル名と比較しないと '1; DELETE FROM tablename -- .id' で攻撃が可能
                        if (isset($modifier) && ($modifier !== $table['table'] && $modifier !== $table['alias'] && $modifier !== $alias)) {
                            continue;
                        }
                        // テーブルにカラムが存在しないなら次へ
                        if (!array_key_exists($col, $schema->getTableColumns($table['table']))) {
                            continue;
                        }

                        return true;
                    }
                }

                // 上記で引っかからなかったら false
                return false;
            }
        };
    }

    /**
     * 指定配列の順番で ORDER BY する
     */
    public static function array(array $array, ?bool $asc = null, ?string $nullsOrder = null): static
    {
        return new class($array, $asc, $nullsOrder) extends OrderBy {
            public function __construct(private $array, ?bool $asc = null, ?string $nullsOrder = null)
            {
                parent::__construct(false, $asc, $nullsOrder);
            }

            public function __invoke(SelectBuilder $builder)
            {
                $orderBy = [];
                foreach ($this->array as $column => $orders) {
                    // 範囲外の値は null にして nullsOrder で制御させる
                    $orderBy[] = Expression::case($column, array_flip(array_values($orders)), null);
                }
                return $orderBy;
            }
        };
    }

    /**
     * シンプルに ORDER BY RANDOM() する
     *
     * - pros: 良い意味で速度のブレが少ない（状態や引数に依存して遅くなったりしない）
     * - cons: 悪い意味で速度のブレが少ない（状態や引数に依存して速くなったりしない）
     */
    public static function random(?int $seed = null): static
    {
        return new class($seed) extends OrderBy {
            public function __construct(private $seed)
            {
                parent::__construct(false);
            }

            public function __invoke(SelectBuilder $builder)
            {
                $builder->detectAutoOrder(false);
                return $builder->getDatabase()->getCompatiblePlatform()->getRandomExpression($this->seed);
            }
        };
    }

    /**
     * 状態や統計に基づいてランダム化する
     *
     * ただし現状は randomOrder, randomPKMinMax, randomPK のみ（他は癖が強すぎる）。
     */
    public static function randomSuitably(): static
    {
        return new class(true) extends OrderBy {
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
                    return $builder->orderBy(OrderBy::random()($builder));
                }
                // 数値系単一主キーなら minmax で引っ張れるが、where があると歯抜けが発生しまくるので除外
                if (!$where && count($pkcols) === 1 && $pkcols[$pkkey]->getType() instanceof PhpIntegerMappingType) {
                    return OrderBy::randomPKMinMax()($builder);
                }
                return OrderBy::randomPK()($builder);
            }
        };
    }

    /**
     * N/COUNT の確率で WHERE する
     *
     * - pros: そこそこ速い
     * - cons: 指定件数以下になりやすい・等確率でない・1クエリで完結しない・速度が安定しない（速いときは速いが遅いときは遅い）
     */
    public static function randomWhere(): static
    {
        return new class(true) extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $random = $builder->getDatabase()->getCompatiblePlatform()->getRandomExpression(null);
                $count = (int) $builder->countize()->value() ?: -1;
                $where = Expression::new("$random <= (? / ?)", [$builder->getQueryPart('limit') ?? PHP_INT_MAX, $count]);
                return $builder->andWhere($where)->orderBy(OrderBy::random());
            }
        };
    }

    /**
     * OFFSET をずらして UNION する
     *
     * - pros: それなりに速い
     * - cons: 要CTE・クエリが大幅に書き換えられる・1クエリで完結しない・速度が安定しない（速いときは速いが遅いときは遅い）
     */
    public static function randomOffset(): static
    {
        return new class(true) extends OrderBy {
            public function __invoke(SelectBuilder $builder)
            {
                $count = (int) $builder->countize()->value();
                $offsets = $count ? random_range(0, $count - 1, $builder->getQueryPart('limit') ?? PHP_INT_MAX) : [];
                $base = SelectBuilder::new($builder->getDatabase())->from(self::CTE_TABLE);
                $queries = array_maps($offsets, fn($offset) => (clone $base)->limit(1, $offset)) ?: $base;
                $that = (clone $builder)->resetQueryPart(['orderBy', 'offset', 'limit']);
                return $builder->getDatabase()->union($queries)->with(self::CTE_TABLE, $that)->orderBy(OrderBy::random());
            }
        };
    }

    /**
     * 主キーで IN（SQL 内部で pk を subquery）する
     *
     * - pros: それなりに速い
     * - cons: 要CTE・JOINに弱い
     */
    public static function randomPK(): static
    {
        return new class(true) extends OrderBy {
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
                $pkwhere = SelectBuilder::new($builder->getDatabase())->from(self::CTE_TABLE)->select(...$pkcolumns)->orderBy(OrderBy::random());
                if ($limit) {
                    $pkwhere->limit($limit)->wrap('SELECT * FROM', self::CTE_TABLE_ALIAS); // for mysql (This version of MySQL doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery)
                }
                $that = (clone $builder)->resetQueryPart(['orderBy', 'offset', 'limit'])->addSelect(...$pkaliases);
                return $builder->resetQueryPart()
                    ->with(self::CTE_TABLE, $that)
                    ->from(self::CTE_TABLE)
                    ->select('*')
                    ->where([$pkkeys => $pkwhere])
                    ->orderBy(OrderBy::random());
            }
        };
    }

    /**
     * 主キーで IN（php で minmax pk を生成して IN）する
     *
     * - pros: かなり速い
     * - cons: 主キーが数値前提・JOINに弱い・歯抜けが発生する・1クエリで完結しない
     */
    public static function randomPKMinMax(): static
    {
        return new class(true) extends OrderBy {
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
                return $builder->andWhere(["$alias.$pkkey" => $pkvals])->orderBy(OrderBy::random());
            }
        };
    }

    /**
     * 主キーで IN（php で minmax pk を生成して UNION）する
     *
     * - pros: かなり速い・歯抜けが発生しない
     * - cons: 要CTE・主キーが数値前提・JOINに弱い・偏りが激しい・クエリが大幅に書き換えられる・1クエリで完結しない
     */
    public static function randomPKMinMax2(): static
    {
        return new class(true) extends OrderBy {
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
                $base = SelectBuilder::new($builder->getDatabase())->from(self::CTE_TABLE)->limit(1);
                $queries = array_maps($pkvals, fn($pkval) => (clone $base)->where(["$pkkey >= ?" => $pkval]));
                return $builder->getDatabase()->union($queries)->with(self::CTE_TABLE, $that)->orderBy(OrderBy::random());
            }
        };
    }
}
