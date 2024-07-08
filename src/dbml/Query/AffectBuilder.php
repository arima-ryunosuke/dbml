<?php

namespace ryunosuke\dbml\Query;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use ryunosuke\dbml\Entity\Entityable;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Metadata\Schema;
use ryunosuke\dbml\Mixin\FactoryTrait;
use ryunosuke\dbml\Query\Clause\Where;
use ryunosuke\dbml\Query\Expression\Expression;
use function ryunosuke\dbml\array_assort;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_get;
use function ryunosuke\dbml\array_maps;
use function ryunosuke\dbml\array_rekey;
use function ryunosuke\dbml\array_sprintf;
use function ryunosuke\dbml\array_uncolumns;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\first_key;
use function ryunosuke\dbml\last_value;
use function ryunosuke\dbml\str_exists;

// @formatter:off
/**
 * AFFECT ビルダークラス
 *
 * 現在のところは内部向けであり、このクラスを用いて何らかのクエリを生成するのは非推奨。
 *
 * @method bool                   getInsertSet()
 * @method $this                  setInsertSet($bool)
 * @method bool                   getUpdateEmpty()
 * @method $this                  setUpdateEmpty($bool)
 * @method array                  getDefaultInvalidColumn()
 * @method $this                  setDefaultInvalidColumn($array)
 * @method bool                   getFilterNoExistsColumn()
 * @method $this                  setFilterNoExistsColumn($bool)
 * @method bool                   getFilterNullAtNotNullColumn()
 * @method $this                  setFilterNullAtNotNullColumn($bool)
 * @method bool                   getConvertEmptyToNull()
 * @method $this                  setConvertEmptyToNull($bool)
 * @method bool                   getConvertBoolToInt()
 * @method $this                  setConvertBoolToInt($bool)
 * @method bool                   getConvertNumericToDatetime()
 * @method $this                  setConvertNumericToDatetime($bool)
 * @method bool                   getTruncateString()
 * @method $this                  setTruncateString($bool)
 */
// @formatter:on
class AffectBuilder extends AbstractBuilder
{
    use FactoryTrait;

    protected ?string $table      = null;        // テーブル名
    protected ?string $alias      = null;        // テーブルエイリアス
    protected ?string $constraint = null;        // ユニーク制約名
    protected array   $set        = [];          // 更新するデータ（column => value）
    protected array   $merge      = [];          // 更新するデータ（UPSERT 系）
    protected array   $column     = [];          // 更新するカラム（BULK 系）
    protected array   $values     = [];          // 更新するデータ（BULK 系）
    protected ?string $select     = null;        // SELECT 文（BULK 系）
    protected array   $where      = [];          // WHERE 条件
    protected array   $groupBy    = [];          // GROUP BY 列（ほとんど使われない）
    protected array   $having     = [];          // HAVING 条件（現状使ってない）
    protected array   $orderBy    = [];          // ORDER BY 列（ほとんど使われない）
    protected ?int    $limit      = null;        // LIMIT 件数（ほとんど使われない）

    protected $autoIncrementSeq = false;
    protected $affectedRows;

    public static function getDefaultOptions(): array
    {
        return [
            /** @var bool 拡張 INSERT SET 構文を使うか否か（mysql 以外は無視される） */
            'insertSet'                 => false,
            /** @var bool update で空データの時に意味のない更新をするか？（false だと構文エラーになる） */
            'updateEmpty'               => true,
            /** @var array invalid 時のデフォルトカラム
             * この設定で ['delete_at' => now()] などとすると invalid コール時に delete_at が now で更新されるようになる。
             */
            'defaultInvalidColumn'      => [
                //'delete_flg'  => 1,
                //'delete_user' => fn() => Auth()::id(),
                //'delete_time' => fn() => date('Y-m-d H:i:s'),
            ],
            /** @var bool insert 時などにテーブルに存在しないカラムを自動でフィルタするか否か
             * この設定を true にすると INSERT/UPDATE 時に「対象テーブルに存在しないカラム」が自動で伏せられるようになる。
             * 余計なキーが有るだけでエラーになるのは多くの場合めんどくさいだけなので true にするのは有用。
             *
             * ただし、スペルミスなどでキーの指定を誤ると何も言わずに伏せてしまうので気づきにくい不具合になるので注意。
             */
            'filterNoExistsColumn'      => true,
            /** @var bool insert 時などに not null な列に null が来た場合に自動でフィルタするか否か
             * この設定を true にすると INSERT/UPDATE 時に「not null なのに null が来たカラム」が自動で伏せられるようになる。
             * 呼び出し側の都合などで null を予約的に扱い、キーとして存在してしまうことはよくある。
             * どうせエラーになるので、結局呼び出し直前に if 分岐で unset したりするのでいっそのこと自動で伏せてしまったほうが便利なことは多い。
             */
            'filterNullAtNotNullColumn' => true,
            /** @var bool insert 時などに NULLABLE NUMERIC カラムは 空文字を null として扱うか否か
             * この設定を true にすると、例えば `hoge_no: INTEGER NOT NULL` なカラムに空文字を与えて INSERT/UPDATE した場合に自動で NULL に変換されるようになる。
             * Web システムにおいては空文字でパラメータが来ることが多いのでこれを true にしておくといちいち変換せずに済む。
             *
             * よくあるのは「年齢」というカラムがあり、入力画面で必須ではない場合。
             * 未入力で空文字が飛んでくるので、設定にもよるがそのまま mysql に突っ込んでしまうと 0 になるかエラーになる。
             * これはそういったケースで楽をするための設定。
             */
            'convertEmptyToNull'        => true,
            /** @var bool insert 時などに数値系カラムは真偽値を int として扱うか否か
             * この設定を true にすると、数値系カラムに真偽値が来た場合に自動で int に変換されるようになる。
             */
            'convertBoolToInt'          => true,
            /** @var bool insert 時などに日時カラムは int/float をタイムスタンプとして扱うか否か
             * この設定を true にすると、日時系カラムに int/float が来た場合にタイムスタンプとみなすようになる。
             */
            'convertNumericToDatetime'  => true,
            /** @var bool insert 時などに文字列カラムは length で切るか否か
             * この設定を true にすると、文字列系カラムに length を超える文字列が来た場合に切り落とされるようになる。
             */
            'truncateString'            => false,
        ];
    }

