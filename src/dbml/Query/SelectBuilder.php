<?php

namespace ryunosuke\dbml\Query;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Entity\Entity;
use ryunosuke\dbml\Entity\Entityable;
use ryunosuke\dbml\Exception\NonSelectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\dbml\Mixin\FactoryTrait;
use ryunosuke\dbml\Mixin\FetchMethodTrait;
use ryunosuke\dbml\Mixin\FetchOrThrowTrait;
use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Mixin\JoinTrait;
use ryunosuke\dbml\Query\Clause\AbstractClause;
use ryunosuke\dbml\Query\Clause\Having;
use ryunosuke\dbml\Query\Clause\OrderBy;
use ryunosuke\dbml\Query\Clause\Select;
use ryunosuke\dbml\Query\Clause\SelectOption;
use ryunosuke\dbml\Query\Clause\Where;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\Pagination\Paginator;
use ryunosuke\dbml\Query\Pagination\Sequencer;
use ryunosuke\dbml\Utility\Adhoc;
use function ryunosuke\dbml\array_and;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_find_first;
use function ryunosuke\dbml\array_flatten;
use function ryunosuke\dbml\array_implode;
use function ryunosuke\dbml\array_lookup;
use function ryunosuke\dbml\array_maps;
use function ryunosuke\dbml\array_set;
use function ryunosuke\dbml\array_sprintf;
use function ryunosuke\dbml\array_strpad;
use function ryunosuke\dbml\array_unset;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\first_key;
use function ryunosuke\dbml\first_keyvalue;
use function ryunosuke\dbml\first_value;
use function ryunosuke\dbml\instance_of;
use function ryunosuke\dbml\is_bindable_closure;
use function ryunosuke\dbml\is_hasharray;
use function ryunosuke\dbml\is_primitive;
use function ryunosuke\dbml\split_noempty;
use function ryunosuke\dbml\str_exists;

// @formatter:off
/**
 * SELECT ビルダークラス
 *
 * ### IteratorAggregate, Countable
 *
 * IteratorAggregate, Countable を実装しているので、 foreach で回すことができるし、count($qb) で件数の取得もできる。
 * foreach の取得処理は array() であり、レコードの配列（≒連想配列）が渡ってくる。 count() はその件数である。
 *
 * ```php
 * $qb = $db->select('t_article');
 * foreach ($qb as $row) {
 *     // $row は ['id' => 1, 'title' => 'hoge title'] のような配列
 * }
 * echo count($qb); // t_article の件数を返す
 * ```
 *
 * ### プリペアードステートメント
 *
 * 明示的に prepare のようなメソッドを呼ばない限り内部のプリペアードステートメントでは名前付きパラメータを一切使用しない（{@link Statement} も参照）。
 * prepare を呼ぶと現時点のパラメータで固定することができ、その上で `:name` のような名前付きパラメータに値を渡すことができる。
 *
 * @method string                 getDefaultLazyMode()
 * @method $this                  setDefaultLazyMode($string)
 * @method array                  getDefaultScope()
 * @method $this                  setDefaultScope($array)
 * @method mixed                  getDefaultOrder()
 * @method $this                  setDefaultOrder($mixed)
 * @method string                 getPrimarySeparator()
 * @method $this                  setPrimarySeparator($string)
 * @method string                 getAggregationDelimiter()
 * @method $this                  setAggregationDelimiter($string)
 * @method string                 getArrayFetch()
 * @method $this                  setArrayFetch($string)
 * @method string                 getNullsOrder()
 * @method $this                  setNullsOrder($string = null)
 * @method int|LockMode           getPropagateLockMode()
 * @method $this                  setPropagateLockMode($lockMode)
 * @method bool                   getInjectChildColumn()
 * @method $this                  setInjectChildColumn($bool)
 *
 * これは phpstorm の as keyword が修正されたら不要になる
 * @method $this|array|Entityable[] array(iterable $params = [])
 */
// @formatter:on
class SelectBuilder extends AbstractBuilder implements \IteratorAggregate, \Countable
{
    use FactoryTrait;
    use IteratorTrait;
    use JoinTrait;

    use FetchMethodTrait {
        fetchArrayWithoutSql as public array; // phpstorm がエラーを吐くので別途定義
        fetchAssocWithoutSql as public assoc;
        fetchListsWithoutSql as public lists;
        fetchPairsWithoutSql as public pairs;
        fetchTupleWithoutSql as public tuple;
        fetchValueWithoutSql as public value;
    }

    use FetchOrThrowTrait {
        fetchArrayOrThrowWithoutSql as public arrayOrThrow;
        fetchAssocOrThrowWithoutSql as public assocOrThrow;
        fetchListsOrThrowWithoutSql as public listsOrThrow;
        fetchPairsOrThrowWithoutSql as public pairsOrThrow;
        fetchTupleOrThrowWithoutSql as public tupleOrThrow;
        fetchValueOrThrowWithoutSql as public valueOrThrow;
    }

    // 構成要素のキー
    public const CLAUSES = [
        'column',
        'where',
        'orderBy',
        'limit',
        'groupBy',
        'having',
    ];

    // 遅延モード
    public const LAZY_MODE_EAGER = 'eager';
    public const LAZY_MODE_BATCH = 'batch';
    public const LAZY_MODE_FETCH = 'fetch';
    public const LAZY_MODE_YIELD = 'yield';

    // 遅延モードの設定値
    public const LAZY_MODES = [
        self::LAZY_MODE_EAGER => ['prepared' => false, 'generated' => false],
        self::LAZY_MODE_BATCH => ['prepared' => false, 'generated' => true],
        self::LAZY_MODE_FETCH => ['prepared' => true, 'generated' => false],
        self::LAZY_MODE_YIELD => ['prepared' => true, 'generated' => true],
    ];

    private const COUNT_ALIAS = '__dbml_auto_cnt';

    protected array $sqlParts = [
        'comment'  => [],
        'with'     => [],
        'option'   => [],
        'select'   => [],
        'union'    => [],
        'from'     => [],
        'join'     => [],
        'hint'     => [],
        'where'    => [],
        'groupBy'  => [],
        'having'   => [],
        'window'   => [],
        'orderBy'  => [],
        'offset'   => null,
        'limit'    => null,
        'operator' => null,
    ];

    protected array $cache = [];

    protected array $callbacks = [];

    protected array $applyments = [
        'before' => null,
        'after'  => null,
    ];

    protected array $joinOrders = [];

    protected array $onConditions = [];

    protected array $wrappers = [];

    protected int|LockMode $lockMode   = LockMode::NONE;
    protected string       $lockOption = '';

    /** @var SelectBuilder[] */
    protected array            $subbuilders = [];
    protected null|bool|string $submethod   = null;
    protected ?string          $subwhere    = null;

    protected ?string $lazyMode      = null;
    protected ?string $lazyMethod    = null;
    protected ?string $lazyParent    = null;
    protected array   $lazyColumns   = [];
    protected array   $lazyCondition = [];
    protected ?int    $lazyChunk     = null;

    /** @var string|callable fetch 時のタイプ */
    protected $caster;

    protected ?bool $emptyCondition = null;

    protected ?bool $enableAutoOrder = null;

    public static function getDefaultOptions(): array
    {
        return [
            /** @var string 配列や Gateway 指定時のデフォルト sub lazy mode */
            'defaultLazyMode'      => self::LAZY_MODE_EAGER,
            /** @var array TableGateway の暗黙的スコープ名 */
            'defaultScope'         => [],
            /** @var string|bool orderBy が無かったとき、あったとしても必ず最後に付与されるデフォルトの並び順
             * true を指定すると主キーの昇順、 false を指定すると主キーの降順が付与される。
             * あるいは任意の文字列や表現を渡すとそれが追加される。
             */
            'defaultOrder'         => true,
            /** @var string 複合主キーを単一主キーとみなすための結合文字列
             * 1. 共に自動採番主キーである親テーブルと子テーブル
             * 2. 親テーブルへの外部キーが複合主キーの一部である子テーブル
             *
             * 1 の場合は共に自動採番なので子テーブルのキーは主キーになる。区切り文字は使われない。
             * 2 の場合は複合主キーであり、単一カラムで表すことはできないが、「自動で子供テーブルを引っ張る」ような処理は外部キーを元に引っ張るので**その子供の世界では複合主キーの片方だけで一意に**なる。
             * つまり結果として区切り文字は使われない。
             *
             * それでも冗長にキーを持っていたりするとどうしても自動では一意性が検出できない場合がある。
             * そのような場合に結合する文字列を指定する。
             */
            'primarySeparator'     => "\x1F",
            /** @var string aggregate 時（columnname@sum）の区切り文字
             * 集約関数は大抵の場合は単一クエリで実行するので大部分で指定不要だが、時折指定が必要になることがある。
             *
             * ```php
             * # こういった個別メソッドの場合は指定する意味はない（区切りも何もスカラー値で取れるんだから）
             * $db->min('table');
             * // results: 1
             *
             * # このように aggregate メソッドを使用したときに活きる
             * $db->aggregate(['min', 'max'], 'table.id', [], 'table.group_id');
             * // results:
             * [
             *     1 => [
             *         'table.id@min' => 1,
             *         'table.id@max' => 2,
             *     ],
             * ]
             * ```
             */
            'aggregationDelimiter' => '@',
            /** @var string 配列を指定した場合のフェッチモード（通常は array か assoc）
             *
             * ```php
             * # このようなサブテーブルの配列指定で・・・
             * $db->selectArray([
             *     't_article' => [
             *         't_comment' => ['*'],
             *     ],
             * ]);
             *
             * # DATABASE::METHOD_ARRAY を指定した場合
             * [
             *     // サブテーブル取得は連番配列になる（0ベース配列）
             *     't_comment' => [
             *         [コメントレコード],
             *         [コメントレコード],
             *         [コメントレコード],
             *     ],
             * ]
             *
             * # DATABASE::METHOD_ASSOC を指定した場合
             * [
             *     // サブテーブル取得は連想配列になる（サブテーブルの主キー）
             *     't_comment' => [
             *         '1' => [コメントレコード],
             *         '2' => [コメントレコード],
             *         '3' => [コメントレコード],
             *     ],
             * ]
             *
             * # null を指定した場合
             * [
             *     // サブテーブル取得はメソッドの selectXXXXX に引きずられる（この場合 selectArray なので METHOD_ARRAY と同等）
             *     't_comment' => [
             *         [コメントレコード],
             *         [コメントレコード],
             *         [コメントレコード],
             *     ],
             * ]
             * ```
             */
            'arrayFetch'           => Database::METHOD_ASSOC,
            /** @var ?string ORDER BY で NULL をどう扱うか
             * - null: 何もしない
             * - "min": 最小値として扱う
             * - "max": 最大値として扱う
             * - "first": 常に最初に来る
             * - "last": 常に最後に来る
             */
            'nullsOrder'           => null,
            /** @var bool 遅延実行時に親のロックモードを受け継ぐか否か */
            'propagateLockMode'    => true,
            /** @var bool サブクエリをコメント化して親のクエリに埋め込むか否か */
            'injectChildColumn'    => false,
        ];
    }

    /**
     * コンストラクタ
     */
    public function __construct(Database $database)
    {
        parent::__construct($database);

        // $this だと gc されずに参照が残り続けるので無名クラスのコンテキストにする（どうせ実行時に再バインドされる）
        $this->setProvider(\Closure::bind(function () {
            /** @var SelectBuilder $this */
            return $this->array();
        }, new class ( ) { }));
    }

    /**
     * @ignore
     */
    public function __clone()
    {
        $clone = static function ($somthing) use (&$clone) {
            if (is_object($somthing)) {
                $somthing = clone $somthing;
            }
            elseif (is_array($somthing)) {
                foreach ($somthing as $key => $value) {
                    $somthing[$key] = $clone($value);
                }
            }
            return $somthing;
        };
        $this->sqlParts = $clone($this->sqlParts);
        $this->subbuilders = $clone($this->subbuilders);
    }

    /**
     * 句を追加する
     *
     * 例えば OrderBy の場合 `$builder->orderBy(OrderBy::primary());` となり表現が冗長となる。
     * この invoke を使えば `$builder(OrderBy::primary());` となり表現が平易となる。
     */
    public function __invoke(AbstractClause ...$clauses): static
    {
        foreach ($clauses as $clause) {
            match (true) {
                $clause instanceof SelectOption => $this->addSelectOption($clause),
                $clause instanceof Select       => $this->addSelect($clause),
                $clause instanceof Where        => $this->andWhere($clause),
                $clause instanceof Having       => $this->andHaving($clause),
                $clause instanceof OrderBy      => $this->addOrderBy($clause),
            };
        }

        return $this;
    }

    /**
     * クエリ文字列を返す
     */
    public function __toString(): string
    {
        if (isset($this->sql)) {
            return $this->sql;
        }

        $platform = $this->database->getPlatform();
        $cplatform = $this->database->getCompatiblePlatform();

        $commentize = static function ($comments, $nest) use (&$commentize, $cplatform) {
            $spacer = str_repeat(' ', $nest * 3);
            $result = '';
            foreach ($comments as $key => $comment) {
                if (is_array($comment)) {
                    if (!is_int($key)) {
                        $result .= $spacer . $cplatform->commentize($key);
                    }
                    $result .= $commentize($comment, $nest + 1);
                }
                else {
                    $result .= $spacer . $cplatform->commentize($comment);
                }
            }
            return $result;
        };

        $comments = $commentize($this->sqlParts['comment'], 0);

        // 無理に変更してるので clone する
        $builder = clone $this;

        // UNION
        if ($builder->sqlParts['union']) {
            // 0 には UNIONN ALL/UNION の記号（最初の要素以外）、1 にはクエリ文字列が入っている
            $sql = array_sprintf($builder->sqlParts['union'], function ($union) use ($cplatform) {
                return concat($union[0], ' ') . ($cplatform->supportsUnionParentheses() ? "({$union[1]})" : "{$union[1]}");
            }, ' ');

            // select,join,where,group,having,order,limit(要するに from 以外) が設定されている場合はラップしたサブクエリに掛かる
            $parts = $builder->sqlParts;
            if ($parts['select'] || $parts['join'] || $parts['where'] || $parts['groupBy'] || $parts['having'] || $parts['orderBy'] || $parts['offset'] !== null || $parts['limit'] !== null) {
                // from が変わるので join も変えなければならない
                $builder->sqlParts['select'] = $parts['select'] ?: ['*'];
                $builder->sqlParts['from'][] = ['table' => "($sql)", 'alias' => '__dbml_union_table', 'fkeyname' => null, 'condition' => null];
                $builder->sqlParts['join'] = ['__dbml_union_table' => array_merge([], ...array_values($parts['join']))];
                $sql = $builder->resetQueryPart('union');
            }
            return $this->sql = $comments . $sql;
        }

        // Random ORDER（OrderBy はかなりアドホックに書き換えることがあるので別軸で行わなければならない）
        foreach ($builder->sqlParts['orderBy'] as $n => $orderBy) {
            if ($orderBy instanceof OrderBy && $orderBy->isRewritable()) {
                unset($builder->sqlParts['orderBy'][$n]);
                $sql = $orderBy($builder);
                $this->sqlParts = $sql->sqlParts;
                return $this->sql = $comments . $sql;
            }
        }

        // SELECT 句に手を加える
        foreach ($builder->sqlParts['select'] as $n => $select) {
            if ($select instanceof Select) {
                $alias = $select->getAlias();
                $actual = $select->getActual();

                $qalias = $cplatform->quoteIdentifierIfNeeded($alias ?? '');
                if ($qalias !== $alias) {
                    $builder->sqlParts['select'][$n] = new Select($qalias, $actual, null, $select->isPlaceholdable());
                }
            }
        }
        // 全て自動系カラムだと実質空（後で伏せられるため）なので * を追加する
        if (array_and($builder->sqlParts['select'], function ($select) {
            return $select instanceof Select && $select->isPlaceholdable();
        }, false)) {
            foreach ($builder->getFromPart() as $from) {
                $builder->sqlParts['select'][] = $from['alias'] . '.*';
            }
        }
        // 子セレクトを埋め込む
        if ($builder->subbuilders && $builder->getInjectChildColumn() && count($builder->sqlParts['select']) > 0) {
            $toParentColumns = array_sprintf($builder->subbuilders, function (SelectBuilder $subbuilder, $key) {
                $that = clone $subbuilder;

                // 自身の subbuilder は再帰しない(そいつの実行時にどうせ実行される)
                $selects = $that->getQueryPart('select');
                $that->resetQueryPart('select');
                $selects = array_filter($selects, function ($select) {
                    return !($select instanceof Select && $select->isPlaceholdable());
                });
                $that->select(...$selects);

                // 結合カラムを WHERE に加えてわかりやすくする
                $that->andWhere(array_sprintf($that->lazyColumns, '%2$s IN ([parent.%1$s])'));

                // カラムコメント化
                $cplatform = $this->getDatabase()->getCompatiblePlatform();
                $x = $that->queryInto();
                return $cplatform->commentize("($x) AS $key");
            }, '');
            $builder->sqlParts['select'][0] = $toParentColumns . ' ' . $builder->sqlParts['select'][0];
        }

        // FROM,JOIN 句に手を加える
        array_maps(array_filter(array_column($builder->getFromPart(), 'table'), fn($v) => is_a($v, self::class)), ['wrap' => ['', '']]);

        // ORDER BY 句に手を加える
        foreach ($builder->joinOrders as $jorder) {
            $builder->addOrderBy($jorder);
        }
        if ($builder->enableAutoOrder) {
            // 集約なら除外
            if (!$builder->sqlParts['groupBy']) {
                // EXISTS 述語も除外
                if (!isset($builder->wrappers['EXISTS']) && !isset($builder->wrappers['NOT EXISTS'])) {
                    // COUNT を含むなら除外
                    if (null === array_find_first($builder->sqlParts['select'], function ($s) { return strpos("$s", self::COUNT_ALIAS) !== false; })) {
                        if (($defaultOrder = $builder->getDefaultOrder()) !== null) {
                            $builder->addOrderBy($defaultOrder);
                        }
                    }
                }
            }
        }

        // 色々手を加えたやつでクエリ文字列化
        $sql = concat($cplatform->getWithRecursiveSyntax(), ' ', array_sprintf($builder->sqlParts['with'], '%2$s AS (%1$s)', ','), ' ')
            . 'SELECT'
            . concat(' ', implode(' ', $builder->sqlParts['option']))
            . concat(' ', implode(', ', $builder->sqlParts['select']) ?: '*')
            . concat(' FROM ', implode(', ', $builder->_getFromClauses()))
            . concat(' WHERE ', $this->_getConditionClause($builder->sqlParts['where']))
            . concat(' GROUP BY ', implode(', ', $builder->sqlParts['groupBy']))
            . concat(' HAVING ', $this->_getConditionClause($builder->sqlParts['having']))
            . concat(' WINDOW ', array_sprintf($builder->sqlParts['window'], '%2$s AS (%1$s)', ', '))
            . concat(' ORDER BY ', implode(', ', $builder->_getOrderByClause($builder->sqlParts['orderBy'])));

        $sql = $platform->modifyLimitQuery($sql, $builder->sqlParts['limit'], $builder->sqlParts['offset'] ?? 0);
        $sql = $cplatform->appendLockSuffix($sql, $builder->lockMode, $builder->lockOption);

        // 最後にラッピングして終わり
        foreach ($builder->wrappers as $wraper) {
            $sql = concat($wraper[0], ' ') . "($sql)" . concat(' ', $wraper[1]);
        }

        return $this->sql = $comments . $sql;
    }

