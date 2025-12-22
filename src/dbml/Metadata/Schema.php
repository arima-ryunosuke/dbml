<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Psr\SimpleCache\CacheInterface;
use ryunosuke\dbml\Database;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_pickup;
use function ryunosuke\dbml\array_rekey;
use function ryunosuke\dbml\array_unset;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\cache_fetch;
use function ryunosuke\dbml\first_keyvalue;
use function ryunosuke\dbml\fnmatch_or;

/**
 * スキーマ情報の収集と保持とキャッシュを行うクラス
 *
 * ### キャッシュ
 *
 * カラム情報や主キー情報の取得のためにスキーマ情報を結構な勢いで漁る。
 * しかし、基本的にはスキーマ情報は自動でキャッシュするので意識はしなくて OK。
 *
 * ### VIEW
 *
 * VIEW は TABLE と同等の存在として扱う。つまり `getTableNames` メソッドの返り値には VIEW も含まれる。
 * VIEW は 外部キーやインデックスこそ張れないが、 SELECT 系なら TABLE と同様の操作ができる。
 * 更新可能 VIEW ならおそらく更新も可能である。
 *
 * ### メタ情報
 *
 * setTableColumn でスキーマの型やメタ情報を変更・追加することが出来る。
 * 設定されているスキーマ・メタ情報は `getTableColumnMetadata` メソッドで取得することができる。
 */
class Schema
{
    public const COLUMN_UPDATABLE = 1 << 2;
    public const COLUMN_REAL      = 1 << 3;

    private AbstractSchemaManager $schemaManger;

    private bool $debug;

    /** @var callable[] */
    private array $listeners;

    private CacheInterface $cache;

    /** @var string[] */
    private array $viewSource = [];

    /** @var string[] */
    private array $tableNames = [];

    /** @var Table[] */
    private array $tables = [];

    /** @var Column[][] */
    private array $tableColumns = [];

    /** @var callable[][] */
    private array $lazyTableColumns = [];

    /** @var ForeignKeyConstraint[][] */
    private array $foreignKeys = [];
    /** @var callable[] */
    private array $lazyForeignKeys = [];

    private array $foreignColumns = [];

    private string $foreignCacheId = '%s-%s-%s';

    /**
     * コンストラクタ
     */
    public function __construct(AbstractSchemaManager $schemaManger, array $listeners, CacheInterface $cache, bool $debug)
    {
        $this->debug = $debug;
        $this->schemaManger = $schemaManger;
        $this->listeners = array_replace([
            'onIntrospectTable' => fn() => null,
            'onAddForeignKey'   => fn() => null,
        ], $listeners);
        $this->cache = $cache;
    }

    private function _invalidateForeignCache(ForeignKeyConstraint $fkey)
    {
        [$ltable, $ftable] = first_keyvalue($this->getForeignTable($fkey));
        $cacheids = [
            sprintf($this->foreignCacheId, $ltable, $ftable, $fkey->getName()),
            sprintf($this->foreignCacheId, $ftable, $ltable, $fkey->getName()),
            sprintf($this->foreignCacheId, $ltable, $ftable, ''),
            sprintf($this->foreignCacheId, $ftable, $ltable, ''),
        ];
        array_unset($this->foreignColumns, $cacheids);
        $this->cache->deleteMultiple($cacheids);
    }

    /**
     * 一切のメタデータを削除する
     */
    public function refresh()
    {
        $this->viewSource = [];
        $this->tableNames = [];
        $this->tables = [];
        $this->tableColumns = [];
        $this->lazyTableColumns = [];
        $this->foreignKeys = [];
        $this->lazyForeignKeys = [];
        $this->foreignColumns = [];

        $this->cache->clear();
    }

    /**
     * ビューオブジェクトのデータソースを設定する
     */
    public function setViewSource(array $viewSource)
    {
        $this->viewSource = array_merge($this->viewSource, $viewSource);
    }