    public function __toString(): string
    {
        return $this->sql;
    }

    public function build(array $queryParts, bool $append = false): static
    {
        if (array_key_exists('table', $queryParts) && $queryParts['table']) {
            assert(!$append || ($append && $this->table === null));
            assert(!$append || ($append && $this->alias === null));

            if (is_string($queryParts['table']) && str_exists($queryParts['table'], TableDescriptor::META_CHARACTORS)) {
                $queryParts['table'] = new TableDescriptor($this->database, $queryParts['table'], []);
            }

            if ($queryParts['table'] instanceof TableGateway) {
                $gateway = $queryParts['table'];
                $scp = $queryParts['table']->getScopeParamsForAffect([],
                    $queryParts['where'] ?? [],
                    $queryParts['orderBy'] ?? [],
                    $queryParts['limit'] ?? [],
                    $queryParts['groupBy'] ?? [],
                    $queryParts['having'] ?? [],
                );

                $queryParts['table'] = $gateway->tableName() ?? $queryParts['table'];
                $queryParts['alias'] = $gateway->alias() ?? $queryParts['alias'] ?? null;
                $queryParts['constraint'] = $gateway->foreign() ?? $queryParts['constraint'] ?? null; // 流用

                $queryParts['set'] = array_merge($queryParts['set'] ?? [], $scp['set'] ?? []);
                $queryParts['column'] = array_merge($queryParts['column'] ?? [], array_keys($scp['column'][$gateway->modifier()] ?? []));

                $queryParts['where'] = array_merge($scp['where'] ?? []);
                $queryParts['groupBy'] = array_merge($scp['groupBy'] ?? []);
                $queryParts['having'] = array_merge($scp['having'] ?? []);
                $queryParts['orderBy'] = array_merge($scp['orderBy'] ?? []);
                $queryParts['limit'] ??= $scp['limit'] ?? null;
            }
            if ($queryParts['table'] instanceof TableDescriptor) {
                $tableDescriptor = $queryParts['table'];
                if ($tableDescriptor->scope) {
                    $gateway = $this->database->{$tableDescriptor->table};
                    foreach ($tableDescriptor->scope as $scope => $args) {
                        $gateway = $gateway->scope($scope, ...$args);
                    }
                    $scp = $gateway->getScopeParamsForAffect();
                }

                $queryParts['table'] = $tableDescriptor->table ?? $queryParts['table'];
                $queryParts['alias'] = $tableDescriptor->alias ?? $queryParts['alias'] ?? null;
                $queryParts['constraint'] = $tableDescriptor->fkeyname ?? $queryParts['constraint'] ?? null; // 流用

                $queryParts['column'] = array_merge($queryParts['column'] ?? [], $tableDescriptor->column, $scp['column'] ?? []);

                $queryParts['where'] = array_merge($queryParts['where'] ?? [], $tableDescriptor->condition, $scp['where'] ?? []);
                $queryParts['groupBy'] = array_merge($queryParts['groupBy'] ?? [], $tableDescriptor->group, $scp['groupBy'] ?? []);
                $queryParts['having'] = array_merge($queryParts['having'] ?? [], $tableDescriptor->having, $scp['having'] ?? []);
                $queryParts['orderBy'] = array_merge($queryParts['orderBy'] ?? [], $tableDescriptor->order, $scp['orderBy'] ?? []);
                $queryParts['limit'] ??= $tableDescriptor->limit ?? $scp['limit'] ?? null;
            }

            $this->table = $this->database->convertTableName($queryParts['table']);
        }

        if (array_key_exists('alias', $queryParts) && $queryParts['alias']) {
            assert(!$append || ($append && $this->alias === null));

            $this->alias = $queryParts['alias'];
        }

        if (array_key_exists('constraint', $queryParts) && $queryParts['constraint']) {
            assert(!$append || ($append && $this->constraint === null));

            $this->constraint = $queryParts['constraint'];
        }

        if (array_key_exists('values', $queryParts) && $queryParts['values']) {
            if (!$append) {
                $this->values = [];
            }

            $rows = [];
            foreach ($queryParts['values'] as $n => $row) {
                if (!is_array($row) && !$row instanceof Entityable) {
                    throw new \InvalidArgumentException('$data\'s element must be array.');
                }
                $rows[$n] = $this->normalize($row);

                if (!isset($queryParts['column'])) {
                    $queryParts['column'] = array_keys($rows[$n]);
                }
                elseif ($queryParts['column'] && $queryParts['column'] !== array_keys($rows[$n])) {
                    throw new \UnexpectedValueException('columns are not match.');
                }
            }
            $this->values = $this->values ? array_merge($this->values, $rows) : $rows; // 無条件で array_merge すると連番キーが死んでしまう
        }

        if (array_key_exists('column', $queryParts) && $queryParts['column']) {
            if (!$append) {
                $this->column = [];
            }

            $this->column = array_merge($this->column, arrayize($queryParts['column']));
        }

        if (array_key_exists('set', $queryParts) && $queryParts['set']) {
            if (!$append) {
                $this->set = [];
            }

            $this->set = $this->set + $this->normalize($queryParts['set']);
        }

        if (array_key_exists('merge', $queryParts) && $queryParts['merge']) {
            if (!$append) {
                $this->merge = [];
            }

            $this->merge = $this->merge + $this->normalize($this->wildUpdate($queryParts['merge'], reset($this->values) ?: $this->set, true));
        }

        if (array_key_exists('select', $queryParts) && $queryParts['select']) {
            assert(!$append || ($this->select === null));

            $this->select = $queryParts['select'];
        }

        if (array_key_exists('where', $queryParts) && $queryParts['where']) {
            if (!$append) {
                $this->where = [];
            }

            $this->where = array_merge($this->where, $this->_precondition([$this->alias ?? $this->table => $this->table], arrayize($queryParts['where'])));
        }

        if (array_key_exists('groupBy', $queryParts) && $queryParts['groupBy']) {
            if (!$append) {
                $this->groupBy = [];
            }

            $this->groupBy = array_merge($this->groupBy, arrayize($queryParts['groupBy']));
        }

        if (array_key_exists('having', $queryParts) && $queryParts['having']) {
            if (!$append) {
                $this->having = [];
            }

            $this->having = array_merge($this->having, $this->_precondition([$this->alias ?? $this->table => $this->table], arrayize($queryParts['having'])));
        }

        if (array_key_exists('orderBy', $queryParts) && $queryParts['orderBy']) {
            if (!$append) {
                $this->orderBy = [];
            }

            $this->orderBy = array_merge($this->orderBy, arrayize($queryParts['orderBy']));
        }

        if (array_key_exists('limit', $queryParts) && $queryParts['limit']) {
            if ($append) {
                assert($this->limit === null);
            }

            $queryParts['limit'] = arrayize($queryParts['limit']);
            $this->limit = (int) reset($queryParts['limit']);
            if ($this->limit < 0) {
                throw new \InvalidArgumentException("\$limit must be >= 0 ($this->limit).");
            }
        }

        return $this;
    }