    private function _getFromClauses(): array
    {
        $platform = $this->getDatabase()->getPlatform();
        $fromClauses = [];
        foreach ($this->sqlParts['from'] as $from) {
            $tname = $from['alias'] ?: $from['table'];
            $clause = $from['table'] . concat(' ', $from['alias']);
            $clause = $platform->appendLockHint($clause, $this->lockMode); // for SQLServer
            $sql = $clause
                . concat(' ', $this->sqlParts['hint'][$tname] ?? '')
                . $this->_getJoinClauses($tname);

            $fromClauses[$tname] = $sql;
        }
        return $fromClauses;
    }

    private function _getJoinClauses(string $fromAlias): string
    {
        $sql = $jsql = '';
        foreach ($this->sqlParts['join'][$fromAlias] ?? [] as $join) {
            $jname = $join['alias'] ?: $join['table'];
            $clause = $join['table'] . concat(' ', $join['alias']);
            $sql .= ' ' . strtoupper($join['type']) . ' JOIN ' . $clause
                . concat(' ', $this->sqlParts['hint'][$jname] ?? '')
                . ' ON ' . $join['condition'];

            $jsql .= $this->_getJoinClauses($jname);
        }
        return $sql . $jsql;
    }

    private function _getConditionClause(array $conditions): string
    {
        $result = "";
        foreach (Adhoc::wrapParentheses($conditions) as $n => $condition) {
            preg_match('#^(AND|OR)\d#i', $n, $matches);
            $andor = isset($matches[1]) ? " {$matches[1]} " : '';
            $result .= $andor . $condition;
        }
        return $result;
    }

    private function _getOrderByClause(array $orderBy): array
    {
        $result = [];
        foreach ($orderBy as [$column, $order]) {
            if ($order === null) {
                $result[] = $column;
                continue;
            }
            $result[] = "{$column} " . ($order ? 'ASC' : 'DESC');
        }
        return array_unique($result);
    }

    private function _buildColumn($columns, $table = null, ?string $alias = null): static
    {
        $result = [];

        $schema = $this->database->getSchema();
        $columns = arrayize($columns);
        $accessor = $alias ?: $table;
        $prefix = $accessor ? $accessor . '.' : '';

        // '*' や '!nocol' は差分をとったり仮想カラムを追加したりしなければならないので事前処理が必要
        if ($schema->hasTable($table ?? '')) {
            $ignores = [];
            foreach ($columns as $key => $column) {
                if (is_string($column) && $column[0] === '!') {
                    $ignores[] = ltrim($column, '!');
                    unset($columns[$key]);
                }
            }

            if ($ignores) {
                $this->database->debug("ignore column $table", $ignores);
                $allcolumns = array_filter($schema->getTableColumns($table), function (Column $column) {
                    return !($column->getPlatformOptions()['virtual'] ?? false) || $column->getPlatformOptions()['implicit'];
                });
                foreach ($ignores as $ignore) {
                    // 無視しようとしているカラムが存在しない場合（テーブル定義を変更した時とかの対策）
                    if (strlen($ignore) && !isset($allcolumns[$ignore])) {
                        throw new \UnexpectedValueException('some columns are not found (' . $ignore . ').');
                    }
                    unset($allcolumns[$ignore]);
                }
                $columns = array_merge(array_keys(array_diff_key($allcolumns, $columns)), $columns);
            }
        }

        $detectForeign = function (SelectBuilder $subbuiler, TableDescriptor $parsed, $table, $from, $name) use ($prefix) {
            if ($subbuiler->getPreparedStatement()) {
                throw new \UnexpectedValueException("subquery does not support prepared statement.");
            }

            // condition 指定時はテーブルは無関係（駆動表を使う）
            foreach ((array) $parsed->condition as $cond) {
                if ($cond instanceof \stdClass) {
                    $from = $from ?? first_value($subbuiler->getFromPart());
                    $fcols = (array) $cond;
                    break;
                }
            }

            // 上記で確定しなかったら相関のある外部キーを漁る
            if (!isset($fcols)) {
                $from = $from ?? array_find_first($subbuiler->getFromPart(), function ($from) use ($table) {
                    $fkey = $from['fkeyname'];
                    $fcols = $this->database->getSchema()->getForeignColumns($table, $from['table'], $fkey);
                    if ($fcols) {
                        return $from;
                    }
                }, false);
                $fkey = $from['fkeyname'] ?? $parsed->fkeyname;
                $fcols = $from
                    ? $this->database->getSchema()->getForeignColumns($table, $from['table'], $fkey)
                    : null;
            }

            if (!$fcols) {
                $from = $from ?: ['table' => 'NotSpecified', 'fkeyname' => 'NotSpecified'];
                throw new \UnexpectedValueException("has not foreign key between '{$table}' and '{$from['table']}' ({$from['fkeyname']}).");
            }

            $concatPrimary = function ($alias, $columns) {
                $psep = $this->database->quote($this->getPrimarySeparator());
                $cplatform = $this->database->getCompatiblePlatform();
                return new Select($alias, $cplatform->getConcatExpression(...array_values(array_implode($columns, $psep))), null, true);
            };

            $lazy_columns = array_strpad($fcols, $from['alias'] . '.', $prefix);

            $subbuiler->lazyMode = $subbuiler->lazyMode ?? $this->getDefaultLazyMode();
            $subbuiler->lazyParent = Database::AUTO_PRIMARY_KEY . strtolower($name);
            $subbuiler->lazyColumns = $lazy_columns;
            $subbuiler->lazyCondition = @instance_of($fkey ?? null, ForeignKeyConstraint::class)?->getOption('condition') ?? [];
            $subbuiler->sqlParts['select'][] = $concatPrimary(Database::AUTO_PARENT_KEY, array_keys($lazy_columns));

            // 「主キーから外部キーを差っ引いたものが空」はすなわち「親との結合」とみなすことができる
            // その場合 assoc は無駄だし、assoc のためのカラムを追加する必要もない
            if ($subbuiler->lazyMethod === null) {
                $pcols = $this->database->getSchema()->getTablePrimaryKey($from['table'])->getColumns();
                $jcols = array_strpad(array_diff($pcols, array_keys($fcols)), '', $from['alias'] . '.');
                if (!$jcols) {
                    $subbuiler->lazyMethod = Database::METHOD_TUPLE;
                }
                else {
                    $subbuiler->lazyMethod = $this->getUnsafeOption('arrayFetch');
                    array_unshift($subbuiler->sqlParts['select'], $concatPrimary(Database::AUTO_CHILD_KEY, $jcols));
                }
            }
            $this->subbuilders[$name] = $subbuiler;
            return [
                $concatPrimary($subbuiler->lazyParent, $lazy_columns),
                new Select($name, 'NULL', null, true),
            ];
        };

        foreach ($columns as $key => $column) {
            // 仮想テーブル
            if ($vtable = $this->database->getVirtualTable($key)) {
                $this->build($vtable, true);
                if ($column !== ['*']) {
                    $this->addSelect($column);
                }
                continue;
            }

            // 仮想カラム
            if ($schema->hasTable($table ?? '') && is_string($column) && $vcolumn = $schema->getTableColumnExpression($table, $column, 'select', $this->database)) {
                $key = is_int($key) ? $column : $key;
                // 仮想カラムは修飾子を付与するチャンスを与えなければ実質使い物にならない（エイリアスが動的だから）
                $column = is_string($vcolumn) ? sprintf($vcolumn, $accessor) : $vcolumn;
            }

            // Expression 化出来そうならする
            $column = Expression::forge($column);

            // テーブルに紐付かない列指定で配列指定は operator(Expression 化) する
            if (!$table && is_array($column)) {
                $column = $this->database->operator($column);
            }

            // null はダミーとして扱い、取得しない
            if ($column === null) {
                assert(true); // for inspection
            }
            // 配列は subselect 化
            elseif (is_array($column)) {
                $parsed = new TableDescriptor($this->database, $key, []);
                $result[] = $detectForeign(
                    $this->database->select([$key => array_merge($parsed->column, $column)]),
                    $parsed,
                    $table,
                    ['table' => $parsed->table, 'alias' => $parsed->accessor, 'fkeyname' => $parsed->fkeyname],
                    $parsed->alias ?: $this->database->convertEntityName($parsed->table) . $parsed->fkeysuffix
                );
            }
            // stdClass なら JSON 化
            elseif (is_object($column) && get_class($column) === \stdClass::class) {
                $column = (array) $column;
                $jsonkey = array_unset($column, '$');
                $column2 = [];
                foreach ($column as $k => $v) {
                    if (is_int($k)) {
                        $k = $v;
                    }
                    $column2[$k] = $v;
                }
                $result[] = Select::forge($key, $this->database->getCompatiblePlatform()->getJsonAggExpression($column2, $jsonkey), $prefix);
            }
            // Gateway は subselect 化
            elseif ($column instanceof TableGateway) {
                $parsed = new TableDescriptor($this->database, $key, []);
                $result[] = $detectForeign(
                    $column->select(),
                    $parsed,
                    $table,
                    ['table' => $column->tableName(), 'alias' => $column->modifier(), 'fkeyname' => $column->foreign()],
                    is_int($key) ? $column->modifier() : ($parsed->accessor ?: first_key($parsed->column))
                );
            }
            // SelectBuilder の遅延モードなら subbuilders に追加するだけ
            elseif ($column instanceof SelectBuilder && $column->lazyMode) {
                $parsed = new TableDescriptor($this->database, $key, []);
                $result[] = $detectForeign(
                    clone $column,
                    $parsed,
                    $table,
                    null,
                    $parsed->condition ? ($parsed->accessor ?? $key) : $key
                );
            }
            // SelectBuilder なら文字列化したものをカッコつきで select + パラメータ追加
            elseif ($column instanceof SelectBuilder && !$column->lazyMode) {
                // サブクエリで order は無意味
                $column->detectAutoOrder(false);

                // subexists はこの段階で where が確定する
                if (($submethod = $column->getSubmethod()) !== null) {
                    $column->setSubwhere($table, $alias, null);
                    if (is_bool($submethod)) {
                        $column = $this->database->getCompatiblePlatform()->convertSelectExistsQuery($column);
                    }
                }
                $result[] = Select::forge($key, Expression::new($column->getQuery(), $column->getParams()), $prefix);
            }
            // SelectOption は単純に addSelectOption するだけ
            elseif ($column instanceof SelectOption) {
                $this->addSelectOption($column);
            }
            // Alias はそのまま
            elseif ($column instanceof Select) {
                $result[] = $column;
            }
            // Closure は callbacks に入れる
            elseif ($column instanceof \Closure) {
                $args = [];
                foreach ((new \ReflectionFunction($column))->getParameters() as $n => $param) {
                    if ($param->isDefaultValueAvailable()) {
                        if (is_null($param->getDefaultValue())) {
                            $args[$n] = $key;
                            $result[] = Select::forge($args[$n], $key, $prefix);
                        }
                        elseif (is_string($param->getDefaultValue())) {
                            [$modifier, $colname] = array_pad(explode('.', $param->getDefaultValue(), 2), -2, $accessor);
                            $args[$n] = Database::AUTO_DEPEND_KEY . "{$modifier}___{$colname}";
                            $result[] = Select::forge($key, 'NULL');
                            $result[] = Select::forge($args[$n], "{$modifier}.{$colname}", $prefix);
                        }
                        else {
                            throw new \InvalidArgumentException('Currently only ?string is allowed as default values.');
                        }
                    }
                    else {
                        $args[$n] = null;
                        $result[] = Select::forge($key, 'NULL');
                    }
                }
                $this->callbacks[$key] = [$column, array_and($args, 'is_null') ? [] : $args];
            }
            // Expression なら文字列化したものをそのまま select
            elseif ($column instanceof Expression) {
                $result[] = Select::forge($key, $column, $prefix);
            }
            // .. プレフィクスなら「親カラムの参照」（識別のために $prefix を付与しないで追加する）
            elseif (is_string($column) && strpos($column, '..') === 0) {
                $result[] = Select::forge($key, $column);
            }
            // 上記以外。文字列として扱う
            else {
                foreach (split_noempty(',', (string) $column) as $col) {
                    // エイリアスをバラす
                    [$key, $col] = Select::split($col, $key);
                    $result[] = Select::forge($key, $prefix . $col, $prefix);
                }
            }
        }

        // 上の過程で配列ごと突っ込んでいる箇所があるのでフラットにする
        $result = array_flatten($result);

        $this->sqlParts['select'] = array_merge($this->sqlParts['select'], array_unique($result));
        return $this->_dirty();
    }