    /**
     * テーブルオブジェクトをメタデータに追加する
     */
    public function addTable(Table $table)
    {
        /// 一過性のものを想定しているのでこのメソッドで決してキャッシュ保存を行ってはならない

        $table_name = $table->getName();

        if ($this->hasTable($table_name)) {
            throw \Doctrine\DBAL\Schema\Exception\TableDoesNotExist::new($table_name);
        }

        $table = $this->listeners['onIntrospectTable']($table) ?? $table;

        $this->tableNames[] = $table_name;
        $this->tables[$table_name] = $table;
    }

    /**
     * テーブルのカラムを変更する
     *
     * 存在しないカラムも指定できる。
     * その場合、普通に追加されるので仮想カラムとして扱うことができる。
     *
     * null を渡すとカラムが削除される。
     */
    public function setTableColumn(string $table_name, string $column_name, $definitation)
    {
        $target_tables = array_merge([$table_name], array_keys($this->viewSource, $table_name, true));
        foreach ($target_tables as $table_name) {
            $this->lazyTableColumns[$table_name][$column_name] = function () use ($table_name, $column_name, $definitation) {
                $table = $this->getTable($table_name);

                if ($definitation === null) {
                    $table->dropColumn($column_name);
                    return null;
                }

                if ($definitation !== null && !is_array($definitation)) {
                    $definitation = ['select' => $definitation];
                }

                if ($table->hasColumn($column_name)) {
                    $column = $table->getColumn($column_name);
                    $definitation['virtual'] ??= $column->getPlatformOptions()['virtual'] ?? false;
                    $definitation['implicit'] ??= $column->getPlatformOptions()['implicit'] ?? ($definitation['virtual'] ? $definitation['implicit'] ?? false : true);
                }
                else {
                    $column = $table->addColumn($column_name, Types::INTEGER);
                    $definitation['virtual'] = true;
                    $definitation['implicit'] = $definitation['implicit'] ?? false;
                }

                $defaults = [
                    'lazy'   => false,
                    'type'   => null,
                    'select' => null,
                    'affect' => null,
                ];
                $definitation += array_intersect_key($column->getPlatformOptions(), $defaults) + $defaults;

                if ($definitation['select'] instanceof \Closure) {
                    $ref = new \ReflectionFunction($definitation['select']);
                    $params = $ref->getParameters();
                    $rtype = isset($params[0]) ? $params[0]->getType() : null;
                    if ($rtype instanceof \ReflectionNamedType && is_a($rtype->getName(), Database::class, true)) {
                        $definitation['lazy'] = true;
                    }
                }

                $type = array_unset($definitation, 'type');
                if ($type) {
                    $column->setType($type instanceof Type ? $type : Type::getType($type));
                }
                foreach ($definitation as $name => $value) {
                    $column->setPlatformOption($name, $value);
                }

                return $column;
            };
        }
    }

    /**
     * 外部キーのメタデータを設定する
     *
     * メタデータと言いつつも単に配列を紐づけるだけに過ぎない。
     * 仮に dbal の方で setOption メソッドが実装されたら不要となる。
     *
     * 原則的に好きに使ってよいが dbal 組み込みと joinable キーは「外部キー結合に使われるか？」の内部判定で使用されるので留意。
     */
    public function setForeignKeyMetadata(string|ForeignKeyConstraint $fkey, array $metadata)
    {
        if (is_string($fkey)) {
            $fkey = $this->getForeignKeys()[$fkey] ?? throw new \InvalidArgumentException("undefined foreign key '$fkey'.");
        }
        (function () use ($metadata) {
            // for compatible
            $option_name = isset($this->options) ? 'options' : '_options';
            return $this->$option_name = array_replace($this->$option_name, $metadata);
        })->bindTo($fkey, ForeignKeyConstraint::class)();
    }

    /**
     * テーブルが存在するなら true を返す
     */
    public function hasTable(string $table_name): bool
    {
        //$tables = array_flip($this->getTableNames());
        //return isset($tables[$table_name]);
        return in_array($table_name, $this->getTableNames(), true);
    }