    public function wildUpdate(array $updateData, array $insertData, bool $useReference): array
    {
        // この分岐はなくても実質同じだが無駄にループをまわしたくないので早期リターン
        if (!array_key_exists('*', $updateData)) {
            return $updateData;
        }

        // 特別な意味はないがなんとなく * の位置で保持しておく
        $newUpdateData = [];
        foreach ($updateData as $uColumn => $uDatum) {
            if ($uColumn === '*') {
                $uDatum ??= function ($updateColumn, $insertData) use ($useReference) {
                    $reference = $this->database->getCompatiblePlatform()->getReferenceSyntax($updateColumn);
                    return !$useReference || $reference === null ? $insertData[$updateColumn] : $this->database->raw($reference);
                };
                foreach (array_diff_key($insertData, $updateData) as $iColumn => $iData) {
                    $newUpdateData[$iColumn] = ($uDatum instanceof \Closure) ? $uDatum($iColumn, $insertData) : $uDatum;
                }
            }
            else {
                $newUpdateData[$uColumn] = $uDatum;
            }
        }
        return array_diff_key($newUpdateData, $this->database->getSchema()->getTableUniqueColumns($this->table, $this->constraint ?? 'PRIMARY'));
    }

    public function normalize($row): array
    {
        // これはメソッド冒頭に記述し、決して場所を移動しないこと
        $columns = $this->database->getSchema()->getTableColumns($this->table);
        $autocolumn = $this->database->getSchema()->getTableAutoIncrement($this->table)?->getName();

        if ($row instanceof Entityable) {
            $row = $row->arrayize();
        }

        if ($this->column) {
            [$list, $hash] = array_assort(arrayize($row), [fn($v, $k) => is_int($k), fn($v, $k) => is_string($k)]);
            // column を（暗黙的にも）与えているものの、データ配列が全て col=>val で読み替えの必要がないこともある
            if ($list) {
                $row = array_combine($this->column, $list) + $hash;
            }
        }

        foreach ($columns as $cname => $column) {
            if (array_key_exists($cname, $row) && ($vaffect = $this->database->getSchema()->getTableColumnExpression($this->table, $cname, 'affect'))) {
                $row = $vaffect($row[$cname], $row) + $row;
            }
            if ($column->getPlatformOptions()['virtual'] ?? null) {
                unset($columns[$cname]);
            }
        }

        if ($this->database->getOption('preparing')) {
            $row = array_each($row, function (&$carry, $v, $k) {
                if (is_int($k) && is_string($v) && str_starts_with($v, ':')) {
                    $k = substr($v, 1);
                    $v = $this->database->raw(":$k");
                }
                $carry[$k] = $v;
            }, []);
        }

        if ($this->getUnsafeOption('filterNoExistsColumn')) {
            $row = array_intersect_key($row, $columns);
        }

        $filterNullAtNotNullColumn = $this->getUnsafeOption('filterNullAtNotNullColumn');
        $convertEmptyToNull = $this->getUnsafeOption('convertEmptyToNull');
        $convertBoolToInt = $this->getUnsafeOption('convertBoolToInt');
        $convertNumericToDatetime = $this->getUnsafeOption('convertNumericToDatetime');
        $truncateString = $this->getUnsafeOption('truncateString');
        $autoCastType = $this->database->getOption('autoCastType');
        $compatibleCharAndBinary = $this->database->getCompatiblePlatform()->supportsCompatibleCharAndBinary();

        $integerTypes = [Types::BOOLEAN => true, Types::INTEGER => true, Types::SMALLINT => true, Types::BIGINT => true];
        $decimalTypes = [Types::DECIMAL => true, Types::FLOAT => true];
        $numericTypes = $integerTypes + $decimalTypes;
        $dateTypes = [Types::DATE_MUTABLE => true, Types::DATE_IMMUTABLE => true];
        $datetimeTypes = [Types::DATETIME_MUTABLE => true, Types::DATETIME_IMMUTABLE => true];
        $datetimeTZTypes = [Types::DATETIMETZ_MUTABLE => true, Types::DATETIMETZ_IMMUTABLE => true];
        $datetimableTypes = $dateTypes + $datetimeTypes + $datetimeTZTypes;
        $clobTypes = [Types::STRING => true, Types::TEXT => true];
        $blobTypes = [Types::BINARY => true, Types::BLOB => true];
        $stringTypes = $clobTypes + $blobTypes;

        foreach ($columns as $cname => $column) {
            if (array_key_exists($cname, $row)) {
                $type = $column->getType();
                $typename = $type->getName();
                $nullable = !$column->getNotnull();

                if ($filterNullAtNotNullColumn && $row[$cname] === null && !$nullable && $cname !== $autocolumn) {
                    unset($row[$cname]);
                    continue;
                }

                if ($convertEmptyToNull && $row[$cname] === '' && ($cname === $autocolumn || (!isset($stringTypes[$typename]) && $nullable))) {
                    $row[$cname] = null;
                }

                if ($convertBoolToInt && is_bool($row[$cname]) && isset($numericTypes[$typename])) {
                    $row[$cname] = (int) $row[$cname];
                }

                if ($convertNumericToDatetime && (is_int($row[$cname]) || is_float($row[$cname])) && isset($datetimableTypes[$typename])) {
                    $dt = new \DateTime();
                    $dt->setTimestamp($row[$cname]);
                    $dt->modify(((int) (($row[$cname] - (int) $row[$cname]) * 1000 * 1000)) . " microsecond");
                    $format = null;
                    $format ??= isset($dateTypes[$typename]) ? $this->database->getPlatform()->getDateFormatString() : null;
                    $format ??= isset($datetimeTypes[$typename]) ? $this->database->getPlatform()->getDateTimeFormatString() : null;
                    $format ??= isset($datetimeTZTypes[$typename]) ? $this->database->getPlatform()->getDateTimeTzFormatString() : null;
                    $row[$cname] = $dt->format($format);
                }

                if ($truncateString && is_string($row[$cname]) && isset($stringTypes[$typename])) {
                    $row[$cname] = $this->database->getCompatiblePlatform()->truncateString($row[$cname], $column);
                }

                if (($converter = $autoCastType[$typename]['affect'] ?? null) && !$row[$cname] instanceof Queryable) {
                    if ($converter instanceof \Closure) {
                        $row[$cname] = $converter($row[$cname], $this->database->getPlatform());
                    }
                    else {
                        $row[$cname] = $type->convertToDatabaseValue($row[$cname], $this->database->getPlatform());
                    }
                }

                if (!$compatibleCharAndBinary && is_string($row[$cname]) && isset($blobTypes[$typename])) {
                    $row[$cname] = $this->database->getCompatiblePlatform()->getBinaryExpression($row[$cname]);
                }
            }
        }

        // sqlite/mysql は null を指定すれば自動採番されるが、他の RDBMS では伏せないと採番されないようだ
        if ($autocolumn && !isset($row[$autocolumn]) && !$this->database->getCompatiblePlatform()->supportsIdentityNullable()) {
            unset($row[$autocolumn]);
        }

        $row = $this->database->{$this->table}->normalize($row);

        return $row;
    }