    private function _buildCondition(string $type, array $predicates, bool $ack, string $andor): static
    {
        $andor = strtoupper($andor);

        $froms = array_filter($this->getFromPart(), fn($from) => is_string($from['table']));
        $tables = array_column($froms, 'table', 'alias');

        $predicates = $this->_precondition($tables, $predicates);

        $params = [];
        $ands = [];
        foreach ($predicates as $cond) {
            $placeholders = Where::build($this->database, arrayize($cond), $params, 'OR', $this->emptyCondition);
            array_set($ands, implode(' AND ', Adhoc::wrapParentheses($placeholders)), null, function ($v) { return !Adhoc::is_empty($v); });
        }

        if ($ands) {
            $ors = implode(' OR ', Adhoc::wrapParentheses($ands));
            if (!$ack) {
                $ors = 'NOT (' . $ors . ')';
            }
            $next = count($this->sqlParts[$type]);
            $key = $next === 0 ? 0 : $andor . $next;
            $this->sqlParts[$type][$key] = Expression::new($ors, $params);
        }

        return $this->_dirty();
    }

    /**
     * サブクエリを実行する
     */
    private function _subquery(array $parents, string $column): array
    {
        $subdatabase = $this->getDatabase();

        // 親カラム参照カラムを分離しておく
        $pcolumns = [];
        $selects = $this->getQueryPart('select');
        foreach ($selects as $n => $select) {
            $palias = null;
            if ($select instanceof Select && is_string($select->getActual())) {
                $palias = $select->getAlias();
                $select = $select->getActual();
            }
            if (is_string($select) && strpos($select, '..') === 0) {
                [, $pcol] = explode('..', $select, 2);
                $pcolumns[$palias ?: $pcol] = $pcol;
                unset($selects[$n]);
            }
        }
        $this->select(...$selects);

        // [子供のキー => 親の値] の配列を作成
        $psep = $this->getPrimarySeparator();
        if (self::LAZY_MODES[$this->lazyMode]['prepared']) {
            $childkeys = array_maps($this->lazyColumns, function ($v, $k) { return str_replace('.', '__', $k); });
        }
        else {
            $childkeys = array_keys($this->lazyColumns);
        }
        $conds = [];
        foreach ($parents as $n => $parent_row) {
            // 親行カラムがあるかチェック（1回で十分なので初回のみ）
            if (!isset($checked)) {
                $checked = true;
                if ($pcolumns && array_diff_key(array_flip($pcolumns), $parent_row)) {
                    throw new \OutOfBoundsException("reference undefined parent column [" . implode(', ', $pcolumns) . "].");
                }
            }
            if ($parent_row[$this->lazyParent] !== null) {
                $conds[$n] = array_combine($childkeys, explode($psep, $parent_row[$this->lazyParent]));
            }
        }

        $this->detectAutoOrder(true);

        // 後処理クロージャ（親カラムを参照して入れたり不要なカラムを伏せたり）
        $keyable = Database::METHODS[$this->lazyMethod]['keyable'];
        $entityable = Database::METHODS[$this->lazyMethod]['entity'];
        $cleanup = function ($crows, $n) use ($keyable, $entityable, $pcolumns, $parents) {
            if (!$crows) {
                return $crows;
            }
            if ($keyable !== null) {
                if ($this->lazyCondition) {
                    $crows = array_filter($crows, function ($crow) use ($parents, $n) {
                        foreach ($this->lazyCondition as $pcol => $pval) {
                            if (strcmp($parents[$n][$pcol], $pval) !== 0) {
                                return false;
                            }
                        }
                        return true;
                    });
                    if ($keyable === false) {
                        $crows = array_values($crows);
                    }
                }
                if ($entityable) {
                    foreach ($crows as $k => $crow) {
                        foreach ($pcolumns as $alias => $pcolumn) {
                            $crows[$k][$alias] = $parents[$n][$pcolumn];
                        }
                        unset($crows[$k][Database::AUTO_PARENT_KEY], $crows[$k][Database::AUTO_CHILD_KEY]);
                    }
                }
            }
            else {
                if ($entityable) {
                    foreach ($this->lazyCondition as $pcol => $pval) {
                        if (strcmp($parents[$n][$pcol], $pval) !== 0) {
                            return false;
                        }
                    }
                    foreach ($pcolumns as $alias => $pcolumn) {
                        $crows[$alias] = $parents[$n][$pcolumn];
                    }
                    unset($crows[Database::AUTO_PARENT_KEY], $crows[Database::AUTO_CHILD_KEY]);
                }
            }
            return $crows;
        };

        switch ($this->lazyMode) {
            case self::LAZY_MODE_EAGER:
            case self::LAZY_MODE_BATCH:
                $fetchChildren = function () use ($conds) {
                    $subdatabase = $this->getDatabase();

                    // 親行から抽出した where（queryInto してるのは誤差レベルではなく速度に差が出るから）
                    $expr = $subdatabase->getCompatiblePlatform()->getPrimaryCondition($conds);
                    $this->andWhere($subdatabase->queryInto($expr));

                    // 子供行の limit は親の範囲内の limit として利用する
                    $suboffset = $this->getQueryPart('offset');
                    $sublength = $this->getQueryPart('limit') === null ? null : $suboffset + $this->getQueryPart('limit');
                    $this->limit(null, null);

                    // 子供行の取得とグループ化
                    $children = [];
                    $counter = [];
                    $child_rows = $subdatabase->fetchArray($this);
                    foreach ($child_rows as $child_row) {
                        $pckey = $child_row[Database::AUTO_PARENT_KEY];
                        if ($suboffset !== null || $sublength !== null) {
                            $counter[$pckey] = ($counter[$pckey] ?? 0) + 1;
                            if ($suboffset !== null && $suboffset >= $counter[$pckey]) {
                                continue;
                            }
                            if ($sublength !== null && $sublength < $counter[$pckey]) {
                                continue;
                            }
                        }
                        $children[$pckey][] = $child_row;
                    }
                    return $children;
                };

                if ($this->lazyMode === self::LAZY_MODE_EAGER) {
                    $children = $fetchChildren();
                    foreach ($parents as $n => $parent_row) {
                        $pkey = $parent_row[$this->lazyParent];
                        unset($parents[$n][$this->lazyParent]);
                        $crows = $children[$pkey] ?? [];
                        $parents[$n][$column] = $cleanup($subdatabase->perform($crows, $this->lazyMethod), $n);
                    }
                }
                if ($this->lazyMode === self::LAZY_MODE_BATCH) {
                    $children = null;
                    foreach ($parents as $n => $parent_row) {
                        $pkey = $parent_row[$this->lazyParent];
                        unset($parents[$n][$this->lazyParent]);
                        $parents[$n][$column] = (function () use (&$children, $parent_row, $pkey, $fetchChildren, $subdatabase, $cleanup, $n) {
                            $children = $children ?? $fetchChildren();
                            $crows = $children[$pkey] ?? [];
                            yield from $cleanup($subdatabase->perform($crows, $this->lazyMethod), $n);
                        })();
                    }
                }
                break;
            case self::LAZY_MODE_FETCH:
            case self::LAZY_MODE_YIELD:
                if (!$this->getPreparedStatement()) {
                    $this->andWhere(array_sprintf($childkeys, '%2$s = :%1$s'));
                    $this->prepare();
                }
                $lazyMethod = 'fetch' . $this->lazyMethod;

                if ($this->lazyMode === self::LAZY_MODE_FETCH) {
                    foreach ($parents as $n => $parent_row) {
                        unset($parents[$n][$this->lazyParent]);
                        $parents[$n][$column] = $cleanup($subdatabase->$lazyMethod($this, $conds[$n]), $n);
                    }
                }
                if ($this->lazyMode === self::LAZY_MODE_YIELD) {
                    foreach ($parents as $n => $parent_row) {
                        unset($parents[$n][$this->lazyParent]);
                        $parents[$n][$column] = (function () use ($subdatabase, $lazyMethod, $conds, $n, $cleanup) {
                            yield from $cleanup($subdatabase->$lazyMethod($this, $conds[$n]), $n);
                        })();
                    }
                }
                break;
        }

        return $parents;
    }

    private function _dirty(): static
    {
        unset($this->sql);
        $this->resetResult();
        return $this;
    }

    /**
     * 取得クラスを指定。クラス名ならそのクラスで、コールバックならそれの呼び出し、になる
     *
     * このメソッドを使うとこのインスタンスが返すレコード配列の型を指定できる。
     * 指定の方法は大まかには下記の4種類。
     *
     * 1. callable （行配列を受け取るクロージャ）
     *     - 最も汎用性がある
     * 2. クラス名
     *     - 与えたクラスのインスタンスで返却されるようになる
     * 3. null （あるいは未指定）
     *     - 駆動表から導き出されるエンティティクラスで返却されるようになる（指定がない場合はデフォルトエンティティ）
     * 4. "array" という文字列
     *     - 配列で返却するようになる（実質的に解除動作として動作する）
     *
     * ```php
     * # 1. callable
     * $qb->column('table_name')->cast(function ($row) {
     *     // $row はレコードの各行の配列
     *     return new \ArrayObject($row, \ArrayObject::ARRAY_AS_PROPS);
     * });
     * // table_name のレコードを ArrayObject インスタンスで返すようになる
     *
     * # 2. クラス名
     * $qb->column('table_name')->cast(EntityClass::class);
     * // table_name のレコードを EntityClass インスタンスで返すようになる
     *
     * # 3. null（省略）
     * $qb->column('table_name')->cast();
     * // table_name のレコードを TableName インスタンスで返すようになる（駆動表 -> エンティティ名は Database に対して指定する）
     *
     * # 4. "array"
     * $qb->column('table_name')->cast("array");
     * // 何もしなかった場合と変わらない。が、上記の 1～3 で設定したものを解除できるという重要な役割がある
     * ```
     *
     * なお、 このメソッドを呼んでも、 `lists` や `pairs` には一切影響しない。
     * これらは配列を返すメソッドであり、「レコード」という概念が通用しない。 `value` もスカラー値なので同様。
     *
     * @param null|string|callable $classname 取得クラス
     */
    public function cast($classname = null): static
    {
        // null は特別扱い(駆動表をエンティティにする)
        if ($classname === null) {
            $froms = $this->getFromPart();
            $from = reset($froms);
            $from = $from === false ? [] : $from;
            $classname = $this->database->getEntityClass($from['alias'] ?? '');
            if ($classname === Entity::class) {
                $classname = $this->database->getEntityClass($from['table'] ?? '');
            }
            foreach ($this->subbuilders as $subselect) {
                if ($subselect->caster === null) {
                    $subselect->cast(null);
                }
            }
        }

        // array は特別扱い(array に戻す)
        if ($classname === 'array') {
            $this->caster = $classname;
            return $this;
        }

        // callable は素で OK
        if (is_callable($classname)) {
            $this->caster = \Closure::fromCallable($classname);
            return $this;
        }

        if (!class_exists($classname)) {
            throw new \InvalidArgumentException("class '$classname' is not exists.");
        }

        if (!is_subclass_of($classname, Entityable::class)) {
            throw new \InvalidArgumentException("'$classname' must be implements Entityable.");
        }

        $this->caster = $classname;
        return $this;
    }

    /**
     * 行キャストクロージャを取得する
     *
     * @ignore
     */
    public function getCaster(): ?\Closure
    {
        if ($this->caster === null || $this->caster === 'array') {
            return null;
        }
        if (is_callable($this->caster)) {
            return $this->caster;
        }
        if (is_string($this->caster)) {
            $caster = $this->caster;
            return function ($row) use ($caster) {
                /** @var Entityable $entity */
                $entity = new $caster();
                return $entity->assign($row);
            };
        }

        // 今のところあり得ないが将来に備えて例外は投げておく
        throw new \DomainException("caster is invalid type (" . gettype($this->caster) . ")."); // @codeCoverageIgnore
    }

    /**
     * submethod を設定する
     *
     * Database 経由・あるいは内部から呼ばれる前提で外からは呼ばれない。
     *
     * @ignore
     */
    public function setSubmethod(null|bool|string $method): static
    {
        if ($method === null) {
            $this->submethod = $method;
            return $this;
        }
        if ($method === true) {
            $this->submethod = $method;
            return $this->exists();
        }
        if ($method === false) {
            $this->submethod = $method;
            return $this->notExists();
        }
        if (is_string($method)) {
            $this->submethod = $method;
            return $this;
        }

        // 今のところあり得ないが将来に備えて例外は投げておく
        throw new \DomainException('submethod is invalid type.'); // @codeCoverageIgnore
    }

    /**
     * submethod を取得する
     *
     * @ignore
     */
    public function getSubmethod(): null|bool|string
    {
        return $this->submethod;
    }

    /**
     * subexists の where 句を設定する
     *
     * 設定されたら true を返す
     *
     * @ignore
     */
    public function setSubwhere(string $table, ?string $alias = null, ?string $fkeyname = null): bool
    {
        $froms = $this->getFromPart();
        $from = reset($froms);

        $fkeyname = $fkeyname ?: $from['fkeyname'];

        $mapper = [];
        if ($from['condition']) {
            $mapper = array_merge($mapper, array_each($from['condition'], function (&$carry, $v) {
                foreach ($v as $c => $p) {
                    $carry[$c] = $p;
                }
            }, []));
        }
        if ($fkeyname !== '') {
            $fkey = $fkeyname;
            $mapper = array_merge($mapper, $this->database->getSchema()->getForeignColumns($table, $from['table'], $fkey));
        }

        if (!$mapper) {
            if (func_num_args() === 3) {
                throw new \UnexpectedValueException("has not foreign key between '{$table}' and '{$from['table']}' ($fkeyname).");
            }
            return false;
        }

        if ($this->subwhere === "$table:$fkeyname") {
            return false;
        }
        $this->subwhere = "$table:$fkeyname";
        $pre_p = $alias ?: $table;
        $pre_c = $from['alias'];
        $this->andWhere(array_merge(
            @instance_of($fkey ?? null, ForeignKeyConstraint::class)?->getOption('condition') ?? [],
            array_sprintf($mapper, "$pre_c.%2\$s = $pre_p.%1\$s"),
        ));

        if ($this->submethod === 'query') {
            if (!$this->sqlParts['select']) {
                $this->select(...array_sprintf($mapper, "$pre_c.%2\$s"));
            }
        }
        return true;
    }

    /**
     * 自身がサブクエリ化されたときの演算を定義する
     *
     * ```php
     * # コメントを10つ以上持つ記事を返す
     * $db->select('t_article A', [
     *     $db->subcount('t_comment C')->operatize('>=', 10),
     * ]);
     * // SELECT A.* FROM t_article A WHERE (SELECT COUNT(*) FROM t_comment C WHERE C.article_id = A.article_id) >= 10
     * ```
     */
    public function operatize(?string $operator, mixed $operands = []): static
    {
        if ($operator === null) {
            $this->sqlParts['operator'] = null;
            return $this->_dirty();
        }

        $platform = $this->database->getCompatiblePlatform();
        if (strpos($operator, '?') === false) {
            $this->sqlParts['operator'] = Operator::new($platform, $operator, ' ', $operands);
        }
        else {
            $this->sqlParts['operator'] = Operator::new($platform, Operator::RAW, $operator, $operands);
        }
        return $this->_dirty();
    }

    /**
     * 各種設定メソッドへのプロクシメソッド
     *
     * - see {@link column()}
     * - see {@link where()}
     * - see {@link orderBy()}
     * - see {@link limit()}
     * - see {@link groupBy()}
     * - see {@link having()}
     */
    public function build(array $queryParts, bool $append = false): static
    {
        if (array_key_exists('column', $queryParts) && $queryParts['column']) {
            $this->{($append ? 'add' : '') . 'column'}($queryParts['column']);
        }
        if (array_key_exists('where', $queryParts) && $queryParts['where']) {
            $this->{($append ? 'and' : '') . 'where'}($queryParts['where']);
        }
        if (array_key_exists('orderBy', $queryParts) && $queryParts['orderBy']) {
            $this->{($append ? 'add' : '') . 'orderBy'}($queryParts['orderBy']);
        }
        if (array_key_exists('limit', $queryParts) && $queryParts['limit']) {
            $this->limit($queryParts['limit']);
        }
        if (array_key_exists('groupBy', $queryParts) && $queryParts['groupBy']) {
            $this->{($append ? 'add' : '') . 'groupBy'}($queryParts['groupBy']);
        }
        if (array_key_exists('having', $queryParts) && $queryParts['having']) {
            $this->{($append ? 'and' : '') . 'having'}($queryParts['having']);
        }

        return $this;
    }