    /**
     * テーブル名一覧を取得する
     */
    public function getTableNames(): array
    {
        if (!$this->tableNames) {
            $this->tableNames = cache_fetch($this->cache, 'Schema-table_names', function () {
                $table_names = $this->schemaManger->listTableNames();

                $views = array_each($this->schemaManger->listViews(), function (&$carry, View $view) {
                    $ns = $view->getNamespaceName();
                    if ($ns === null || $ns === 'public') {
                        $carry[] = $view->getShortestName($ns);
                    }
                }, []);

                return array_merge($table_names, $views);
            });
        }
        return $this->tableNames;
    }

    /**
     * テーブルオブジェクトを取得する
     */
    public function getTable(string $table_name): Table
    {
        if (!isset($this->tables[$table_name])) {
            if (!$this->hasTable($table_name)) {
                throw \Doctrine\DBAL\Schema\Exception\TableDoesNotExist::new($table_name);
            }

            $this->tables[$table_name] = cache_fetch($this->cache, "Table-$table_name", function () use ($table_name) {
                if ($this->schemaManger->tablesExist([$table_name])) {
                    $table = $this->schemaManger->introspectTable($table_name);
                }
                // optional for ryunosuke/dbal
                elseif (method_exists($this->schemaManger, 'introspectViewAsTable')) {
                    $table = $this->schemaManger->introspectViewAsTable($table_name); // @codeCoverageIgnore for compatible
                }
                // https://github.com/doctrine/dbal/issues/5821
                elseif (class_exists(\Doctrine\DBAL\Exception::class)) {
                    // @codeCoverageIgnoreStart for compatible
                    $columns = \Closure::bind(function ($table) {
                        $database = $this->_conn->getDatabase();
                        if ($this->_platform instanceof SQLServerPlatform) {
                            $sql = <<<SQL
                                SELECT 
                                    c.name                  AS name,
                                    type_name(user_type_id) AS type,
                                    c.max_length            AS length,
                                    ~c.is_nullable          AS notnull,
                                    NULL                    AS "default",
                                    c.scale                 AS scale,
                                    c.precision             AS precision,
                                    0                       AS autoincrement,
                                    c.collation_name        AS collation,
                                    NULL                    AS comment
                                FROM sys.columns c
                                JOIN sys.views v ON v.object_id = c.object_id
                                WHERE SCHEMA_NAME(v.schema_id) = SCHEMA_NAME() AND v.name = {$this->_platform->quoteStringLiteral($table)}
                            SQL;
                        }
                        else {
                            /** @noinspection PhpDeprecationInspection */
                            $sql = $this->_platform->getListTableColumnsSQL($table, $database);
                        }
                        $tableColumns = $this->_conn->fetchAllAssociative($sql);
                        if ($this->_platform instanceof SqlitePlatform) {
                            array_walk($tableColumns, function (&$column) {
                                // sqlite は view だけの列の型が得られない（テスト用だし汎用的な VARCHAR で代替する）
                                if (!strlen($column['type'])) {
                                    $column['type'] = 'VARCHAR(255)';
                                }
                            });
                        }
                        return $this->_getPortableTableColumnList($table, $database, $tableColumns);
                    }, $this->schemaManger, $this->schemaManger)($table_name);
                    $table = new Table($table_name, $columns);
                    // @codeCoverageIgnoreEnd
                }
                if (!isset($table)) {
                    throw \Doctrine\DBAL\Schema\Exception\TableDoesNotExist::new($table_name); // @codeCoverageIgnore for compatible
                }
                $table->setSchemaConfig($this->schemaManger->createSchemaConfig());

                return $this->listeners['onIntrospectTable']($table) ?? $table;
            });
        }
        return $this->tables[$table_name];
    }