    public function tableAs(): string
    {
        return $this->table . concat(' AS ', $this->alias);
    }

    public function restrictWheres(string $event): array
    {
        assert(in_array(strtolower($event), ['update', 'delete']));
        $where = [];

        $schema = $this->database->getSchema();
        $fkeys = $schema->getForeignKeys($this->table, null);
        foreach ($fkeys as $fkey) {
            if ($fkey->{"on$event"}() === null) {
                $ltable = first_key($schema->getForeignTable($fkey));
                $notexists = $this->database->select($ltable);
                $notexists->setSubwhere($this->table, null, $fkey->getName());
                $where[] = $notexists->notExists();
            }
        }
        return $where;
    }

    public function cascadeValues(ForeignKeyConstraint $fkey): array
    {
        $subdata = [];
        foreach (array_combine($fkey->getLocalColumns(), $fkey->getForeignColumns()) as $lcol => $fcol) {
            if (array_key_exists($fcol, $this->set)) {
                $subdata[$lcol] = $this->set[$fcol];
            }
        }
        return $subdata;
    }

    public function cascadeWheres(ForeignKeyConstraint $fkey): array
    {
        $pselect = $this->database->select([$fkey->getForeignTableName() => $fkey->getForeignColumns()], $this->where);
        $subwhere = [];
        if (!$this->database->getCompatiblePlatform()->supportsRowConstructor() && count($fkey->getLocalColumns()) > 1) {
            $pvals = array_maps($pselect->array(), fn($pval) => array_combine($fkey->getLocalColumns(), $pval));
            $ltable = first_key($this->database->getSchema()->getForeignTable($fkey));
            $pcond = $this->database->getCompatiblePlatform()->getPrimaryCondition($pvals, $ltable);
            $subwhere[] = $this->database->queryInto($pcond) ?: 'FALSE';
        }
        else {
            $ckey = implode(',', $fkey->getLocalColumns());
            $subwhere["($ckey)"] = $pselect;
        }
        return $subwhere;
    }