    /**
     * スコープを当てる
     */
    public function scope(string $tablename, string|array $scope, ...$args): static
    {
        $gateway = $this->database->$tablename->clone();
        $gateway->scope($scope, $args);
        $sparam = $gateway->getScopeParams([]);
        $scolumn = array_unset($sparam, 'column');
        $this->addColumn($scolumn);
        return $this->build($sparam, true);
    }

    /**
     * lazyMode を設定する
     *
     * このメソッドを呼ぶと fetch 系メソッドは実行されなくなる。
     * 引数無しで呼ぶと解除される。
     *
     * - eager: 親の取得と同時に一括取得する（親キーの IN）
     * - batch: 最初のアクセス時に一括取得する（親キーの IN の Generator）
     * - fetch: 都度クエリを投げる（prepared statement）
     * - yield: 必要になったらクエリを投げる（prepared statement の Generator）
     */
    public function setLazyMode(?string $lazyMode = null): static
    {
        if ($lazyMode !== null && !isset(self::LAZY_MODES[$lazyMode])) {
            throw new \InvalidArgumentException('$mode is must be self::LAZY_MODE_* (' . implode('|', array_keys(self::LAZY_MODES)) . ')');
        }

        $this->lazyMode = func_num_args() ? $lazyMode : $this->getDefaultLazyMode();
        return $this;
    }

    /**
     * WITH 句を設定する
     *
     * $name は何も加工されずにそのままクエリに埋め込まれる。
     * 今のところ (colname) などもここに含める用途となる。
     *
     * $query に null を指定すると削除として働く。
     */
    public function with(string $name, null|string|Queryable $query): static
    {
        if ($query === null) {
            unset($this->sqlParts['with'][$name]);
        }
        else {
            $this->sqlParts['with'][$name] = $query;
        }
        return $this->_dirty();
    }

    /**
     * select オプションを追加する
     *
     * SELECT オプションとは「SELECT 句のカラム群の前に（カラムとは区別されて）置かれる文字列」のこと。
     * 典型的には DISTINCT や STRAIGHT_JOIN など。
     *
     * ```php
     * $qb->addSelectOption(SelectOption::SQL_CACHE)->column('test');
     * // SELECT SQL_CACHE test.* FROM test
     * ```
     */
    public function addSelectOption(?string $option): static
    {
        if (!$option) {
            return $this;
        }

        if (!in_array($option, $this->sqlParts['option'])) {
            $this->sqlParts['option'][] = $option;
        }
        return $this->_dirty();
    }

    /**
     * [table => [col1, col2]] のような指定を出来るようにする（クリア版）
     *
     * かなり多彩な指定ができる（複雑とも言う）。
     *
     * ```php
     * $qb->column([
     *     'table1' => [
     *         'aliasA'  => 'columnA',
     *         'aliasB'  => 'columnB',
     *         '+table2'  => [
     *             'aliasA' => 'columnA',
     *             'aliasB' => 'columnB',
     *         ],
     *         'table3' => [
     *             'aliasA' => 'columnA',
     *             'aliasB' => 'columnB',
     *         ],
     *     ],
     *     'table4' => [
     *         'aliasA'          => 'columnA',
     *         'aliasB'          => 'columnB',
     *         'aliasC|datetime' => 'columnC',
     *     ],
     * ]);
     * ```
     *
     * 上記が基本構文となる。原則的には「取得したいテーブルの配下にカラムを置く」になる。
     * ネストさせると JOIN あるいは子テーブル取得になる（先頭の JOIN 記号で区別する）。
     * 並列に並べると JOIN ではなく複数の FROM になる。
     *
     * 上記で言えば table1, table4 を駆動表として、table2 を JOIN、table3 を子テーブルとして取得する、というクエリになる。
     *
     * 実際は怠惰に文字列だけで指定できたり、糖衣構文が多数存在する。そもそも「テーブル名」を書く場所にテーブル記法が使えたりする。
     * ざっくりと記法を一覧したものが下記（No が飛んでいるのに深い意味はない）。
     *
     * | No | type                                             | 説明
     * | --:|:--                                               |:--
     * |  0 | `['cond1', 'cond2' => 1]`                        | ネスト配列に素の配列を混ぜると JOIN 条件扱い
     * |  1 | `"**"`                                           | 子テーブルを含めた全テーブル全列を取得
     * |  2 | `null`                                           | null を与えると「何も取得しない」を明示
     * |  4 | `"!hoge"`                                        | hoge 列**以外**を取得
     * |  5 | `"!"`                                            | 仮想カラムを含めたテーブルの全列を取得（これは「空文字カラム以外を全て」を意味するので結局全てのカラムが得られる、ということになる）
     * |  8 | `"..hoge"`                                       | subselect 時において親のカラムを表す
     * | 10 | `"+prefix.column_name"`                          | JOIN 記号＋ドットを含む文字列は prefix テーブルと JOIN してそのカラムを取得
     * | 11 | `Expression::new("NOW()")`                       | {@link Expression} を与えると一切加工せずそのまま文字列を表す
     * | 12 | `"NOW()"`                                        | 上と同じ。 `()` を含む文字列は自動で {@link Expression} 化される
     * | 13 | `(object) ['$' => 'id', 'key' => 'value']`       | stdClass を与えると JSON で集約される。$ を与えるとキーになる
     * | 21 | `['alias|typename' => 'column']`                 | 配列のキーをパイプでつなぐとその型に変換されて取得できる
     * | 22 | `['alias' => function($row){}]`                  | デフォルト値がないクロージャは行全体が渡ってくるコールバックになる
     * | 25 | `['cname' => function($cname=null){}]`           | デフォルト値が null のクロージャはカラム値が単一で渡ってくるコールバックになる
     * | 26 | `['alias' => function($c1='id', $c2='name'){}]`  | デフォルト値が文字列のクロージャそれぞれが個別で渡ってくるコールバックになる
     * | 27 | `function(){return function($v){return $v;};}`   | クロージャの亜種。クロージャを返すクロージャはそのままクロージャとして活きるのでメソッドのような扱いにできる
     * | 30 | `Gateway object`                                 | Gateway の表すテーブルとの {@link Database::subselect()} 相当の動作
     * | 31 | `['+alias' => Gateway object]`                   | Gateway の表すテーブルとの JOIN を表す
     * | 50 | `'TableDescriptor'`                              | 「テーブル名」を書く場所にはテーブル記法が使用できる（駆動表）
     * | 51 | `['+TableDescriptor' => ['*']]`                  | 「テーブル名」を書く場所にはテーブル記法が使用できる（JOIN）
     * | 80 | `SelectOption::DISTINCT()`                       | SelectOption インスタンスを与えると `addSelectOption` と同等の効果を示す
     * | 98 | `['' => ['expression']]`                         | 空キーは「テーブルに紐付かないカラム指定」を表す
     *
     * 上記の通り、尋常ではないほど複雑なのでサンプルコードを以下に記す。
     *
     * ```php
     * # No.0： 素の配列を混ぜると JOIN 条件になる（形式は where と全く同じ）
     * $qb->column([
     *     't_article A.*' => [
     *         '+t_comment: C.*' => [ // 外部キーがあると自動で ON が付くので、外すことを明示するために: が必要
     *             // 素の配列はそのテーブルと親テーブル（この場合 t_article と t_comment） の結合条件になる
     *             ['A.article_id = C.article_id', 'C.delete_flg' => 0],
     *         ],
     *     ],
     * ]);
     *
     * # No.1： t_ancestor に紐づく t_parent に紐づく t_child を怠惰に取得（*の数だけ子リレーションを辿る）
     * $qb->column('t_ancestor.***');
     *
     * # No.2： null は何も取得しないがキーは活きる（t_article に紐づくリレーションを怠惰に取得したいが、特定テーブルは除きたい場合など）
     * $qb->column([
     *     't_article' => [
     *         '***',
     *         't_imgblob' => null,
     *         't_hugelog' => null,
     *     ],
     * ]);
     *
     * # No.4： ! を付けるとそのテーブル内でそれ以外を取得する
     * $qb->column([
     *     't_article' => [
     *         '!content', // 例えば一覧画面でデータ量の大きい本文を取得したくないときなど
     *     ],
     * ]);
     *
     * # No.8： "..hoge" で subselect における親カラムを参照できる
     * $qb->column([
     *     't_article' => [
     *         '*',
     *         't_comment C' => [
     *             '..article_title',             // 子テーブルのコンテキストで親のカラムが参照できる
     *             'atitle' => '..article_title', // 全く同じ。エイリアスも貼れる
     *         ],
     *     ],
     * ]);
     *
     * # No.10： 自動プレフィックス JOIN
     * $qb->column([
     *     't_comment' => [
     *         // このように値に join 記号＋テーブル.カラムを置くと自動で JOIN される
     *         '+t_article.article_title',
     *         '+t_article.tags',
     *         // このように2つ並べても同テーブルであれば JOIN されるのは1回のみ
     *     ],
     * ]);
     *
     * # No.11, 12： Expression
     * $qb->column([
     *     't_article' => [
     *         'upper_title' => Expression::new('UPPER(article_title)'), // タイトルを大文字で取得
     *         'upper_title' => 'UPPER(article_title)',                  // 全く同じ。カッコを含めば自動で Expression 化される
     *     ],
     * ]);
     *
     * # No.22： 行全体を受け取るクロージャ
     * $qb->column([
     *     't_article' => [
     *         // $row は行全体が渡ってくる
     *         'row' => function($row){},
     *     ],
     * ]);
     *
     * # No.25, 26： カラム値を受け取るクロージャ
     * $qb->column([
     *     't_article' => [
     *         // デフォルト値を null にするとキーのカラム値が渡ってくる
     *         'id'     => function($id=null){return $id * 10;},
     *         // デフォルト値でカラムを指定できる
     *         'idname' => function($id='id', $name='name'){return "$id: $name";},
     *     ],
     * ]);
     *
     * # No.27： クロージャを返すクロージャ
     * $tuple = $qb->column([
     *     't_article.*' => [
     *         // クロージャ内の $this は行そのものを表す ArrayAccess なオブジェクト（現実装は ArrayObject）で bind される
     *         'func'   => function(){return function($prefix){return $prefix . $this['name'];};},
     *         // 静的クロージャは bind されない
     *         'static' => function($row){return function($prefix)use($row){return $prefix . $row['name'];};},
     *     ],
     * ])->tuple();
     * // 'func' や 'static' にはクロージャが格納されているので呼び出しが可能
     * $tuple['func']('prefix-');   // => 'prefix-hogehoge'
     * $tuple['static']('prefix-'); // => 'prefix-hogehoge'
     *
     * # No.30, 31：配列で指定する箇所は Gateway も指定できる
     * $qb->column([
     *     't_article' => [
     *         'comments1'  => $db->t_comment, // t_comment を子テーブルとして取得する
     *         '+comments2' => $db->t_comment, // t_comment と JOIN される
     *     ],
     * ]);
     *
     * # No.50, 51：テーブル記法
     * $qb->column('t_article(1)'); // 主キー = 1 と同じ
     * $qb->column([
     *     // 駆動表にも使えるし
     *     't_article(1) AS A' => [
     *         // JOIN 表にも使える
     *         '+t_comment@scope[state: active] AS C' => ['*'],
     *     ]
     * ]);
     * // 応用。アクティブな記事をID昇順で10件取り、そのそれぞれのコメントを作成日降順で3件ずつ取る（いわゆるグループ内のN件取得）
     * $qb->column([
     *     't_article[state: active]+id#0-10 AS A.*' => [
     *         't_comment-create_date#0-3 AS C.*' => []
     *     ],
     * ]);
     *
     * # No.80：SelectOption を与える
     * $qb->column([
     *     't_article' => [
     *         SelectOption::DISTINCT(),
     *         '*',
     *     ],
     * ]);
     *
     * # No.98： 空文字キーによるテーブルに紐付かないカラム指定（勝手に修飾されたり JOIN されたりせず、シンプルに SELECT 句に追加される）
     * $qb->column([
     *     't_table' => '*',
     *     '' => [
     *         'now' => 'NOW()',
     *         'ttc' => 't_table.colA',                 // 修飾子として動作する
     *         'ope' => ['column_name:LIKE' => 'hoge'], // operator として動作する
     *     ],
     * ]);
     * ```
     */
    public function column($tableDescriptor): static
    {
        return $this->resetQueryPart(['select', 'from', 'join', 'where', 'groupBy', 'orderBy'])->addColumn($tableDescriptor);
    }

    /**
     * [table => [col1, col2]] のような指定を出来るようにする（{@link column()} の追加版）
     *
     * @inheritdoc column()
     */
    public function addColumn($tableDescriptor, ?string $parent = null, bool $defaultScoped = false): static
    {
        foreach (TableDescriptor::forge($this->database, $tableDescriptor, $this->getSubmethod() === 'query' ? [] : ['*']) as $descriptor) {
            $this->_buildColumn($descriptor->column, $descriptor->table, $descriptor->alias);

            // テーブル未指定ならカラムが確定したこの時点で終わり
            if (!$descriptor->table) {
                continue;
            }

            $this->from($descriptor->table, $descriptor->alias, $descriptor->jointype, $descriptor->condition, $descriptor->fkeyname, $parent);

            if ($descriptor->group) {
                $this->addGroupBy([$descriptor->accessor => $descriptor->group]);
            }
            if ($descriptor->order) {
                if ($parent) {
                    $this->joinOrders[] = array_strpad($descriptor->order, $descriptor->accessor . '.');
                }
                else {
                    $this->addOrderBy(array_strpad($descriptor->order, $descriptor->accessor . '.'));
                }
            }
            if ($descriptor->offset || $descriptor->limit) {
                $this->limit($descriptor->limit, $descriptor->offset);
            }

            $defaultScope = $defaultScoped ? [] : $this->getDefaultScope();

            if ($defaultScope || $descriptor->scope) {
                $gateway = $this->database->{$descriptor->table}->clone();
                $gateway->as($descriptor->alias);
                $gateway->scope(array_merge($defaultScope, $descriptor->scope));
                $sparam = $gateway->getScopeParams([]);
                $scolumn = array_unset($sparam, 'column');
                $this->addColumn($scolumn, $parent, !!$defaultScope);
                $this->build($sparam, true);
            }

            foreach ($descriptor->jointable as $join) {
                $jointable = $join->descriptor;
                $jcondition = $join->condition;
                $key = $join->key;

                if ($defaultScope || $join->scope) {
                    $key = $join->joinsign . $join->table . ' ' . $join->alias;
                    $jointable = $this->database->{$join->table}->clone();
                    $jointable->as($join->alias);
                    $jointable->column($join->descriptor);
                    $jointable->scope(array_merge($defaultScope, $join->scope));
                }
                if ($jointable instanceof TableGateway) {
                    $this->hint($jointable->hint(), $jointable->modifier());
                    $joinable = $jointable->joinize();
                    if (isset($joinable['order'])) {
                        $this->joinOrders[] = $joinable['order'];
                        $jointable = $joinable['table'];
                        $jointable[] = $joinable['condition'];
                    }
                    else {
                        $jcondition = array_merge($join->condition, $joinable['condition']);
                        $jointable = $joinable['table'];
                    }
                }

                if ($jointable instanceof SelectBuilder) {
                    $this->from($jointable, $join->accessor, $join->jointype, $jcondition, $join->fkeyname, $parent);
                }
                else {
                    $this->addColumn([$key => $jointable], $descriptor->accessor);
                }
            }
        }

        return $this->_dirty();
    }

    /**
     * select 列を設定する（クリア版）
     */
    public function select(...$selects): static
    {
        $this->sqlParts['select'] = [];
        return $this->addSelect(...$selects);
    }

    /**
     * select 列を設定する（{@link select()} の追加版）
     */
    public function addSelect(...$selects): static
    {
        foreach ($selects as $select) {
            $this->_buildColumn($select);
        }
        return $this->_dirty();
    }