    /**
     * パターン一致したテーブルオブジェクトを取得する
     *
     * @return Table[] テーブルオブジェクト配列
     */
    public function getTables(string|array $table_pattern = []): array
    {
        $table_names = $this->getTableNames();
        $table_pattern = (array) ($table_pattern ?: $table_names);

        $positive = $negative = [];
        foreach ($table_pattern as $pattern) {
            $pattern = trim($pattern);
            if (($pattern[0] ?? '') !== '!') {
                $positive[] = $pattern;
            }
            else {
                $negative[] = substr($pattern, 1);
            }
        }

        $result = [];
        foreach ($table_names as $table_name) {
            if ((!$positive || fnmatch_or($positive, $table_name)) && (!$negative || !fnmatch_or($negative, $table_name))) {
                $result[$table_name] = $this->getTable($table_name);
            }
        }
        return $result;
    }

    /**
     * テーブルのカラムオブジェクトを取得する
     *
     * @return Column[] テーブルのカラムオブジェクト配列
     */
    public function getTableColumns(string $table_name, null|int|callable $filter = null): array
    {
        if (isset($this->viewSource[$table_name]) && !$this->hasTable($table_name)) {
            $table_name = $this->viewSource[$table_name];
        }
        if (!isset($this->tableColumns[$table_name])) {
            $this->tableColumns[$table_name] = array_each($this->getTable($table_name)->getColumns(), function (&$carry, Column $column) {
                $carry[$column->getName()] = $column;
            }, []);
        }
        foreach ($this->lazyTableColumns[$table_name] ?? [] as $name => $lazy) {
            unset($this->lazyTableColumns[$table_name][$name]);
            $column = $lazy();
            if ($column === null) {
                unset($this->tableColumns[$table_name][$name]);
            }
            else {
                $this->tableColumns[$table_name][$name] = $column;
            }
        }
        if ($filter === null) {
            return $this->tableColumns[$table_name];
        }
        return array_filter($this->tableColumns[$table_name], function (Column $column, $name) use ($filter) {
            if (is_callable($filter)) {
                return $filter($column, $name);
            }

            $platformOptions = $column->getPlatformOptions();
            if ($filter & static::COLUMN_REAL) {
                if ($platformOptions['virtual'] ?? false) {
                    return false;
                }
            }
            if ($filter & static::COLUMN_UPDATABLE) {
                if (($platformOptions['virtual'] ?? false) && !isset($platformOptions['affect'])) {
                    return false;
                }
                // for ryunosuke/dbal
                if (isset($platformOptions['generation'])) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * テーブルカラムの表現を返す
     */
    public function getTableColumnExpression(string $table_name, string $column_name, string $type, ...$args)
    {
        $column = $this->getTableColumns($table_name)[$column_name] ?? null;
        if ($column === null) {
            return null;
        }
        if (!$column->hasPlatformOption($type)) {
            return null;
        }

        $expression = $column->getPlatformOption($type);
        if ($type === 'select' && $column->hasPlatformOption('lazy') && $column->getPlatformOption('lazy')) {
            $expression = $expression(...$args);
            $column->setPlatformOption('lazy', false);
            $column->setPlatformOption($type, $expression);
        }
        return $expression;
    }

    /**
     * テーブルの主キーインデックスオブジェクトを取得する
     */
    public function getTablePrimaryKey(string $table_name): ?Index
    {
        $table_name = $this->viewSource[$table_name] ?? $table_name;
        return $this->getTable($table_name)->getPrimaryKey();
    }

    /**
     * テーブルの主キーカラムオブジェクトを取得する
     *
     * @return Column[] 主キーカラムオブジェクト配列
     */
    public function getTablePrimaryColumns(string $table_name): array
    {
        $pkey = $this->getTablePrimaryKey($table_name);
        if ($pkey === null) {
            return [];
        }
        return array_pickup($this->getTableColumns($table_name), $pkey->getColumns());
    }

    /**
     * テーブルの（主キーを除く）ユニークキーカラムオブジェクトを取得する
     *
     * @return Column[] ユニークキーカラムオブジェクト配列
     */
    public function getTableUniqueColumns(string $table_name, string $ukname = ''): array
    {
        $table_name = $this->viewSource[$table_name] ?? $table_name;
        $table = $this->getTable($table_name);

        if (strcasecmp($ukname, 'PRIMARY') === 0) {
            $uniqueKey = $table->getPrimaryKey();
        }
        elseif (strlen($ukname ?? '')) {
            $uniqueKey = $table->getIndex($ukname);
        }
        else {
            foreach ($table->getIndexes() as $index) {
                if (!$index->isPrimary() && $index->isUnique()) {
                    $uniqueKey = $index;
                    break;
                }
            }
        }

        if (!isset($uniqueKey) || !$uniqueKey->isUnique()) {
            throw new \InvalidArgumentException("Unique Index is not found or not unique");
        }

        return array_pickup($this->getTableColumns($table_name), $uniqueKey->getColumns());
    }

    /**
     * テーブルのオートインクリメントカラムを取得する
     */
    public function getTableAutoIncrement(string $table_name): ?Column
    {
        $pcols = $this->getTablePrimaryColumns($table_name);
        foreach ($pcols as $pcol) {
            if ($pcol->getAutoincrement()) {
                return $pcol;
            }
        }

        return null;
    }

    /**
     * テーブルの外部キーオブジェクトを取得する
     *
     * @return ForeignKeyConstraint[] テーブルの外部キーオブジェクト配列
     */
    public function getTableForeignKeys(string $table_name): array
    {
        $table_name = $this->viewSource[$table_name] ?? $table_name;

        if (!isset($this->foreignKeys[$table_name])) {
            // doctrine が制約名を小文字化してるみたいなのでオリジナルでマップする
            $this->foreignKeys[$table_name] = array_each($this->getTable($table_name)->getForeignKeys(), function (&$fkeys, ForeignKeyConstraint $fkey) {
                $fkeys[$fkey->getName()] = $fkey;
            }, []);
        }
        $lazykeys = $this->lazyForeignKeys[$table_name] ?? [];
        $this->lazyForeignKeys[$table_name] = [];
        foreach ($lazykeys ?? [] as $fk) {
            $this->addForeignKey(...$fk());
        }
        return $this->foreignKeys[$table_name];
    }

    /**
     * 外部キーがループ構造を持つか返す
     */
    public function isCircularForeignKey(ForeignKeyConstraint $fkey): bool
    {
        $main = function (ForeignKeyConstraint $fkey, array $history) use (&$main) {
            if (isset($history[$fkey->getName()])) {
                return true;
            }
            $history[$fkey->getName()] = $fkey;

            // ループ検出なのでどこから始めてもどちらの方向でもよい
            foreach ($this->getTableForeignKeys($fkey->getForeignTableName()) as $fkey2) {
                if ($main($fkey2, $history)) {
                    return true;
                }
            }
            return false;
        };
        return $main($fkey, []);
    }

    /**
     * テーブル間外部キーオブジェクトを取得する
     *
     * 端的に言えば $from_table から $to_table へ向かう外部キーを取得する。ただし
     *
     * - $from_table の指定がない場合は $to_table へ向かう全ての外部キー
     * - $to_table の指定もない場合は データベース上に存在する全ての外部キー
     *
     * を取得する。
     *
     * @return ForeignKeyConstraint[] 外部キーオブジェクト配列
     */
    public function getForeignKeys(?string $to_table = null, ?string $from_table = null): array
    {
        if ($from_table === null) {
            $from_table = $this->getTableNames();
        }

        $result = [];
        foreach (arrayize($from_table) as $from) {
            $fkeys = $this->getTableForeignKeys($from);
            foreach ($fkeys as $fk) {
                if ($to_table === null || $to_table === ($this->viewSource[$fk->getForeignTableName()] ?? $fk->getForeignTableName())) {
                    $result[$fk->getName()] = $fk;
                }
            }
        }
        return $result;
    }

    /**
     * 外部キーから関連テーブルを取得する
     *
     * @return array [fromTable => $toTable] の配列
     */
    public function getForeignTable(string|ForeignKeyConstraint $fkey): array
    {
        $fkeyname = $fkey instanceof ForeignKeyConstraint ? $fkey->getName() : $fkey;
        foreach ($this->getTableNames() as $from) {
            $fkeys = $this->getTableForeignKeys($from);
            if (isset($fkeys[$fkeyname])) {
                return [$from => $fkeys[$fkeyname]->getForeignTableName()];
            }
        }
        return [];
    }

    /**
     * テーブル間を結ぶ外部キーカラムを取得する
     *
     * $fkeyname 未指定時は唯一の外部キー（複数ある場合は例外）。確定した外部キーオブジェクトが格納される。
     * $direction キー（$table_name1 -> $table_name2 なら true）の方向が格納される
     */
    public function getForeignColumns(string $table_name1, string $table_name2, ?string &$fkeyname = null, ?bool &$direction = null): array
    {
        $table_name1 = $this->viewSource[$table_name1] ?? $table_name1;
        $table_name2 = $this->viewSource[$table_name2] ?? $table_name2;
        $direction = null;

        if (!$this->hasTable($table_name1) || !$this->hasTable($table_name2)) {
            return [];
        }

        $cacheid = sprintf($this->foreignCacheId, $table_name1, $table_name2, $fkeyname);
        if (!isset($this->foreignColumns[$cacheid])) {
            // 遅延外部キーのためにここで呼んでおく（cache_fetch でキャッシュが使われた場合、アクセスされる機会が失われる）
            $this->getTableForeignKeys($table_name1);
            $this->getTableForeignKeys($table_name2);

            $this->foreignColumns[$cacheid] = cache_fetch($this->cache, $cacheid, function () use ($table_name1, $table_name2, $fkeyname) {
                $fkeys = [];
                $fkeys += $this->getForeignKeys($table_name1, $table_name2);
                $fkeys += $this->getForeignKeys($table_name2, $table_name1);
                $fcount = count($fkeys);

                // 外部キーがなくても中間テーブルを介した関連があるかもしれない
                if ($fcount === 0) {
                    $ikeys = $this->getIndirectlyColumns($table_name1, $table_name2, $fkey);
                    if ($ikeys) {
                        return ['direction' => false, 'columns' => $ikeys, 'fkey' => $fkey->getName()];
                    }
                    $ikeys = $this->getIndirectlyColumns($table_name2, $table_name1, $fkey);
                    if ($ikeys) {
                        return ['direction' => true, 'columns' => array_flip($ikeys), 'fkey' => $fkey->getName()];
                    }
                    return ['direction' => null, 'columns' => [], 'fkey' => null];
                }

                // キー指定がないなら自動検出、あるならそれを取得
                if ($fkeyname === null) {
                    // 2個以上は joinable 指定されているものを使う
                    $joinablefkeys = array_filter($fkeys, fn(ForeignKeyConstraint $fkey) => $fkey->getOptions()['joinable'] ?? true);
                    if (count($joinablefkeys) === 0) {
                        throw new \UnexpectedValueException("joinable foreign key is not exists between $table_name1<->$table_name2 .");
                    }
                    if (count($joinablefkeys) >= 2) {
                        throw new \UnexpectedValueException('ambiguous foreign keys ' . implode(', ', array_keys($fkeys)) . '.');
                    }
                    $fkey = reset($joinablefkeys);
                }
                else {
                    if (!isset($fkeys[$fkeyname])) {
                        throw new \UnexpectedValueException("foreign key '$fkeyname' is not exists between $table_name1<->$table_name2 .");
                    }
                    $fkey = $fkeys[$fkeyname];
                }

                // 外部キーカラムを順序に応じてセットして返す
                if ($fkey->getForeignTableName() === $table_name1) {
                    $direction = false;
                    $keys = $fkey->getLocalColumns();
                    $vals = $fkey->getForeignColumns();
                }
                else {
                    $direction = true;
                    $keys = $fkey->getForeignColumns();
                    $vals = $fkey->getLocalColumns();
                }
                return ['direction' => $direction, 'columns' => array_combine($keys, $vals), 'fkey' => $fkey->getName()];
            }, $this->debug ? 1 : null);
        }

        $direction = $this->foreignColumns[$cacheid]['direction'];
        $main_table = $direction ? $table_name1 : $table_name2;
        $fkeyname = $this->getTableForeignKeys($main_table)[$this->foreignColumns[$cacheid]['fkey']] ?? null;
        return $this->foreignColumns[$cacheid]['columns'];
    }

    /**
     * テーブルに外部キーを追加する
     *
     * このメソッドで追加された外部キーはできるだけ遅延して追加され、必要になるまでは実行されない。
     */
    public function addForeignKeyLazy(string $localTable, string $foreignTable, string|array $fkdata, ?string $fkname = null): string
    {
        $fkname = $fkname ?? ($localTable . '_' . $foreignTable . '_' . count($this->lazyForeignKeys[$localTable] ?? []));
        $this->lazyForeignKeys[$localTable][$fkname] = function () use ($localTable, $foreignTable, $fkdata, $fkname) {
            $fkdata = arrayize($fkdata);
            $options = array_unset($fkdata, 'options', []) + ['virtual' => true];
            $columnsMap = array_rekey($fkdata, fn($k, $v) => trim(is_int($k) ? $v : $k));
            $fk = new ForeignKeyConstraint(array_keys($columnsMap), $foreignTable, array_values($columnsMap), $fkname, $options);
            return [$fk, $localTable];
        };
        return $fkname;
    }

    /**
     * テーブルに外部キーを追加する
     *
     * このメソッドで追加された外部キーはデータベースに反映されるわけでもないし、キャッシュにも乗らない。
     * あくまで「アプリ的にちょっとリレーションが欲しい」といったときに使用する想定。
     */
    public function addForeignKey(ForeignKeyConstraint $fkey, ?string $lTable = null): ForeignKeyConstraint
    {
        $lTable ??= array_key_first($this->getForeignTable($fkey));
        if ($lTable === null) {
            throw new \InvalidArgumentException('$fkey\'s localTable is not set.');
        }

        $fTable = $fkey->getForeignTableName();
        $lCols = $fkey->getLocalColumns();
        $fCols = $fkey->getForeignColumns();

        // カラム存在チェック
        if (count($lCols) !== count(array_pickup($this->getTableColumns($lTable), $lCols))) {
            throw new \InvalidArgumentException("undefined column for $lTable.");
        }
        if (count($fCols) !== count(array_pickup($this->getTableColumns($fTable), $fCols))) {
            throw new \InvalidArgumentException("undefined column for $fTable.");
        }

        // テーブルとカラムが一致するものがあるなら例外
        $fkeys = $this->getTableForeignKeys($lTable);
        foreach ($fkeys as $fk) {
            if ($fTable === $fk->getForeignTableName()) {
                if ($lCols === $fk->getLocalColumns() && $fCols === $fk->getForeignColumns()) {
                    throw new \UnexpectedValueException('foreign key already defined same.');
                }
            }
        }

        // キャッシュしてそれを返す
        $this->foreignKeys[$lTable][$fkey->getName()] = $fkey;
        $this->_invalidateForeignCache($fkey);

        $fkey = $this->listeners['onAddForeignKey']($fkey, $this->getTable($lTable)) ?? $fkey;

        return $fkey;
    }

    /**
     * テーブルの外部キーを削除する
     *
     * このメソッドで削除された外部キーはデータベースに反映されるわけでもないし、キャッシュにも乗らない。
     * あくまで「アプリ的にちょっとリレーションを外したい」といったときに使用する想定。
     */
    public function ignoreForeignKey(ForeignKeyConstraint|string $fkey, ?string $lTable = null): ForeignKeyConstraint
    {
        // 文字列指定ならオブジェクト化
        if (is_string($fkey)) {
            $all = $this->getForeignKeys();
            if (!isset($all[$fkey])) {
                throw new \InvalidArgumentException("undefined foreign key '$fkey'.");
            }
            $fkey = $all[$fkey];
        }

        $lTable ??= array_key_first($this->getForeignTable($fkey));
        if ($lTable === null) {
            throw new \InvalidArgumentException('$fkey\'s localTable is not set.');
        }

        $fTable = $fkey->getForeignTableName();
        $lCols = $fkey->getLocalColumns();
        $fCols = $fkey->getForeignColumns();

        // テーブルとカラムが一致するものを削除
        $deleted = null;
        $fkeys = $this->getTableForeignKeys($lTable);
        foreach ($fkeys as $fname => $fk) {
            if ($fTable === $fk->getForeignTableName()) {
                if ($lCols === $fk->getLocalColumns() && $fCols === $fk->getForeignColumns()) {
                    $deleted = $fkeys[$fname];
                    unset($fkeys[$fname]);
                }
            }
        }

        // 消せなかったら例外
        if (!$deleted) {
            throw new \InvalidArgumentException('matched foreign key is not found.');
        }

        // 再キャッシュすれば「なにを無視するか」を覚えておく必要がない
        $this->_invalidateForeignCache($deleted);
        $this->foreignKeys[$lTable] = $fkeys;

        return $deleted;
    }

    /**
     * 外部キーから [table => [columnA => [table => [column => FK]]]] な配列を生成する
     *
     * 外部キーがループしてると導出が困難なため、木構造ではなく単純なフラット配列にしてある。
     * （自身へアクセスすれば木構造的に辿ることは可能）。
     *
     * @return array [table => [columnA => [table => [column => FK]]]]
     */
    public function getRelation(): array
    {
        return array_each($this->getForeignKeys(), function (&$carry, ForeignKeyConstraint $fkey) {
            [$ltable, $ftable] = first_keyvalue($this->getForeignTable($fkey));
            $lcolumns = $fkey->getLocalColumns();
            $fcolumns = $fkey->getForeignColumns();
            foreach ($fcolumns as $n => $fcolumn) {
                $carry[$ltable][$lcolumns[$n]][$ftable][$fcolumn] = $fkey->getName();
                foreach (array_keys($this->viewSource, $ftable, true) as $vtable) {
                    $carry[$ltable][$lcolumns[$n]][$vtable][$fcolumn] = $fkey->getName();
                }
            }
        }, []);
    }

    /**
     * 中間テーブルを介さずに結合できるカラムを返す
     *
     * $fkey 確定した外部キーが格納される。
     *
     * @return array [lcolmun => fcolumn]
     */
    public function getIndirectlyColumns(string $to_table, string $from_table, ?ForeignKeyConstraint &$fkey = null)
    {
        $result = [];
        foreach ($this->getTableForeignKeys($from_table) as $fkey2) {
            foreach ($fkey2->getLocalColumns() as $lcolumn) {
                // 外部キーカラムを一つづつ辿って
                $routes = $this->followColumnName($to_table, $from_table, $lcolumn);

                // 経路は問わず最終的に同じカラムに行き着く（unique して1）なら加える
                $columns = array_unique($routes);
                if (count($columns) === 1) {
                    $result[$lcolumn] = reset($columns);
                    $fkey = $fkey2;
                }
            }
        }
        return $result;
    }

    /**
     * 外部キーを辿って「テーブルA.カラムX」から「テーブルB.カラムY」を導出
     *
     * 返り値のキーには辿ったパス（テーブル）が / 区切りで格納される。
     */
    public function followColumnName(string $to_table, string $from_table, string $from_column): array
    {
        $relations = $this->getRelation();

        $result = [];
        $trace = function ($from_table, $from_column) use (&$trace, &$result, $to_table, $relations) {
            if (!isset($relations[$from_table][$from_column])) {
                return;
            }
            foreach ($relations[$from_table][$from_column] as $p_table => $c_columns) {
                foreach ($c_columns as $cc => $dummy) {
                    if ($p_table === $to_table) {
                        $result[$from_table . '/' . $p_table] = $cc;
                    }
                    $trace($p_table, $cc);
                }
            }
        };
        $trace($from_table, $from_column);
        return $result;
    }
}