    public function loadCsv(null|string|TableDescriptor|TableGateway $table = null, $column = null, $rows = null, array $opt = []): static
    {
        $this->build([
            'table' => $table,
        ], true);
        $this->params = [];

        $columns = $column ?: array_keys($this->database->getSchema()->getTableColumns($this->table, Schema::COLUMN_REAL | Schema::COLUMN_UPDATABLE));

        $current = mb_internal_encoding();

        $values = [];

        foreach ($rows as $m => $fields) {
            if ($m < $opt['skip']) {
                continue;
            }

            if ($current !== $opt['encoding']) {
                mb_convert_variables($current, $opt['encoding'], $fields);
            }

            $r = -1;
            $row = [];
            foreach ($columns as $cname => $expr) {
                $r++;
                // 範囲外は全部直値（マップするキーがないのでどうしようもない）
                if (!isset($fields[$r])) {
                    $row[$cname] = $expr;
                }
                // 値のみ指定ならそれをカラム名として CSV 列値を使う（ただし、 null はスキップ）
                elseif (is_int($cname)) {
                    if ($expr === null) {
                        continue;
                    }
                    $row[$expr] = $fields[$r];
                }
                // Expression はマーカーとしての役割なので作り直す
                elseif ($expr instanceof Expression) {
                    $row[$cname] = Expression::new($expr, $fields[$r]);
                }
                elseif ($expr instanceof \Closure) {
                    $row[$cname] = $expr($fields[$r]);
                }
                else {
                    $row[$cname] = $expr;
                }
            }
            $row = $this->normalize($row);
            $set = $this->bindInto($row, $this->params);
            $values[] = '(' . implode(', ', $set) . ')';
        }

        $colnames = array_filter(array_keys(array_rekey($columns, function ($k, $v) { return is_int($k) ? $v : $k; })), 'strlen');
        $this->sql = sprintf("INSERT INTO {$this->tableAs()} (%s) VALUES %s", implode(', ', $colnames), implode(', ', $values));

        return $this;
    }

    public function insertSelect(null|string|TableDescriptor|TableGateway $table = null, $sql = null, $columns = [], array $opt = []): static
    {
        $this->build([
            'table'  => $table,
            'select' => $sql,
            'column' => $columns,
        ], true);
        $this->params = [];

        $ignore = array_get($opt, 'ignore') ? $this->database->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $this->sql = "INSERT {$ignore}INTO {$this->tableAs()} " . concat('(', implode(', ', $this->column), ') ') . $this->select;

        return $this;
    }

    public function insertArray(null|string|TableDescriptor|TableGateway $table = null, $data = null, array $opt = []): static
    {
        $this->build([
            'table'  => $table,
            'values' => $data,
        ], true);
        $this->params = [];

        $values = [];
        foreach ($this->values as $row) {
            $set = $this->bindInto($row, $this->params);

            $values[] = '(' . implode(', ', $set) . ')';
        }

        $ignore = array_get($opt, 'ignore') ? $this->database->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $this->sql = sprintf("INSERT {$ignore}INTO {$this->tableAs()} (%s) VALUES %s", implode(', ', $this->column), implode(', ', $values));

        return $this;
    }