    /**
     * カラム・エイリアスの完全一致で select 句から取り除く
     *
     * クロージャを与えるとコールバックされ、 true 相当を返した時に取り除かれる。
     * 文字列を与えるとエイリアス or 完全カラムに一致した時に取り除かれる。
     * 数値を与えるとその番目が取り除かれる（都度連番はリセットされるので注意）。
     */
    public function unselect(...$aliases): static
    {
        foreach ($this->sqlParts['select'] as $n => $select) {
            foreach ($aliases as $alias) {
                if ($alias instanceof \Closure) {
                    $unset = $alias($select);
                }
                elseif (ctype_digit("$alias")) {
                    $unset = $n === intval($alias);
                }
                elseif ($select instanceof Select) {
                    $unset = $alias === $select->getAlias() || Database::AUTO_PRIMARY_KEY . $alias === $select->getAlias();
                }
                else {
                    $unset = $alias === $select;
                }

                if ($unset) {
                    unset($this->sqlParts['select'][$n]);
                    if ($select instanceof Select) {
                        unset($this->callbacks[$select->getAlias()]);
                        unset($this->subbuilders[$select->getAlias()]);
                    }
                    break;
                }
            }
        }

        $this->sqlParts['select'] = array_values($this->sqlParts['select']);
        return $this->_dirty();
    }

    /**
     * FROM 句（JOIN 込）を構成する
     *
     * 結合タイプや結合条件をまとめて指定して FROM, JOIN を構成できるが、複雑極まりないので使用は非推奨（FROM 句の設定は {@link column()} を使用すれば基本的に不要）。
     */
    public function from($table, ?string $alias = null, ?string $type = null, $condition = [], ?string $fkeyname = null, ?string $fromAlias = null): static
    {
        $tables = $this->getFromPart();
        $froms = array_lookup($tables, 'table');
        $schema = $this->database->getSchema();

        // $table, $alias の解決（配列・ビルダ・文字列を受け入れる）
        if (is_array($table)) {
            [$alias, $table] = first_keyvalue($table);
        }
        if (!$alias && is_string($table)) {
            [$alias, $table] = Select::split($table, $alias);
        }
        if ($table instanceof Queryable) {
            if ($alias === null) {
                $alias = '__dbml_auto_from_' . count($froms);
            }
        }

        $columns = is_string($table) && $schema->hasTable($table) ? array_filter($schema->getTableColumns($table), function (Column $column) {
            return !($column->getPlatformOptions()['virtual'] ?? false);
        }) : [];

        // $fkeyname, $fromAlias の解決（大抵はどちらか一方が決まればどちらか一方も決まる）
        if (empty($fromAlias)) {
            if ($fkeyname !== '') {
                $ftable = $fkeyname === null ? [] : $schema->getForeignTable($fkeyname);
                if ($ftable) {
                    [$local, $foreign] = first_keyvalue($ftable);
                    if ($table === $local) {
                        $fromAlias = array_search($foreign, $froms, true);
                    }
                    elseif ($table === $foreign) {
                        $fromAlias = array_search($local, $froms, true);
                    }
                }
                else {
                    foreach (array_reverse($froms, true) as $falias => $from) {
                        if (is_object($from)) {
                            continue;
                        }
                        $fromAlias = $falias;
                        $fkey = $fkeyname;
                        if ($schema->getForeignColumns($table, $from, $fkey)) {
                            break;
                        }
                    }
                }
            }
            else {
                end($froms);
                $fromAlias = key($froms);
            }
        }
        $fromTable = $froms[$fromAlias] ?? null;
        $joinAlias = $alias ?: $table;

        // 外部キーの解決
        $fcols = [];
        $direction = null;
        if ($type !== null && $fkeyname !== '') {
            $fkey = $fkeyname;
            $fcols = $schema->getForeignColumns($table ?? '', $fromTable ?? '', $fkey, $direction) ?: [];
            if (!$fcols && $fromTable !== null && "$fkeyname" !== "") {
                throw new \UnexpectedValueException("foreign key '$fkeyname' is not exists between $table<->$fromTable.");
            }
        }

        // $condition の解決
        $condition = arrayize($condition);
        $stdclasses = array_unset($condition, function ($v) { return $v instanceof \stdClass; }, []);

        if ($fromAlias) {
            foreach ($stdclasses as $cond) {
                $condition = array_merge(array_sprintf((array) $cond, fn($v, $k) => sprintf('%s.%s = %s.%s', $joinAlias, is_int($k) ? $v : $k, $fromAlias, $v)), $condition);
            }
        }
        $virtualCondition = [];
        if (isset($fkey) && $fkey instanceof ForeignKeyConstraint) {
            $virtualAlias = $fkey->getForeignTableName() === $fromTable ? $fromAlias : $joinAlias;
            $virtualCondition = array_strpad(@$fkey->getOption('condition') ?? [], "$virtualAlias.");
        }
        $condition = array_merge(
            $virtualCondition,
            array_sprintf($fcols, fn($v, $k) => sprintf('%s.%s = %s.%s', $joinAlias, $v, $fromAlias, $k)),
            Adhoc::modifier($joinAlias, $columns, $condition),
        );

        // $type の解決
        if (strcasecmp($type ?? '', 'AUTO') === 0) {
            $type = $condition ? 'LEFT' : null;
            if ($fcols) {
                $cols1 = array_flip(array_values($fcols));
                $cols2 = $fcols;

                $nullable = static function (Column $c) { return !$c->getNotnull(); };
                $join_nullable = array_find_first(array_intersect_key($schema->getTableColumns($table), $cols1), $nullable);
                $from_nullable = array_find_first(array_intersect_key($schema->getTableColumns($fromTable), $cols2), $nullable);
                // 4パターンで inner,left,right,full に対応してもいいけど、旨味が少ない（勝手に right されても使い勝手が悪い）上、full 対応の DBMS は少ない
                if ($join_nullable || $from_nullable) {
                    $type = 'LEFT';
                }
                elseif ($direction === true) {
                    $type = 'LEFT';
                }
                else {
                    $type = 'INNER';
                }
            }
        }

        if (is_string($table) && $schema->hasTable($table)) {
            $cte_table = $schema->getTable($table);
            if ($cte_table->hasOption('cte')) {
                $cte_option = $cte_table->getOption('cte');
                $this->with("$table{$cte_option['columns']}", $cte_option['query']);
            }
        }

        $isfrom = $type === null || (empty($fromAlias) && empty($froms));

        if ($isfrom) {
            // for compatible 既設定エイリアスならスルー
            // $gateway->getScopeParams で駆動表が2回呼ばれてしまう対策で、そこを解決すれば不要 or 例外になる
            if (isset($froms[$joinAlias]) && $froms[$joinAlias] === $table) {
                return $this;
            }

            $this->sqlParts['from'][] = [
                'table'     => $table,
                'alias'     => $alias,
                'fkeyname'  => $fkeyname,
                'condition' => $stdclasses,
            ];
            return $this->andWhere($condition);
        }

        $qb = SelectBuilder::new($this->database);
        $qb->sqlParts = $this->sqlParts;
        $qb->where($condition);
        if ($table instanceof SelectBuilder) {
            $qb->andWhere($table->onConditions);
        }
        $conditionExpr = Expression::new($this->_getConditionClause($qb->sqlParts['where'] ?: [1]), $qb->getParams('where'));

        // 既設定エイリアスならスルー
        if (isset($froms[$joinAlias]) && $froms[$joinAlias] === $table) {
            // ただし外部キーが異なるなら安全のため例外
            $fkeyL = $fkeyname;
            $fkeyR = $tables[$joinAlias]['fkeyname'];
            if (strlen($fkeyL ?? '') && strlen($fkeyR ?? '') && $fkeyL !== $fkeyR) {
                throw new \UnexpectedValueException("same table(alias) specified($fkeyL, $fkeyR), must be table as alias");
            }
            // 同上（Conditionチェック）
            $condL = $conditionExpr;
            $condR = $tables[$joinAlias]['condition'];
            if ($condL->getQuery() !== $condR->getQuery() || $condL->getParams() !== $condR->getParams()) {
                throw new \UnexpectedValueException("same table(alias) specified($condL, $condR), must be table as alias");
            }

            return $this;
        }

        $this->sqlParts['join'][$fromAlias][] = [
            'type'      => $type,
            'table'     => $table,
            'alias'     => $alias,
            'fkeyname'  => $fkeyname,
            'condition' => $conditionExpr,
        ];

        return $this->_dirty();
    }

    /**
     * 結合タイプや結合条件、外部キーを指定して JOIN する
     *
     * 実際は下記のようなエイリアスメソッドが定義されているのでそちらを使うことが多く、明示的に呼ぶことはほとんどない。
     * さらに単純な JOIN であれば {@link column()} でも可能なため、ますます出番はない。
     *
     * ```php
     * # 指定条件で ON して JOIN
     * $qb->from('t_from')->innerJoinOn('t_join', ['hoge = fuga']);
     * // SELECT  FROM t_from INNER JOIN t_join ON hoge = fuga
     *
     * # 外部キーカラムで ON して JOIN
     * $qb->from('t_from')->innerJoinForeign('t_join', 'ForeignKeyName');
     * $qb->from('t_from')->innerJoinForeign('t_join'); // テーブル間外部キーが1つなら省略可能
     * // SELECT  FROM t_from INNER JOIN t_join ON t_from.foreign_col = t_join.foreign_col
     *
     * # 外部キーカラムと指定条件で ON して JOIN
     * $qb->from('t_from')->innerJoinForeignOn('t_join', ['hoge = fuga'], 'ForeignKeyName');
     * // SELECT  FROM t_from INNER JOIN t_join ON ((t_from.foreign_col = t_join.foreign_col) AND (hoge = fuga))
     * ```
     *
     * @used-by innerJoinOn()
     * @used-by leftJoinOn()
     * @used-by rightJoinOn()
     * @used-by autoJoinForeign()
     * @used-by innerJoinForeign()
     * @used-by leftJoinForeign()
     * @used-by rightJoinForeign()
     * @used-by autoJoinForeignOn()
     * @used-by innerJoinForeignOn()
     * @used-by leftJoinForeignOn()
     * @used-by rightJoinForeignOn()
     */
    public function join(string $type, $table, $on, ?string $fkeyname = null, ?string $from = null): static
    {
        return $this->from($table, null, $type, $on, $fkeyname, $from);
    }

    /**
     * 自身が JOIN されたときの ON 条件を設定する
     *
     * ```php
     * # 記事とそれに紐づく最新のコメントを JOIN して取得
     * $db->select([
     *     't_article A' => [
     *         'article_id',
     *         '<t.maxid'     => $db->select([
     *             't_comment' => [
     *                 'article_id',
     *                 'maxid' => 'MAX(comment_id)',
     *             ],
     *         ])->groupBy('article_id')->on('t.article_id = A.article_id'),
     *         '<t_comment C' => [
     *             '!article_id',
     *             ['C.comment_id = t.maxid'],
     *         ],
     *     ],
     * ]);
     * // SELECT A.article_id, t.maxid, C.comment_id, C.comment
     * // FROM t_article A
     * // LEFT JOIN (
     * //   SELECT t_comment.article_id, MAX(comment_id) AS maxid
     * //   FROM t_comment GROUP BY article_id
     * // ) t ON t.article_id = A.article_id
     * // LEFT JOIN t_comment C ON (C.article_id = A.article_id) AND (C.comment_id = t.maxid)
     *
     *```
     */
    public function on($condition): static
    {
        $this->onConditions = arrayize($condition);
        return $this->_dirty();
    }

    /**
     * 引数内では AND、引数間では OR する where（クリア版）
     *
     * 基本は {@link Where::build()} の where 記法と同じ。加えて下記の記法が使用できる。
     *
     * | No | where                         | 説明
     * | --:|:--                            |:--
     * | 30 | `['' => 123]`                 | キーを空文字にすると駆動表の主キーを表す
     * | 33 | `['*.delete_flg' => 1]`       | テーブル部分に `*` を指定すると「あらゆるテーブルのそのカラム」を意味する
     * | 40 | `['table.vcolumn' => "hoge"]` | 仮想カラム（単純なものに限る）も普通のカラムと同じように指定できる
     * | 41 | `['table.vcolumn' => [cond]]` | 仮想カラム（実態が subselect に限る）に配列パラメータを与えると「追加の WHERE で EXISTS」となる。この記法は whereInto と同じく、ユーザ入力を直接与えると SQL インジェクションの危険があるため、**決してユーザ由来の値を渡してはならない**
     *
     * ```php
     * # 引数配列内では AND、引数間では OR される
     * $qb->where(['hoge = 1', 'fuga = ?' => 1], ['piyo' => 1]); // WHERE (hoge = 1 AND fuga = 1) OR (piyo = 1)
     *
     * # No.30（空キーは駆動表の主キーを表す）
     * $qb->column('t_article')->where(['' => 123]);            // WHERE article_id = 123
     * $qb->column('t_article')->where(['' => [123, 456]]);     // WHERE article_id IN (123, 456)
     * $qb->column('t_multi')->where(['' => [123, 456]]);       // WHERE id1 = 123 AND id2 = 456
     * $qb->column('t_multi')->where(['' => [[1, 2], [3, 4]]]); // WHERE (id1 = 1 AND id2 = 2) OR (id1 = 3 AND id2 = 4)
     *
     * # No.33（例えば対象テーブルに delete_flg があり、 delete_flg = 0 を付与したい場合、下記のようにすると全てのテーブルに付与される）
     * $qb->column('table1 t1, table2 t2, table3 t3')->where(['*.delete_flg' => 0]); // WHERE (t1.delete_flg = 0) AND (t2.delete_flg = 0) AND (t3.delete_flg = 0)
     *
     * # No.40（仮想カラムを指定。仮想カラムは「親に紐づく子供の COUNT」とする）
     * $qb->column('t_parent')->where(['t_parent.child_count' => 0]); // WHERE (SELECT COUNT(*) FROM t_child WHERE (t_child.parent_id = t_parent.id)) = 0
     * # No.41（仮想カラムを指定。仮想カラムは「親に紐づく子供の subselect」とする）
     * $qb->column('t_parent')->where(['t_parent.children' => ['delete_flg' => 0]]); // WHERE EXISTS(SELECT * FROM t_child WHERE (t_child.parent_id = t_parent.id AND delete_flg = 0)) = 0
     * ```
     */
    public function where(...$predicates): static
    {
        return $this->resetQueryPart('where')->_buildCondition('where', $predicates, true, 'AND');
    }

    /**
     * NOT つきで引数内では AND、引数間では OR する where（クリア版）
     *
     * NOT が付くこと以外は {@link where()} と同じ。
     *
     * @inheritdoc where()
     */
    public function notWhere(...$predicates): static
    {
        return $this->resetQueryPart('where')->_buildCondition('where', $predicates, false, 'AND');
    }

    /**
     * 現在に対して AND で引数内では AND、引数間では OR する（{@link where()} の追加版）
     *
     * クリアされずに追加されること以外は {@link where()} と同じ。
     *
     * ```php
     * $qb->where('current');
     * $qb->andWhere(['a', 'b'], ['c', 'd']);
     * // results: (current) AND ((a AND b) OR (c AND d))
     * ```
     *
     * @inheritdoc where()
     */
    public function andWhere(...$predicates): static
    {
        return $this->_buildCondition('where', $predicates, true, 'AND');
    }

    /**
     * 現在に対して AND で NOT つきで引数内では AND、引数間では OR する（{@link andWhere()} の NOT 版）
     *
     * NOT が付く以外は {@link andWhere()} と同じ。
     *
     * @inheritdoc where()
     */
    public function andNotWhere(...$predicates): static
    {
        return $this->_buildCondition('where', $predicates, false, 'AND');
    }

    /**
     * 現在に対して OR で引数内では AND、引数間では OR する（{@link where()} の追加版）
     *
     * クリアされずに OR で追加されること以外は {@link where()} と同じ。
     *
     * ```php
     * $qb->where('current');
     * $qb->orWhere(['a', 'b'], ['c', 'd']);
     * // results: (current) OR ((a AND b) OR (c AND d))
     * ```
     *
     * @inheritdoc where()
     */
    public function orWhere(...$predicates): static
    {
        return $this->_buildCondition('where', $predicates, true, 'OR');
    }

    /**
     * 現在に対して OR で NOT つきで引数内では AND、引数間では OR する（{@link orWhere()} のNOT 版）
     *
     * NOT が付く以外は {@link orWhere()} と同じ。
     *
     * @inheritdoc where()
     */
    public function orNotWhere(...$predicates): static
    {
        return $this->_buildCondition('where', $predicates, false, 'OR');
    }

    /**
     * 現在の条件をブロック化する
     *
     * AND/OR は基本的に andWhere/orWhere の「引数内では AND、引数間では OR」という仕様で賄えるが、既にあるブロックはどうしようもない。
     * このメソッドを呼ぶと現在の条件を1つのブロックとみなして括弧が付与されるようになる。
     *
     * ```php
     * $qb->where('a');
     * $qb->orWhere('b');
     * $qb->endWhere(); // これがあることで括弧が付く
     * $qb->andWhere('c');
     * // results: (a OR b) AND c
     *
     * $qb->where('a');
     * $qb->orWhere('b');
     * //$qb->endWhere(); これがないと括弧が付かない
     * $qb->andWhere('c');
     * // results: a OR b AND c
     * ```
     *
     * @inheritdoc where()
     */
    public function endWhere(): static
    {
        if ($this->sqlParts['where']) {
            $params = [];
            foreach ($this->sqlParts['where'] as $where) {
                $where->merge($params);
            }
            $this->sqlParts['where'] = [Expression::new($this->_getConditionClause($this->sqlParts['where']), $params)];
        }
        return $this->_dirty();
    }

    /**
     * WINDOW 句（クリア版）
     *
     * エスケープなどは一切行われないので注意（OVER でリテラルを指定するシチュエーションが少ないため）。
     * ただし Queryable は受け付ける。
     *
     * ```php
     * // 簡易な文字列指定
     * $qb->window('w', 'gid', 'id', 'ROWS UNBOUNDED PRECEDING');
     * // results: WINDOW w AS (PARTITION BY gid ORDER BY id ROWS UNBOUNDED PRECEDING)
     *
     * // 配列など
     * $qb->window('w', ['gid1', 'gid2'], ['id', 'subid' => false, '-prise'], ['ROWS' => [1, 1]]);
     * // results: WINDOW w AS (PARTITION BY gid1, gid2 ORDER BY id, subid DESC, prise DESC ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING)
     * ```
     */
    public function window(string $name, $partitionBy = [], $orderBy = [], $frame = null): static
    {
        return $this->resetQueryPart('window')->addWindow(...func_get_args());
    }

    /**
     * WINDOW 句（追加版）
     *
     * @inheritdoc window()
     */
    public function addWindow(string $name, $partitionBy = [], $orderBy = [], $frame = null): static
    {
        $this->sqlParts['window'][$name] = Expression::window($partitionBy, $orderBy, $frame);
        return $this->_dirty();
    }

    /**
     * [table => array(col1, col2)] のように指定できるように拡張した {@link groupBy()}（クリア版）
     *
     * ```php
     *  # シンプルにカラムを指定
     * $qb->groupBy('id1');          // GROUP BY id1
     * $qb->groupBy('id1', 'id2');   // GROUP BY id1, id2
     *
     * # 配列も指定できる。キーを与えるとテーブルプレフィックスになる
     * $qb->groupBy(['id1', 'id2']);          // GROUP BY id1, id2
     * $qb->groupBy(['T' => ['id1', 'id2']]); // GROUP BY T.id1, T.id2
     * ```
     */
    public function groupBy(...$groupBy): static
    {
        return $this->resetQueryPart('groupBy')->addGroupBy(...$groupBy);
    }

    /**
     * [table => [col1, col2]] のように指定できるように拡張した {@link groupBy()}（追加版）
     *
     * @inheritdoc groupBy()
     */
    public function addGroupBy(...$groupBy): static
    {
        foreach ($groupBy as $groups) {
            foreach (arrayize($groups) as $tbl => $arg) {
                foreach (arrayize($arg) as $col) {
                    if (is_int($tbl)) {
                        $this->sqlParts['groupBy'][] = $col;
                    }
                    else {
                        $this->sqlParts['groupBy'][] = $tbl . '.' . $col;
                    }
                }
            }
        }

        return $this->_dirty();
    }

    /**
     * 引数内では AND、引数間では OR する having（クリア版）
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link where()} と同じ。
     *
     * @inheritdoc where()
     */
    public function having(...$predicates): static
    {
        return $this->resetQueryPart('having')->_buildCondition('having', $predicates, true, 'AND');
    }

    /**
     * NOT つきで引数内では AND、引数間では OR する having（クリア版）
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link notWhere()} と同じ。
     *
     * @inheritdoc having()
     */
    public function notHaving(...$predicates): static
    {
        return $this->resetQueryPart('having')->_buildCondition('having', $predicates, false, 'AND');
    }

    /**
     * 引数内では AND、引数間では OR する（{@link having()} の追加版）
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link andWhere()} と同じ。
     *
     * @inheritdoc having()
     */
    public function andHaving(...$predicates): static
    {
        return $this->_buildCondition('having', $predicates, true, 'AND');
    }

    /**
     * NOT つきで引数内では AND、引数間では OR する（{@link notHaving()} の追加版）
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link notWhere()} と同じ。
     *
     * @inheritdoc having()
     */
    public function andNotHaving(...$predicates): static
    {
        return $this->_buildCondition('having', $predicates, false, 'AND');
    }

    /**
     * 現在に対して OR で引数内では AND、引数間では OR する（{@link having()} の追加版）
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link orWhere()} と同じ。
     *
     * @inheritdoc having()
     */
    public function orHaving(...$predicates): static
    {
        return $this->_buildCondition('having', $predicates, true, 'OR');
    }

    /**
     * 現在に対して OR で NOT つきで引数内では AND、引数間では OR する（{@link having()} の追加版）
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link orNotWhere()} と同じ。
     *
     * @inheritdoc having()
     */
    public function orNotHaving(...$predicates): static
    {
        return $this->_buildCondition('having', $predicates, false, 'OR');
    }

    /**
     * 現在の条件をブロック化する
     *
     * WHERE ではなく HAVING である点を除いて引数体系などは {@link endWhere()} と同じ。
     *
     * @inheritdoc where()
     */
    public function endHaving(): static
    {
        if ($this->sqlParts['having']) {
            $params = [];
            foreach ($this->sqlParts['having'] as $where) {
                $where->merge($params);
            }
            $this->sqlParts['having'] = [Expression::new($this->_getConditionClause($this->sqlParts['having']), $params)];
        }
        return $this->_dirty();
    }

    /**
     * [col => ASC] のように指定できるように拡張した orderBy（クリア版）
     *
     * ORDER BY 句を設定する。
     * 渡し方が引数だったり配列だったりするのでややこしく見えるが、原則として {カラム名, 順序} のタプルを渡す。
     * 「カラム名」に特記事項はない。「順序」は 未指定, 'ASC', true などが昇順を表し、 'DESC', false などが降順を表す。
     * 第3引数で null の場合の挙動を指定できる。
     *
     * ```php
     * # シンプルなカラム ORD
     * $qb->orderBy('col');         // ORDER BY col ASC
     * $qb->orderBy('col', true);   // ORDER BY col ASC
     * $qb->orderBy('col', 'ASC');  // ORDER BY col ASC
     * $qb->orderBy('col', false);  // ORDER BY col DESC
     * $qb->orderBy('col', 'DESC'); // ORDER BY col DESC
     *
     * # [col => ORD] 形式
     * $qb->orderBy(['colA' => 'ASC', 'colB' => false]);  // ORDER BY colA ASC, colB DESC
     * $qb->orderBy(['colA' => true, 'colB' => 'DESC']);  // ORDER BY colA ASC, colB DESC
     * $qb->orderBy(['colA', 'colB' => false]);           // ORDER BY colA ASC, colB DESC
     *
     * # [+col, -col] 形式
     * $qb->orderBy('+colA');            // ORDER BY colA ASC
     * $qb->orderBy(['-colA', '+colB']); // ORDER BY colA DESC, colB ASC
     *
     * # [col, col, col], ORD 形式
     * $qb->orderBy(['colA', 'colB', 'colC'], false);  // ORDER BY colA DESC, colB DESC, colC DESC
     * ```
     */
    public function orderBy($sort, $order = null, ?string $nullsOrder = null): static
    {
        return $this->resetQueryPart('orderBy')->addOrderBy(...func_get_args());
    }