    public function updateArray(null|string|TableDescriptor|TableGateway $table = null, $data = null, $where = [], array $opt = []): static
    {
        $this->build([
            'table'  => $table,
            'column' => [], // 可変なので指定しない
            'values' => $data,
            'where'  => $where,
        ], true);
        $this->params = [];

        $pcols = $this->database->getSchema()->getTablePrimaryColumns($this->table);

        $columns = $this->database->getSchema()->getTableColumns($this->table);
        $singleuk = count($pcols) === 1 ? first_key($pcols) : null;

        $params = array_fill_keys(array_keys($columns), []);
        $pvals = [];
        $result = [];
        foreach ($this->values as $row) {
            foreach ($pcols as $pcol => $dummy) {
                if (!isset($row[$pcol])) {
                    throw new \InvalidArgumentException('$data\'s must be contain primary key.');
                }
                if (!is_scalar($row[$pcol])) {
                    throw new \InvalidArgumentException('$data\'s primary key must be scalar value.');
                }
            }

            foreach ($columns as $col => $val) {
                if (!array_key_exists($col, $row)) {
                    continue;
                }
                if (isset($pcols[$col])) {
                    $pvals[$col][] = $row[$col];
                    continue;
                }

                if ($singleuk) {
                    $pv = $this->bindInto($row[$singleuk], $params[$col]);
                }
                else {
                    $pv = [];
                    foreach ($pcols as $pcol => $dummy) {
                        $pv[] = $pcol . ' = ' . $this->bindInto($row[$pcol], $params[$col]);
                    }
                    $pv = implode(' AND ', $pv);
                }
                $tv = $this->bindInto($row[$col], $params[$col]);
                $result[$col][] = "WHEN $pv THEN $tv";
            }
        }

        $cols = [];
        foreach ($result as $column => $exprs) {
            $cols[$column] = $this->database->raw('CASE ' . concat($singleuk ?: '', ' ') . implode(' ', $exprs) . " ELSE $column END", $params[$column]);
        }

        $columns = $pvals + $cols;
        $pkcols = array_intersect_key($columns, $pcols);
        $cvcols = array_diff_key($columns, $pcols);

        $set = $this->bindInto($cvcols, $this->params);
        $sets = array_sprintf($set, '%2$s = %1$s', ', ');

        $pkcond = $this->database->getCompatiblePlatform()->getPrimaryCondition(array_uncolumns($pkcols), $this->table);
        $criteria = Where::and(array_merge($this->where, [$pkcond]))($this->database)->merge($this->params);

        $ignore = array_get($opt, 'ignore') ? $this->database->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $this->sql = "UPDATE {$ignore}{$this->tableAs()} SET $sets WHERE $criteria";

        return $this;
    }

    public function modifyArray(null|string|TableDescriptor|TableGateway $table = null, $insertData = null, $updateData = [], $uniquekey = '', array $opt = []): static
    {
        $this->autoIncrementSeq = $this->database->getCompatiblePlatform()->supportsIdentityAutoUpdate() ? false : null;
        $this->build([
            'table'      => $table,
            'values'     => $insertData,
            'constraint' => $uniquekey,
            'merge'      => $updateData,
        ], true);
        $this->params = [];

        $cplatform = $this->database->getCompatiblePlatform();
        $ukcols = $this->database->getSchema()->getTableUniqueColumns($this->table, $this->constraint);
        $merge = $cplatform->getMergeSyntax(array_keys($ukcols));
        $refer = $cplatform->getReferenceSyntax('%1$s');

        $values = [];
        foreach ($this->values as $row) {
            $set = $this->bindInto($row, $this->params);

            $values[] = '(' . implode(', ', $set) . ')';
        }

        if ($this->merge) {
            $updates = array_sprintf($this->bindInto($this->merge, $this->params), '%2$s = %1$s', ', ');
        }
        else {
            $updates = array_sprintf($this->column, '%1$s = ' . $refer, ', ');
        }

        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $this->sql = sprintf("INSERT {$ignore}INTO {$this->tableAs()} (%s) VALUES %s $merge %s", implode(', ', $this->column), implode(', ', $values), $updates);

        return $this;
    }

    public function insert(null|string|TableDescriptor|TableGateway $table = null, $data = null, array $opt = []): static
    {
        $this->build([
            'table' => $table,
            'set'   => $data,
        ], true);
        $this->params = [];

        $set = $this->bindInto($this->set, $this->params);

        $cplatform = $this->database->getCompatiblePlatform();
        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $this->sql = "INSERT {$ignore}INTO {$this->tableAs()} ";
        if ($this->where) {
            $condition = $this->database->selectNotExists($this->table, $this->where);
            $condition = $condition->merge($this->params);
            $fromDual = concat(' FROM ', $cplatform->getDualTable());
            $this->sql .= sprintf("(%s) SELECT %s$fromDual WHERE $condition", implode(', ', array_keys($set)), implode(', ', $set));
        }
        elseif (count($this->set) && $cplatform->supportsInsertSet() && $this->getUnsafeOption('insertSet')) {
            $this->sql .= "SET " . array_sprintf($set, '%2$s = %1$s', ', ');
        }
        else {
            $this->sql .= sprintf("(%s) VALUES (%s)", implode(', ', array_keys($set)), implode(', ', $set));
        }

        return $this;
    }

    public function update(null|string|TableDescriptor|TableGateway $table = null, $data = null, $where = [], array $opt = []): static
    {
        $this->build([
            'table' => $table,
            'set'   => $data,
            'where' => $where,
        ], true);
        $this->params = [];

        if (!count($this->set) && $this->getUnsafeOption('updateEmpty')) {
            foreach ($this->database->getSchema()->getTablePrimaryColumns($this->table) as $pk => $column) {
                $this->set[$pk] = $this->database->raw($pk);
            }
        }

        $set = $this->bindInto($this->set, $this->params);
        $sets = array_sprintf($set, '%2$s = %1$s', ', ');

        $criteria = Where::and($this->where)($this->database)->merge($this->params);

        $ignore = array_get($opt, 'ignore') ? $this->database->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $this->sql = "UPDATE {$ignore}{$this->tableAs()} SET $sets" . ($criteria ? " WHERE $criteria" : '');

        return $this;
    }