    /**
     * [col => ASC] のように指定できるように拡張した {@link orderBy()}（追加版）
     *
     * @inheritdoc orderBy()
     */
    public function addOrderBy($sort, $order = null, ?string $nullsOrder = null): static
    {
        $add = function ($sort, $order, $nullsOrder) {
            $nullsOrder = $nullsOrder ?? $this->getUnsafeOption('nullsOrder');
            if ($nullsOrder !== null) {
                $expr = $sort;
                $params = [];
                if ($expr instanceof Queryable) {
                    $expr = $expr->merge($params);
                }
                $this->sqlParts['orderBy'][] = match (strtolower($nullsOrder)) {
                    default => throw new \InvalidArgumentException("$nullsOrder is not supported."),
                    'min'   => [Expression::new("CASE WHEN $expr IS NULL THEN 0 ELSE 1 END", $params), $order],
                    'max'   => [Expression::new("CASE WHEN $expr IS NULL THEN 1 ELSE 0 END", $params), $order],
                    'first' => [Expression::new("CASE WHEN $expr IS NULL THEN 1 ELSE 0 END", $params), false],
                    'last'  => [Expression::new("CASE WHEN $expr IS NULL THEN 1 ELSE 0 END", $params), true],
                };
            }
            $this->sqlParts['orderBy'][] = [$sort, $order];
        };

        // bool は特別扱いで主キーとする
        if (is_bool($sort)) {
            return $this->addOrderBy(OrderBy::primary(), $sort, $nullsOrder);
        }

        if ($sort instanceof OrderBy) {
            if (!$sort->isRewritable()) {
                return $this->addOrderBy($sort($this), $order ?? $sort->asc, $nullsOrder ?? $sort->nullsOrder);
            }

            // 超特殊な処理（build 時に遅延構築する）
            $this->sqlParts['orderBy'][] = $sort;
        }
        elseif (is_array($sort)) {
            foreach ($sort as $col => $ord) {
                if (is_int($col) && is_array($ord)) {
                    $this->addOrderBy($ord[0], $ord[1] ?? $order, $nullsOrder);
                }
                elseif (is_int($col)) {
                    $this->addOrderBy($ord, $order, $nullsOrder);
                }
                else {
                    $this->addOrderBy($col, $ord, $nullsOrder);
                }
            }
        }
        // テーブル記法
        elseif ($order === null && is_string($sort) && str_exists($sort, ['+', '-'])) {
            $parts = preg_split('#([+-])#', $sort, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            foreach (array_chunk($parts, 2) as $part) {
                $this->addOrderBy($part[1], $part[0] === '+', $nullsOrder);
            }
        }
        else {
            if ($sort instanceof Queryable && $order === null) {
                $add($sort, null, $nullsOrder);
            }
            else {
                if (is_array($order)) {
                    $nullsOrder = $order[1] ?? null;
                    $order = $order[0] ?? null;
                }
                if (is_bool($order)) {
                    $order = $order ? 'ASC' : 'DESC';
                }
                $add($sort, strtoupper($order ?? '') !== 'DESC', $nullsOrder);
            }
        }

        $this->_dirty();

        // id asc,id desc のような冗長な ORDER BY を許さない RDBMS がいる
        return $this->database->getCompatiblePlatform()->supportsRedundantOrderBy() ? $this : $this->detectAutoOrder(false);
    }

    /**
     * (count, offset) or [offset => length] or [offset, length] のように指定できるようにする
     *
     * LIMIT OFFSET 句を設定する。
     *
     * ```php
     * # シンプルにスカラーで指定
     * $qb->limit(10);     // LIMIT 10
     * $qb->limit(10, 20); // LIMIT 10 OFFSET 20
     *
     * # 配列で指定（単純引数でも連想配列でも指定できる）。意味合いがスカラー指定と逆になっているので注意
     * $qb->limit([20, 10]);   // LIMIT 10 OFFSET 20
     * $qb->limit([20 => 10]); // LIMIT 10 OFFSET 20
     * ```
     */
    public function limit(int|array|null $count, ?int $offset = null): static
    {
        if (is_array($count)) {
            $c = count($count);
            if ($c === 1) {
                [$offset, $count] = first_keyvalue($count);
            }
            elseif ($c === 2) {
                $offset = array_shift($count);
                $count = array_shift($count);
            }
            else {
                throw new \InvalidArgumentException('limit array length must be 1 or 2.');
            }
        }

        $this->sqlParts['offset'] = $offset === null ? null : (int) $offset;
        $this->sqlParts['limit'] = $count === null ? null : (int) $count;

        return $this->_dirty();
    }

    /**
     * ページ単位として LIMIT OFFSET する
     *
     * できることは {@link limit()} と同じ。指定の仕方が異なるだけ。
     * limit() は LIMIT, OFFSET をダイレクトに指定するが、こちらは一定の単位（ページ）で設定する。
     * 「LIMIT ありきで、それを元に OFFSET する」とも言える。
     *
     * ※ {@link Paginator} は全く関係ない
     *
     * ```php
     * # 10 件ごと 5 ページ目を設定
     * $qb->page(5, 10);        // LIMIT 10 OFFSET 50
     *
     * # あらかじめ limit 設定しておけば第2引数は省略できる
     * $qb->limit(10)->page(5); // LIMIT 10 OFFSET 50
     * ```
     */
    public function page(int $page, ?int $limit = null): static
    {
        if ($limit === null) {
            $limit = $this->sqlParts['limit'];
            if ($limit === null) {
                return $this;
            }
        }
        return $this->limit($limit, $page * $limit);
    }

    /**
     * クエリに対してコメントを付ける
     *
     * コメントはクエリ冒頭に改行付きで付与される。複数設定した場合はその分付与される。
     * コメント内に : や ? などのメタ的な文字列を含んではならない。
     *
     * ```php
     * # 単一文字列は追加される
     * echo $qb->column('test')->comment('hoge')->comment('fuga');
     * // -- hoge
     * // -- fuga
     * // SELECT * FROM test
     *
     * # 配列は置換される
     * echo $qb->column('test')->comment('foo')->comment(['hoge', 'fuga']);
     * // -- hoge
     * // -- fuga
     * // SELECT * FROM test
     *
     * # 配列は置換されるので削除も行える
     * echo $qb->column('test')->comment([]);
     * // SELECT * FROM test
     * ```
     */
    public function comment(string|array $comment): static
    {
        if (is_array($comment)) {
            $this->sqlParts['comment'] = $comment;
        }
        else {
            $this->sqlParts['comment'][] = $comment;
        }
        return $this->_dirty();
    }

    /**
     * UNION する
     *
     * UNION を行うと「UNION テーブルから自身のクエリビルダを利用して SELECT する」という動作になる。
     * 言い換えれば「自身の各句は UNION テーブルのためのものになる」となる。
     *
     * ```php
     * $qb = $db->select([
     *     // 後の union のための column 指定
     *     '' => ['title', 'content']
     * ], [
     *     // 後の union のための where 指定
     *     'status' => 'active',
     * ]);
     * // t_article と t_comment を union する（これが駆動表となる）
     * $qb->union($db->select('t_article'));
     * $qb->union($db->select('t_comment'));
     * echo $qb;
     * // SELECT title, content FROM
     * // (
     * //   SELECT t_article.* FROM t_article
     * //   UNION
     * //   SELECT t_comment.* FROM t_comment
     * // ) __dbml_union_table
     * // WHERE status = 'active'
     * ```
     */
    public function union($query): static
    {
        // 隠し引数 $isall
        $isall = func_num_args() === 2 ? func_get_arg(1) : false;

        foreach (arrayize($query) as $subq) {
            $this->sqlParts['union'][] = [$this->sqlParts['union'] ? ($isall ? 'UNION ALL' : 'UNION') : '', $subq];
        }

        return $this->_dirty();
    }

    /**
     * UNION ALL する
     *
     * ALL で UNION される以外は {@link union()} と全く同じ。
     */
    public function unionAll($query): static
    {
        return $this->union($query, true);
    }

    /**
     * '!' 付き条件で全てがフィルタされたかを返す
     *
     * @ignore
     */
    public function isEmptyCondition(): bool
    {
        // $this->emptyCondition は単純な bool ではないので !! する
        return !!$this->emptyCondition;
    }

    /**
     * EXISTS クエリ化
     *
     * ```php
     * # status: 'active' の EXISTS を発行する
     * $qb->column('t_article')->where(['status' => 'active'])->existize();
     * // SELECT EXISTS(SELECT * FROM t_article WHERE status = 'active')
     * ```
     */
    public function existize(bool $affirmation = true, bool $for_update = false): static
    {
        $that = clone $this;
        $that->resetQueryPart('select');
        $that->resetQueryPart('orderBy');
        $that->resetQueryPart('offset');
        $that->resetQueryPart('limit');

        // EXISTS だけなのでこの辺は全部不要
        $that->subbuilders = [];
        $that->callbacks = [];
        $that->caster = null;

        if ($affirmation) {
            $that->exists();
        }
        else {
            $that->notExists();
        }

        if ($for_update) {
            $that->lockForUpdate();
        }

        $exister = SelectBuilder::new($that->database);
        $exister->select($that->database->getCompatiblePlatform()->convertSelectExistsQuery($that));
        $exister->detectAutoOrder(false);
        return $exister;
    }

    /**
     * COUNT(\*) クエリ化（厳密に言えば limit なしの COUNT(\*) 化）
     *
     * ```php
     * # status: 'active' の COUNT(*) を発行する
     * $qb->column('t_article')->where(['status' => 'active'])->countize();
     * // SELECT COUNT(*) AS __dbml_auto_cnt FROM t_article WHERE status = 'active'
     * ```
     */
    public function countize(string $column = '*'): static
    {
        $that = clone $this;
        $that->detectAutoOrder(false);
        $that->resetQueryPart('orderBy');
        $that->resetQueryPart('offset');
        $that->resetQueryPart('limit');

        // COUNT だけなのでこの辺は全部不要
        $that->subbuilders = [];
        $that->callbacks = [];
        $that->caster = null;

        // groupBy,having がある時は集約クエリなのでラップしたクエリで count する
        if ($that->sqlParts['groupBy'] || $that->sqlParts['having']) {
            // その際 select 句は不要だが、select 句を group,having に指定できる RDBMS もあるのでそれは残さなければならない
            $conditions = array_map('strval', array_merge($that->sqlParts['groupBy'], $that->sqlParts['having']));
            foreach ($that->sqlParts['select'] as $n => $select) {
                if ($select instanceof Select) {
                    // group はともかく、having からカラム名を抽出するのは容易ではないので暫定措置（まぁ大抵は先頭だろう）
                    // ここの処理で SQL エラーが出るようなら呼び元で何とかするしかない
                    if (preg_grep('#^' . preg_quote($select->getAlias()) . '([^_0-9a-z]|$)#', $conditions)) {
                        continue;
                    }
                }
                unset($that->sqlParts['select'][$n]);
            }
            if (!$that->sqlParts['select']) {
                $that->addSelect('1');
            }
            $counter = SelectBuilder::new($that->database);
            $counter->select(new Select(self::COUNT_ALIAS, "COUNT($column)"));
            $counter->from(['__dbml_auto_table' => $that]);
        }
        else {
            $that->resetQueryPart('select');
            $counter = $that->select(new Select(self::COUNT_ALIAS, "COUNT($column)"));
        }

        return $counter;
    }

    /**
     * new Paginator へのプロキシメソッド
     *
     * 引数が与えられている場合は {@link Paginator::paginate()} も同時に行う。
     *
     * @inheritdoc Paginator::paginate()
     */
    public function paginate(?int $currentpage = null, ?int $countperpage = null): Paginator
    {
        $p = new Paginator($this);
        if (func_num_args()) {
            $p->paginate(...func_get_args());
        }
        return $p;
    }

    /**
     * new Sequencer へのプロキシメソッド
     *
     * 引数が与えられている場合は {@link Sequencer::sequence()} も同時に行う。
     *
     * @inheritdoc Sequencer::sequence()
     */
    public function sequence(?array $condition = null, ?int $count = null, ?bool $orderbyasc = true): Sequencer
    {
        $p = new Sequencer($this);
        if (func_num_args()) {
            $p->sequence(...func_get_args());
        }
        return $p;
    }

    /**
     * 分割して sequence してレコードジェネレータを返す
     *
     * 例えば 150 件のレコードに対して chunk: 10 するとクエリを 15 回に分けて実行する。
     * そのそれぞれのクエリは別々であり、php 側 にも db 側にもバッファは作られないためメモリ効率が非常に良い（そのかわりそこまで高速ではない）。
     *
     * 一般的な実装と違い、下記の制限がある。
     *
     * - 数値主キーか、数値的な NOT NULL ユニークキーを持っている必要がある
     * - あらかじめ設定していても ORDER BY, LIMIT は無視される
     *
     * そのかわり chunk 内でレコードの更新を行ってもズレが発生しないようになっている。
     *
     * ```php
     * // 100 件ずつループする
     * foreach ($qb->where(['status' => 'active'])->chunk(100) as $row) {
     *     // do something
     *     var_dump($row['id']);
     * }
     * // SELECT * FROM t_table WHERE (status = "active") AND (id > 0) ORDER BY id ASC LIMIT 100
     * // SELECT * FROM t_table WHERE (status = "active") AND (id > 100) ORDER BY id ASC LIMIT 100
     * // SELECT * FROM t_table WHERE (status = "active") AND (id > 200) ORDER BY id ASC LIMIT 100
     * // ・・・のようなクエリが順次投げられる（Generator で返されるので分割されていることは意識しなくて良い）
     * ```
     */
    public function chunk(int $chunk, ?string $column = null, $fixrange = false): \Generator
    {
        $from = first_value($this->getFromPart())['table'] ?? throw new \UnexpectedValueException('from table is not set.');
        $column = $column ?: strval($this->database->getSchema()->getTableAutoIncrement($from)?->getName() ?: throw new \UnexpectedValueException('not autoincrement column.'));
        $orderasc = $column[0] !== '-';
        $column = ltrim($column, '-+');

        if ($fixrange) {
            $breaker = (clone $this)->select($column)->aggregate($orderasc ? 'max' : 'min')->value();
        }

        $sequencer = new Sequencer($this);
        $items = [];
        do {
            $n = end($items)[$column] ?? null;
            $sequencer->sequence([$column => $n], $chunk, $orderasc);
            $items = $sequencer->getItems();
            foreach ($items as $item) {
                if ($fixrange) {
                    if (($orderasc && $item[$column] > $breaker) || (!$orderasc && $item[$column] < $breaker)) {
                        break 2;
                    }
                }
                yield $item;
            }
        } while ($items);
    }

    /**
     * 特定レコードの前後のレコードを返す
     *
     * 結果配列は特定レコードとの距離がキーになり、かつ昇順でソートされる。
     *
     * ```php
     * # id:5 の前後のレコードを1行ずつ返す
     * $qb->neighbor(['id' => 5]);
     * // results:
     * [
     *     -1 => ['id' => 4],
     *     1  => ['id' => 6],
     * ];
     *
     * # id:5 の前後のレコードを2行ずつ返す
     * $qb->neighbor(['id' => 5], 2);
     * // results:
     * [
     *     -2 => ['id' => 3],
     *     -1 => ['id' => 4],
     *     1  => ['id' => 6],
     *     2  => ['id' => 7],
     * ];
     *
     * # 前後が無い場合、無い方は含まれない
     * $qb->neighbor(['id' => 99999], 2);
     * // results:
     * [
     *     // 99999 より大きいレコードが無いとすると負数キー（前後の前）のみ返ってくる
     *     -2 => ['id' => 99997],
     *     -1 => ['id' => 99998],
     * ];
     * ```
     */
    public function neighbor(array $predicates, int $limit = 1): array
    {
        $pcount = count($predicates);
        if (!$pcount) {
            throw new \InvalidArgumentException('$predicates is empty.');
        }

        $cols = array_keys($predicates);
        $vals = array_values($predicates);

        $colss = implode(',', $cols);
        $valss = '?';
        if ($pcount >= 2) {
            $colss = "($colss)";
            $valss = "($valss)";
        }

        // ORDER BY の順番が保証されているという情報はないし、取得列に $col が含まれている保証もないので UNION ORDER BY は使えない。2回に分けて取得する
        $baselect = (clone $this)->limit($limit);
        $prevs = (clone $baselect)->where(["$colss < $valss" => $vals])->orderBy($cols, false)->array();
        $nexts = (clone $baselect)->where(["$colss > $valss" => $vals])->orderBy($cols, true)->array();

        $result = [];

        foreach (array_reverse($prevs, true) as $n => $row) {
            $result[-($n + 1)] = $row;
        }
        foreach ($nexts as $n => $row) {
            $result[+($n + 1)] = $row;
        }

        return $result;
    }

    /**
     * 行レベルの変換クロージャを返す
     *
     * @ignore 内部用
     */
    public function getRowConverter(): \Closure
    {
        $caster = $this->getCaster();
        return function ($parent_row) use ($caster) {
            foreach ($this->callbacks as $name => [$callback, $args]) {
                if ($args) {
                    $args2 = [];
                    foreach ($args as $arg) {
                        $args2[] = $arg === null ? $parent_row : $parent_row[$arg];
                    }
                    $parent_row[$name] = $callback(...$args2);
                }
            }
            foreach ($parent_row as $col => $val) {
                if (strpos($col, Database::AUTO_DEPEND_KEY) === 0) {
                    unset($parent_row[$col]);
                }
            }
            if ($caster) {
                $parent_row = $caster($parent_row);
            }
            return $parent_row;
        };
    }

    /**
     * fectch 後の処理を登録
     *
     * 呼び出されるタイミングには下記の2種類がある。
     *
     * - before: ほぼ「SELECT クエリの直後」であり、サブビルダやクロージャの解決前
     * - after: ほぼ「return 直前」であり、サブビルダやクロージャの解決後
     *
     * 引数は大本の行配列で、 $this は SelectBuilder で bind される。
     */
    public function before(?\Closure $callback = null): static
    {
        $this->applyments['before'] = $callback;
        return $this;
    }

    /**
     * fectch 後の処理を登録
     *
     * タイミング以外の仕様は {@link before()} と同じ。
     *
     * @inheritdoc before()
     */
    public function after(?\Closure $callback = null): static
    {
        $this->applyments['after'] = $callback;
        return $this;
    }

    /**
     * 行フェッチ後に SelectBuilder 特有の処理を行う
     *
     * ほぼ内部処理で明示的に呼ぶことはない。
     */
    public function postselect(array $parents, bool $continuity = false): array
    {
        assert(!$continuity || ($continuity && $this->applyments['before'] === null), 'yield not support before apply');
        assert(!$continuity || ($continuity && $this->applyments['after'] === null), 'yield not support after apply');

        $parents = $this->applyments['before'] ? $this->applyments['before']->call($this, $parents) : $parents;

        if ($parents) {
            // subselect
            if ($this->subbuilders) {
                // 親行がスカラーなのは何かがおかしい
                assert(!is_primitive(first_value($parents)));

                // subselects 分ループ（多くても数個）
                foreach ($this->subbuilders as $column => $subselect) {
                    // 連続コールされる場合は無駄なので clone もしないし prepare を使用する
                    if ($continuity) {
                        if ($subselect->lazyMode === self::LAZY_MODE_EAGER) {
                            $subselect->lazyMode = self::LAZY_MODE_FETCH;
                        }
                    }
                    else {
                        $subselect = clone $subselect;
                    }

                    // 親のロックモードを受け継ぐ
                    if ($this->lockMode !== LockMode::NONE && $subselect->lockMode === LockMode::NONE && $subselect->getPropagateLockMode()) {
                        $subselect->lockMode = $this->lockMode;
                        $subselect->lockOption = $this->lockOption;
                    }

                    // 親のフェッチメソッドを受け継ぐ
                    if ($subselect->lazyMethod === null) {
                        $subselect->lazyMethod = $this->lazyMethod;
                        if (Database::METHODS[$subselect->lazyMethod]['keyable'] === null) {
                            $subselect->lazyMethod = Database::METHOD_ASSOC;
                        }
                    }

                    $parents = $subselect->_subquery($parents, $column);
                }
            }

            // binding
            foreach ($parents as $n => $parent_row) {
                $row_class = null;
                foreach ($this->callbacks as $name => [$callback, $args]) {
                    if (!$args) {
                        $parents[$n][$name] = $callback($parents[$n]);
                    }
                    if (isset($parents[$n][$name]) && $parents[$n][$name] instanceof \Closure) {
                        // 親行がスカラーなのは何かがおかしい
                        assert(!is_primitive(first_value($parents)));

                        if (is_bindable_closure($parents[$n][$name])) {
                            if ($row_class === null) {
                                if ($parents[$n] instanceof Entityable) {
                                    $row_class = $parents[$n];
                                }
                                else {
                                    $row_class = new \ArrayObject($parents[$n], \ArrayObject::ARRAY_AS_PROPS);
                                }
                            }
                            $parents[$n][$name] = $parents[$n][$name]->bindTo($row_class);
                        }
                    }
                }
            }
        }

        $parents = $this->applyments['after'] ? $this->applyments['after']->call($this, $parents) : $parents;

        return $parents;
    }

    /**
     * 特定文字列でラップする
     *
     * 典型的には `EXISTS` だが、それ以外の「何らかの文字列で囲みたい」場合に汎用的に使用できる。
     * （もっとも、 `EXISTS` は専用メソッドが有るので使用頻度はそこまで高くない）。
     *
     * ラップ文字は追加で積み重なっていくが、第3引数（$name）を指定すると、種別を指定できて、後から上書きすることができる。
     *
     * ```php
     * $qb->column('t_article')->wrap('A', 'B');
     * // A (SELECT t_article.* FROM t_article) B
     *
     * # 種別なしでラップ（積み重ね）
     * $qb->column('t_article')->wrap('A', 'B')->wrap('C', 'D');
     * // C (A (SELECT t_article.* FROM t_article) B) D
     *
     * # 種別を hoge でラップ（上書き）
     * $qb->column('t_article')->wrap('A', 'B', 'hoge')->wrap('C', 'D', 'hoge');
     * // C (SELECT t_article.* FROM t_article) D
     * ```
     */
    public function wrap(string $keyword1, string $keyword2 = '', ?string $name = null): static
    {
        array_set($this->wrappers, [$keyword1, $keyword2], $name);
        return $this->_dirty();
    }

    /**
     * EXISTS でラップして返す
     *
     * {@link wrap()} の特化メソッド。
     */
    public function exists(): static
    {
        $this->sqlParts['select'] = ['*'];
        return $this->wrap('EXISTS', '', 'EXISTS');
    }

    /**
     * NOT EXISTS でラップして返す
     *
     * {@link wrap()} の特化メソッド。
     */
    public function notExists(): static
    {
        $this->sqlParts['select'] = ['*'];
        return $this->wrap('NOT EXISTS', '', 'EXISTS');
    }

    /**
     * 集約関数化する
     *
     * {@link Database::aggregate()} のためのヘルパーメソッドで、明示的には呼ばれないし呼ばない。
     */
    public function aggregate(string|array $aggregations, int $select_limit = PHP_INT_MAX): static
    {
        // 集約クエリで主キー順に意味は無い
        $this->detectAutoOrder(false);

        // $aggregations が連想配列の場合は自由モード（かなりの制約がなくなるモード）
        if (is_array($aggregations) && is_hasharray($aggregations)) {
            // @todo ビルダーの数値判定をごまかすためにスペースを付与している（キモいのでなんとかしたい）
            $columns = $this->sqlParts['groupBy'];
            foreach ($aggregations as $cond => $vals) {
                if (strpos($cond, '?') !== false) {
                    foreach (arrayize($vals) as $k => $v) {
                        $v = arrayize($v);
                        $cname = is_int($k) ? implode($this->getPrimarySeparator(), $v) : $k;
                        $columns[ctype_digit("$cname") ? " $cname" : $cname] = $this->database->raw($cond, $v);
                    }
                }
                else {
                    if (is_array($vals)) {
                        [$k, $v] = first_keyvalue($vals);
                        $vals = $this->database->raw($k, $v);
                    }
                    $columns[is_int($cond) ? " $cond" : $cond] = $vals;
                }
            }
            return $this->select($columns)->_dirty();
        }

        $platform = $this->database->getCompatiblePlatform();
        $delimiter = $this->getAggregationDelimiter();

        // median は統一できない程度に固有処理
        if (is_string($aggregations) && strtolower($aggregations) === 'median') {
            $rawcolumns = [];
            foreach ($this->sqlParts['select'] as $column) {
                $c = array_pad(explode('.', $column, 2), -2, '')[1];
                $rawcolumns[$column . $delimiter . $aggregations] = $platform->getAvgExpression($c);
            }
            $rawgroupBys = [];
            foreach ($this->sqlParts['groupBy'] as $column) {
                $rawgroupBys[] = array_pad(explode('.', $column, 2), -2, '')[1];
            }

            $selects = implode(',', $this->sqlParts['select']) ?: "'1'";
            $groupBys = implode(',', $this->sqlParts['groupBy']) ?: "'1'";
            $this->addSelect([
                ...$this->sqlParts['groupBy'],
                '_number' => new Expression("ROW_NUMBER() OVER (PARTITION BY $groupBys ORDER BY $selects)"),
                '_count'  => new Expression("COUNT(*) OVER (PARTITION BY $groupBys)"),
            ]);
            $this->resetQueryPart('groupBy');

            $select = $this->database->select([]);
            $select->from(['__dbml_auto_table' => $this]);
            $select->addSelect(array_merge($rawgroupBys, $rawcolumns));
            $select->where('_number BETWEEN _count * 1.0 / 2 AND _count * 1.0 / 2 + 1');
            $select->groupBy($rawgroupBys);
            return $select;
        }

        // json は統一できない程度に固有処理
        if (is_string($aggregations) && strtolower($aggregations) === 'jsonagg') {
            $labels = [];
            $keyvalues = [];
            foreach ($this->sqlParts['select'] as $alias => $column) {
                if (is_int($alias)) {
                    if ($column instanceof Select) {
                        $alias = $column->getAlias();
                        $column = $column->getActual();
                    }
                    else {
                        $alias = array_pad(explode('.', $column, 2), -2, '')[1];
                    }
                }

                $labels[] = "$alias:$column";
                $keyvalues[$alias] = $column;
            }
            $select = new Select(implode(',', $labels) . $delimiter . $aggregations, $platform->getJsonAggExpression($keyvalues));
            $this->sqlParts['select'] = array_merge($this->sqlParts['groupBy'], [$select]);
            return $this->_dirty();
        }

        // カラムとタプルのセットを取得しておく
        $fields = $this->sqlParts['select'] ?: ['*'];
        $tuples = array_each(arrayize($aggregations), function (&$carry, $aggregation) {
            $carry[] = [
                'method'    => "get{$aggregation}Expression",
                'aggregate' => $aggregation,
            ];
        }, []);

        // どちらかの最大数に合わせるように埋める
        $fields_count = count($fields);
        $tuples_count = count($tuples);
        if ($tuples_count === 0) {
            throw new \InvalidArgumentException('$aggregations is empty.');
        }
        if (max($fields_count, $tuples_count) > $select_limit) {
            throw new \InvalidArgumentException("aggregate column's length is over $select_limit.");
        }
        $fields = array_pad($fields, $tuples_count, end($fields));
        $tuples = array_pad($tuples, $fields_count, end($tuples));

        foreach ($fields as $n => $field) {
            $method = $tuples[$n]['method'];
            $aggregate = $tuples[$n]['aggregate'];
            $field = strpos($field, '*') === false ? $field : '*'; // for example: COUNT(TableName.*) -> COUNT(*)
            $fields[$n] = new Select($field . $delimiter . $aggregate, $platform->$method($field));
        }
        $this->sqlParts['select'] = array_merge($this->sqlParts['groupBy'], $fields);

        return $this->_dirty();
    }

    /**
     * ヒント句を追加する
     *
     * 第2引数で紐づくテーブルを指定できるが、省略すると（その時点の）駆動表と紐づく。
     *
     * RDBMS の方言は吸収しないのでダイレクトに与える必要がある。
     * （一応 {@link \ryunosuke\dbml\Metadata\CompatiblePlatform::getIndexHintSQL()} に実装がある）。
     *
     * ```php
     * // SELECT * FROM tablename FORCE INDEX (PRIMARY)
     * $qb->column('tablename')->hint('FORCE INDEX (PRIMARY)');
     * ```
     */
    public function hint(?string $hint, ?string $talias = null): static
    {
        if ("$hint" === "") {
            return $this;
        }
        if ($talias === null) {
            $froms = $this->getFromPart();
            $talias = reset($froms)['alias'];
        }
        $this->sqlParts['hint'][$talias] = $hint;
        return $this->_dirty();
    }

    /**
     * 共有ロック構文を付与する
     *
     * $lockoption で付随するロックオプション（SKIP LOCKED とか）を指定できる（共有ロックで指定することはあまりないと思うけど）。
     * SKIP LOCKED などは有用だが方言がかなり激しく正規化が難しいので文字列で指定する。
     * ただし、 SqlServer は未対応（Doctrine 側が対応していない）。
     *
     * ```php
     * # 共有ロック（mysql）
     * $qb->column('t_article')->lockInShare();
     * // SELECT t_article.* FROM t_article LOCK IN SHARE MODE
     * ```
     */
    public function lockInShare(string $lockoption = ''): static
    {
        $this->lockMode = LockMode::PESSIMISTIC_READ;
        $this->lockOption = $lockoption;
        return $this->_dirty();
    }

    /**
     * 排他ロック構文を付与する
     *
     * $lockoption で付随するロックオプション（SKIP LOCKED とか）を指定できる。
     * SKIP LOCKED などは有用だが方言がかなり激しく正規化が難しいので文字列で指定する。
     * ただし、 SqlServer は未対応（Doctrine 側が対応していない）。
     *
     * ```php
     * # 排他ロック（mysql）
     * $qb->column('t_article')->lockForUpdate();
     * // SELECT t_article.* FROM t_article FOR UPDATE
     *
     * # オプション付き排他ロック（postgres や mysql 8.0）
     * $qb->column('t_article')->lockForUpdate('SKIP LOCKED');
     * // SELECT t_article.* FROM t_article FOR UPDATE SKIP LOCKED
     * ```
     */
    public function lockForUpdate(string $lockoption = ''): static
    {
        $this->lockMode = LockMode::PESSIMISTIC_WRITE;
        $this->lockOption = $lockoption;
        return $this->_dirty();
    }

    /**
     * ロックを解除する
     *
     * {@link lockInShare()} や {@link lockForUpdate()} で追加したロック構文を解除する。
     *
     * ```php
     * $qb->column('t_article');
     *
     * # 排他ロック
     * $qb->lockForUpdate();
     * // SELECT t_article.* FROM t_article FOR UPDATE
     *
     * # ロック解除
     * $qb->unlock();
     * // SELECT t_article.* FROM t_article
     * ```
     */
    public function unlock(): static
    {
        $this->lockMode = LockMode::NONE;
        $this->lockOption = '';
        return $this->_dirty();
    }

    /**
     * 自動 OrderBy の有効無効を設定する
     */
    public function detectAutoOrder(?bool $use): static
    {
        // ビルド中に呼ばれることがあるが、最終的に Database の fetch でも呼ばれるため上書きされてしまうので1度きりの設定とする
        if ($use !== null && $this->enableAutoOrder !== null) {
            return $this;
        }

        $current = $this->enableAutoOrder;
        $this->enableAutoOrder = $use;

        if ($current !== $this->enableAutoOrder) {
            $this->_dirty();
        }
        return $this;
    }

    /**
     * サブクエリビルダを返す
     *
     * ```php
     * $select = $db->select('t_parent/t_child');
     * # t_child のサブクエリビルダを返す
     * $select->getSubbuilder('t_child');
     * # 全サブクエリビルダを返す
     * $select->getSubbuilder();
     * ```
     *
     * このメソッドでサブビルダを取得すると、テーブル記法や簡易記法で記述した子供ビルダに対して各句を指定することができる。
     *
     * ```php
     * $qb->column([
     *     't_table' => [
     *         't_child AS children' => [],
     *     ],
     * ]);
     * $qb->getSubbuilder('children')->where(['delete_time' => 0])->orderBy(['update_time' => 'DESC'])->limit(5);
     * ```
     *
     * @return static|static[] サブクエリビルダ
     */
    public function getSubbuilder(?string $name = null)
    {
        if ($name === null) {
            return $this->subbuilders;
        }
        if (!isset($this->subbuilders[$name])) {
            throw new \InvalidArgumentException("subbuilder '$name' is not defined.");
        }
        return $this->subbuilders[$name];
    }

    /**
     * FROM 句(from,join)を返す
     *
     * {@link getQueryPart()} でも得られるが、 FROM は JOIN も兼ねており、ややこしい構造になっているのでそれを補正しつつシンプルな構造で返す。
     *
     * ```php
     * $qb->column('t_article A + t_comment C');
     * $qb->getFromPart();
     * // result:
     * [
     *     'A' => [
     *         'from'      => null,
     *         'table'     => 't_article',
     *         'alias'     => 'A',
     *         'fkeyname'  => null,
     *         'condition' => [],
     *     ],
     *     'C' => [
     *         'from'      => 'A',
     *         'table'     => 't_comment',
     *         'alias'     => 'C',
     *         'fkeyname'  => null,
     *         'condition' => null,
     *         'type'      => 'INNER',
     *     ],
     * ];
     * ```
     */
    public function getFromPart(): array
    {
        $result = [];
        foreach ($this->sqlParts['from'] as $from) {
            $alias = $from['alias'] ?: $from['table'];
            $result[$alias] = [
                'from'      => null,
                'table'     => $from['table'],
                'alias'     => $alias,
                'fkeyname'  => $from['fkeyname'],
                'condition' => $from['condition'],
            ];
        }
        foreach ($this->sqlParts['join'] as $fromkey => $joins) {
            foreach ($joins as $join) {
                $alias = $join['alias'] ?: $join['table'];
                $result[$alias] = [
                    'from'      => $fromkey,
                    'table'     => $join['table'],
                    'alias'     => $alias,
                    'fkeyname'  => $join['fkeyname'],
                    'condition' => $join['condition'],
                    'type'      => $join['type'],
                ];
            }
        }
        return $result;
    }

    /**
     * SQL の各句を返す
     */
    public function getQueryPart(?string $queryPartName): mixed
    {
        if ($queryPartName === null) {
            return $this->sqlParts;
        }
        return $this->sqlParts[$queryPartName];
    }

    /**
     * SQL の各句をリセットする
     *
     * 引数でリセットする句を指定する。
     * 配列を与えると複数の句をリセットする。
     * null を与えると全句をリセットする。
     *
     * ```php
     * $qb = $db->select('t_article', ['article_id' => 1], ['article_id' => 'DESC'], 5);
     *
     * # where をリセット
     * $qb->resetQueryPart('where');
     * // SELECT t_article.* FROM t_article ORDER BY article_id DESC LIMIT 5
     *
     * # limit をリセット
     * $qb->resetQueryPart('limit');
     * // SELECT t_article.* FROM t_article ORDER BY article_id DESC
     *
     * # orderBy をリセット
     * $qb->resetQueryPart('orderBy');
     * // SELECT t_article.* FROM t_article
     * ```
     */
    public function resetQueryPart(string|array|null $queryPartName = null): static
    {
        if ($queryPartName === null || is_array($queryPartName)) {
            foreach ($queryPartName ?? array_keys($this->sqlParts) as $name) {
                $this->resetQueryPart($name);
            }
            return $this;
        }

        if (!array_key_exists($queryPartName, $this->sqlParts)) {
            throw new \InvalidArgumentException("queryPartName:'$queryPartName' is undefined.");
        }

        $this->sqlParts[$queryPartName] = is_array($this->sqlParts[$queryPartName]) ? [] : null;

        // 各句に紐づくフィールドは特別にクリアしなければならない
        if ($queryPartName === 'select') {
            $this->subbuilders = [];
            $this->callbacks = [];
            $this->joinOrders = [];
            $this->sqlParts['option'] = [];
        }

        // 同上（where）
        if ($queryPartName === 'where') {
            $this->submethod = null;
            $this->subwhere = null;
            $this->emptyCondition = null;
        }

        return $this->_dirty();
    }

    public function reset(): static
    {
        parent::reset();

        // キャッシュ解除は真っ先に行う（配下のフィールドにもあてるため）
        $this->cache(false);

        // 固有なフィールドをクリア
        $this->caster = null;
        $this->subbuilders = [];
        $this->callbacks = [];
        $this->joinOrders = [];
        $this->wrappers = [];
        $this->lockMode = LockMode::NONE;
        $this->lockOption = '';
        $this->applyments = [
            'before' => null,
            'after'  => null,
        ];

        $this->lazyMode = null;
        $this->lazyMethod = null;
        $this->lazyParent = null;
        $this->lazyColumns = [];
        $this->lazyCondition = [];
        $this->lazyChunk = null;

        $this->resetQueryPart();

        return $this->_dirty();
    }

    /**
     * このビルダとサブビルダにキャッシュするように指示する
     *
     * キャッシュはクエリ＋パラメータで丸ごとキャッシュされる。
     *
     * ```php
     * # このクエリは10秒間キャッシュされる
     * $qb->cache(10)->column('t_article', ['state' => 'active'])->array();
     * ```
     *
     * @param null|int|false $ttl キャッシュ期限（null はキャッシュドライバーのデフォルトに従う。false は解除）
     */
    public function cache($ttl = null): static
    {
        if ($ttl === false) {
            $this->cache = [];
        }
        else {
            $this->cache = ['ttl' => $ttl];
        }

        foreach ($this->subbuilders as $subbuilder) {
            $subbuilder->cache($ttl);
        }
        return $this;
    }

    /**
     * 内部向け
     */
    public function getCacheTtl(): ?int
    {
        // 未設定
        if (!$this->cache) {
            return 0;
        }
        // ロッククエリでキャッシュを有効化するのは多くの場合良くない
        if ($this->lockMode !== LockMode::NONE) {
            return 0;
        }

        return $this->cache['ttl'];
    }

    /**
     * 取得方法指定で Database にクエリを投げる
     *
     * 実際は下記のようなエイリアスメソッドが定義されているのでそちらを使うことが多く、明示的に呼ぶことはほとんどない。
     *
     * @used-by array()
     * @used-by assoc()
     * @used-by lists()
     * @used-by pairs()
     * @used-by tuple()
     * @used-by value()
     */
    public function fetch(string $method, iterable $params = [])
    {
        $this->lazyMethod = $method;
        if ($this->lazyMode) {
            return $this;
        }

        return $this->getDatabase()->fetch($method, $this, $params);
    }

    public function fetchOrThrow(string $method, iterable $params = [])
    {
        $result = $this->fetch($method, $params);
        // Value, Tuple は [] を返し得ないし、複数行系も false を返し得ない
        if ($result === [] || $result === false) {
            throw new NonSelectedException('record is not found.');
        }
        return $result;
    }

    /**
     * Yielder を返す
     *
     * @link Database::yield()
     *
     * 引数は subselect 時に使用されるものなので通常時は不要。
     */
    public function yield(?int $chunk = null, ?string $method = null, iterable $params = []): Yielder
    {
        $method ??= 'array';
        $this->lazyMethod = $method;
        $this->lazyMode = self::LAZY_MODE_FETCH; // postselect 参照
        $this->lazyChunk = $chunk;
        return $this->getDatabase()->yield($this, $params)->setFetchMethod($method);
    }

    /**
     * 内部向け
     */
    public function getLazyMode(): ?string
    {
        return $this->lazyMode;
    }

    /**
     * 内部向け
     */
    public function getLazyChunk(): ?int
    {
        return $this->lazyChunk;
    }

    public function getQuery(): string
    {
        return "($this)" . concat(' ', trim($this->sqlParts['operator'] ?? ''));
    }

    public function getParams(?string $queryPartName = null): array
    {
        // _getSql で内部構造が変わることがあるので呼んでおく必要がある
        $this->__toString();

        $parts = $queryPartName ? $this->sqlParts[$queryPartName] : $this->sqlParts;
        $params = [];
        array_walk_recursive($parts, function ($param) use (&$params) {
            if ($param instanceof Select) {
                $param = $param->getActual();
            }
            if ($param instanceof Queryable) {
                $params = array_merge($params, $param->getParams());
            }
        });
        return $params;
    }
}