    public function replace(null|string|TableDescriptor|TableGateway $table = null, $data = null, array $opt = []): static
    {
        $this->build([
            'table' => $table,
            'set'   => $data,
        ], true);
        $this->params = [];

        $sets = $this->bindInto($this->set, $this->params);

        $primary = $this->database->getSchema()->getTablePrimaryColumns($this->table);
        $columns = $this->database->getSchema()->getTableColumns($this->table, Schema::COLUMN_REAL | Schema::COLUMN_UPDATABLE);

        $cplatform = $this->database->getCompatiblePlatform();
        $defaults = $this->database->getEmptyRecord($this->table);
        $selects = [];
        foreach ($columns as $cname => $column) {
            if (array_key_exists($cname, $sets)) {
                $selects[$cname] = $sets[$cname];
            }
            else {
                $pkisnull = array_sprintf($primary, "$this->table.%2\$s IS NULL", ' AND ');
                $default = $this->database->raw($this->database->quote($defaults[$cname]));
                $selects[$cname] = $cplatform->getCaseWhenSyntax(null, [$pkisnull => $default], $this->database->raw($cname))->merge($this->params);
            }
        }

        $criteria = Where::and(array_intersect_key($this->set, $primary))($this->database)->merge($this->params);

        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $this->sql = "REPLACE {$ignore}INTO {$this->tableAs()} (" . implode(', ', array_keys($selects)) . ") ";
        $this->sql .= "SELECT " . implode(', ', $selects) . " FROM (SELECT NULL) __T ";
        $this->sql .= "LEFT JOIN $this->table ON " . ($criteria ? $criteria : '1=0');

        return $this;
    }

    public function modify(null|string|TableDescriptor|TableGateway $table = null, $insertData = null, $updateData = [], $uniquekey = '', array $opt = []): static
    {
        $this->autoIncrementSeq = $this->database->getCompatiblePlatform()->supportsIdentityAutoUpdate() ? false : null;
        $this->build([
            'table'      => $table,
            'set'        => $insertData,
            'merge'      => $updateData,
            'constraint' => $uniquekey,
        ], true);
        $this->params = [];

        $schema = $this->database->getSchema();
        $pkcols = $schema->getTableUniqueColumns($this->table, $this->constraint ?? 'PRIMARY');

        $merge = $this->database->getCompatiblePlatform()->convertMergeData($this->set, $this->merge);

        $sets1 = $this->bindInto($this->set, $this->params);
        $condition = null;
        if ($this->where) {
            $condition = $this->database->selectNotExists($this->table, $this->where);
            $condition = $condition->merge($this->params);
        }
        $sets2 = $this->bindInto($merge, $this->params);

        $cplatform = $this->database->getCompatiblePlatform();
        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $this->sql = "INSERT {$ignore}INTO {$this->tableAs()} ";
        if ($condition !== null) {
            $fromDual = concat(' FROM ', $cplatform->getDualTable());
            $this->sql .= sprintf("(%s) SELECT %s$fromDual WHERE $condition", implode(', ', array_keys($sets1)), implode(', ', $sets1));
        }
        elseif (count($this->set) && $cplatform->supportsInsertSet() && $this->getUnsafeOption('insertSet')) {
            $this->sql .= "SET " . array_sprintf($sets1, '%2$s = %1$s', ', ');
        }
        else {
            $this->sql .= sprintf("(%s) VALUES (%s)", implode(', ', array_keys($sets1)), implode(', ', $sets1));
        }
        $this->sql .= ' ' . $cplatform->getMergeSyntax(array_keys($pkcols)) . ' ' . array_sprintf($sets2, '%2$s = %1$s', ', ');

        return $this;
    }

    public function duplicate(null|string|TableDescriptor|TableGateway $table = null, array $overrideData = [], $where = [], $sourceTable = null, array $opt = []): static
    {
        $this->build([
            'table' => $table,
            'set'   => $overrideData,
            'where' => $where,
        ], true);
        $this->params = [];

        $sourceTable = $sourceTable === null ? $this->table : $sourceTable;
        $sourceTable = $this->database->convertTableName($sourceTable);

        $metatarget = $this->database->getSchema()->getTableColumns($this->table, Schema::COLUMN_REAL | Schema::COLUMN_UPDATABLE);
        $metasource = $this->database->getSchema()->getTableColumns($sourceTable);

        // 主キーが指定されてないなんておかしい（必ず重複してしまう）
        // しかし AUTO INCREMENT を期待して敢えて指定してないのかもしれない
        // したがって、「同じテーブルの場合は AUTO INCREMENT な主キーはselectしない」で対応できる（その結果例外が出てもそれは呼び出し側の責任）
        if ($sourceTable === $this->table) {
            $autocolumn = $this->database->getSchema()->getTableAutoIncrement($this->table)?->getName();
            $metasource = array_diff_key($metasource, [$autocolumn => null]);
        }

        $overrideSet = $this->bindInto($this->set, $this->params);
        $overrideSet = array_map(function ($v) { return Expression::new($v); }, $overrideSet);

        $this->set += ($this->table === $sourceTable ? [] : $this->database->getSchema()->getTablePrimaryColumns($sourceTable));

        foreach ($metasource as $name => $dummy) {
            if (array_key_exists($name, $metatarget) && !array_key_exists($name, $overrideSet)) {
                $overrideSet[$name] = Expression::new($name);
            }
        }

        $select = $this->database->select([$sourceTable => $overrideSet], $this->where);
        $select->merge($this->params);
        $ignore = array_get($opt, 'ignore') ? $this->database->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $this->sql = "INSERT {$ignore}INTO {$this->tableAs()} (" . implode(', ', array_keys($overrideSet)) . ") $select";

        return $this;
    }

    public function delete(null|string|TableDescriptor|TableGateway $table = null, $where = [], array $opt = []): static
    {
        $this->build([
            'table' => $table,
            'where' => $where,
        ], true);
        $this->params = [];

        $criteria = Where::and($this->where)($this->database)->merge($this->params);

        $ignore = array_get($opt, 'ignore') ? $this->database->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $this->sql = "DELETE {$ignore}FROM {$this->tableAs()}" . ($criteria ? " WHERE $criteria" : '');

        return $this;
    }

    public function reduce(null|string|TableDescriptor|TableGateway $table = null, $limit = null, $orderBy = [], $groupBy = [], $where = [], array $opt = []): static
    {
        $this->build([
            'table'   => $table,
            'limit'   => $limit,
            'orderBy' => $orderBy,
            'groupBy' => $groupBy,
            'where'   => $where,
        ], true);
        $this->params = [];

        $simplize = function ($v) { return last_value(explode('.', $v)); };
        $orderBy = array_maps($this->orderBy, function ($v, $k) use ($simplize) {
            if (is_int($k)) {
                return $simplize($v);
            }
            $v = ['ASC' => true, 'DESC' => false][$v] ?? $v;
            return ($v ? '+' : '-') . $simplize($k);
        });
        if (count($orderBy) !== 1) {
            throw new \InvalidArgumentException("\$orderBy must be === 1.");
        }

        $orderBy = reset($orderBy);
        $groupBy = array_map($simplize, $this->groupBy);
        $this->where = array_combine(array_map($simplize, array_keys($this->where)), $this->where);

        $BASETABLE = '__dbml_base_table';
        $JOINTABLE = '__dbml_join_table';
        $TEMPTABLE = '__dbml_temp_table';
        $GROUPTABLE = '__dbml_group_table';
        $VALUETABLE = '__dbml_value_table';

        $pcols = $this->database->getSchema()->getTablePrimaryKey($this->table)->getColumns();
        $ascdesc = $orderBy[0] !== '-';
        $glsign = ($ascdesc ? '>=' : '<=');
        $orderBy = ltrim($orderBy, '-+');

        // 境界値が得られるサブクエリ
        $subquery = $this->database->select(["$this->table $VALUETABLE" => $orderBy])
            ->where($this->where)
            ->andWhere(array_map(function ($gk) use ($GROUPTABLE, $VALUETABLE) { return "$GROUPTABLE.$gk = $VALUETABLE.$gk"; }, $groupBy))
            ->orderBy($groupBy + [$orderBy => $ascdesc])
            ->limit(1, $this->limit);

        // グルーピングしないなら主キー指定で消す必要はなく、直接比較で消すことができる（結果は変わらないがパフォーマンスが劇的に違う）
        if (!$groupBy) {
            $this->where["$this->table.$orderBy $glsign ?"] = $subquery->wrap("SELECT * FROM", "AS $TEMPTABLE");
        }
        else {
            // グループキーと境界値が得られるサブクエリ
            $subtable = $this->database->select([
                "$this->table $GROUPTABLE" => $groupBy + [$orderBy => $subquery],
            ], $this->where)->groupBy($groupBy);
            // ↑と JOIN して主キーが得られるサブクエリ
            $select = $this->database->select([
                "$this->table $BASETABLE" => $pcols,
            ])->innerJoinOn([$JOINTABLE => $subtable],
                array_merge(array_map(function ($gk) use ($BASETABLE, $JOINTABLE) { return "$JOINTABLE.$gk = $BASETABLE.$gk"; }, $groupBy), [
                    "$BASETABLE.$orderBy $glsign $JOINTABLE.$orderBy",
                ])
            );
            // ↑を主キー where に設定する
            $this->where["(" . implode(',', $pcols) . ")"] = $select->wrap("SELECT * FROM", "AS $TEMPTABLE");
        }

        return $this->delete(opt: $opt);
    }

    public function truncate(null|string|TableDescriptor|TableGateway $table = null, array $opt = []): static
    {
        $this->autoIncrementSeq = $this->database->getCompatiblePlatform()->supportsResetAutoIncrementOnTruncate() ? false : 1;
        $this->build([
            'table' => $table,
        ], true);
        $this->params = [];

        $this->sql = $this->database->getCompatiblePlatform()->getTruncateTableSQL($this->table, array_get($opt, 'cascade'));

        return $this;
    }

    public function execute()
    {
        return $this->affectedRows = $this->database->executeAffect($this->getQuery(), $this->getParams());
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getConstraint(): ?string
    {
        return $this->constraint;
    }

    public function getSet(): array
    {
        return $this->set;
    }

    public function getMerge(): array
    {
        return $this->merge;
    }

    public function getColumn(): array
    {
        return $this->column;
    }

    public function getSelect(): ?string
    {
        return $this->select;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getWhere(): array
    {
        return $this->where;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getHaving(): array
    {
        return $this->having;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getAutoIncrementSeq()
    {
        if (true
            && $this->autoIncrementSeq !== false
            && $this->database->getSchema()->getTableAutoIncrement($this->table) !== null
        ) {
            return $this->autoIncrementSeq;
        }
        return false;
    }

    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    public function reset(): static
    {
        parent::reset();

        $this->table = null;
        $this->alias = null;
        $this->constraint = null;
        $this->set = [];
        $this->merge = [];
        $this->column = [];
        $this->values = [];
        $this->select = null;
        $this->where = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;

        $this->autoIncrementSeq = false;
        $this->affectedRows = null;

        return $this;
    }
}
