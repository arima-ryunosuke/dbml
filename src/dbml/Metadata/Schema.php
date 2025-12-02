<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\View;
use Doctrine\DBAL\Types\Type;
use Psr\SimpleCache\CacheInterface;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_pickup;
use function ryunosuke\dbml\array_rekey;
use function ryunosuke\dbml\array_unset;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\cache_fetch;
use function ryunosuke\dbml\first_keyvalue;
use function ryunosuke\dbml\fnmatch_or;
use function ryunosuke\dbml\optional;

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
 * （ただし、現在のところこのメタ情報を活用している機能は非常に少なく、実質 anywhere のみ）。
 */
class Schema
{
    public const COLUMN_UPDATABLE = 1 << 2;
    public const COLUMN_REAL      = 1 << 3;

    /** @var AbstractSchemaManager */
    private $schemaManger;

    /** @var callable[] */
    private $listeners;

    /** @var CacheInterface */
    private $cache;

    /** @var string[] */
    private $tableNames = [];

    /** @var Table[] */
    private $tables = [];

    /** @var Column[][] */
    private $tableColumns = [];

    /** @var ForeignKeyConstraint[][] */
    private $foreignKeys = [], $lazyForeignKeys = [];

    /** @var array */
    private $foreignColumns = [];

    /** @var string */
    private $foreignCacheId = '%s-%s-%s';

    /**
     * コンストラクタ
     *
     * @param AbstractSchemaManager $schemaManger スキーママネージャ
     * @param callable[] $listeners イベントリスナ
     * @param CacheInterface $cache キャッシュプロバイダ
     */
    public function __construct(AbstractSchemaManager $schemaManger, $listeners, $cache)
    {
        $this->schemaManger = $schemaManger;
        $this->listeners = $listeners;
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
        $this->tableNames = [];
        $this->tables = [];
        $this->tableColumns = [];
        $this->foreignKeys = [];
        $this->lazyForeignKeys = [];
        $this->foreignColumns = [];

        $this->cache->clear();
    }

    /**
     * テーブルオブジェクトをメタデータに追加する
     *
     * @param Table $table 追加するテーブルオブジェクト
     */
    public function addTable($table)
    {
        /// 一過性のものを想定しているのでこのメソッドで決してキャッシュ保存を行ってはならない

        $table_name = $table->getName();

        if ($this->hasTable($table_name)) {
            throw SchemaException::tableAlreadyExists($table_name);
        }

        $table = ($this->listeners['onIntrospectTable'] ?? fn() => null)($table) ?? $table;

        $this->tableNames[] = $table_name;
        $this->tables[$table_name] = $table;
    }

    /**
     * テーブルのカラムを変更する
     *
     * 存在しないカラムも指定できる。
     * その場合、普通に追加されるので仮想カラムとして扱うことができる。
     *
     * @param string $table_name テーブル名
     * @param string $column_name カラム名
     * @param array|null $definitation カラム定義。 null を渡すと削除になる
     * @return Column|null 定義されたカラム
     */
    public function setTableColumn($table_name, $column_name, ?array $definitation)
    {
        /// 一過性のものを想定しているのでこのメソッドで決してキャッシュ保存を行ってはならない

        $table = $this->getTable($table_name);

        if ($definitation === null) {
            $table->dropColumn($column_name);
            unset($this->tableColumns[$table_name][$column_name]);
            return null;
        }

        if ($table->hasColumn($column_name)) {
            $column = $table->getColumn($column_name);
            $definitation['virtual'] ??= $column->getPlatformOptions()['virtual'] ?? false;
            $definitation['implicit'] ??= $column->getPlatformOptions()['implicit'] ?? ($definitation['virtual'] ? $definitation['implicit'] ?? false : true);
        }
        else {
            $column = $table->addColumn($column_name, 'integer');
            $definitation['virtual'] = true;
            $definitation['implicit'] = $definitation['implicit'] ?? false;
        }

        $type = array_unset($definitation, 'type');
        if ($type) {
            $column->setType($type instanceof Type ? $type : Type::getType($type));
        }
        foreach ($definitation as $name => $value) {
            $column->setPlatformOption($name, $value);
        }

        // 再キャッシュ
        $columns = $this->getTableColumns($table_name);
        $this->tableColumns[$table_name] = array_merge($columns, [$column_name => $column]);

        return $column;
    }

    /**
     * 外部キーのメタデータを設定する
     *
     * メタデータと言いつつも単に配列を紐づけるだけに過ぎない。
     * 仮に dbal の方で setOption メソッドが実装されたら不要となる。
     *
     * 原則的に好きに使ってよいが dbal 組み込みと joinable キーは「外部キー結合に使われるか？」の内部判定で使用されるので留意。
     *
     * @param string|ForeignKeyConstraint $fkey 外部キー
     * @param array $metadata メタデータ
     */
    public function setForeignKeyMetadata($fkey, array $metadata)
    {
        $fkey = $fkey instanceof ForeignKeyConstraint ? $fkey : $this->getForeignKeys()[$fkey] ?? null;
        if (!isset($fkey)) {
            throw new \InvalidArgumentException("undefined foreign key '$fkey'.");
        }
        (fn() => $this->_options = array_replace($this->_options, $metadata))->bindTo($fkey, ForeignKeyConstraint::class)();
    }

    /**
     * テーブルが存在するなら true を返す
     *
     * @param string $table_name 調べるテーブル名
     * @return bool テーブルが存在するなら true
     */
    public function hasTable($table_name)
    {
        //$tables = array_flip($this->getTableNames());
        //return isset($tables[$table_name]);
        return in_array($table_name, $this->getTableNames(), true);
    }

    /**
     * テーブル名一覧を取得する
     *
     * @return string[] テーブル名配列
     */
    public function getTableNames()
    {
        if (!$this->tableNames) {
            $this->tableNames = cache_fetch($this->cache, 'Schema-table_names', function () {
                $table_names = $this->schemaManger->listTableNames();

                /** @noinspection PhpDeprecationInspection */
                $paths = array_fill_keys($this->schemaManger->getSchemaSearchPaths(), '');
                $views = array_each($this->schemaManger->listViews(), function (&$carry, View $view) use ($paths) {
                    $ns = $view->getNamespaceName();
                    if ($ns === null || isset($paths[$ns])) {
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
     *
     * @param string $table_name 取得したいテーブル名
     * @return Table テーブルオブジェクト
     */
    public function getTable($table_name)
    {
        if (!isset($this->tables[$table_name])) {
            if (!$this->hasTable($table_name)) {
                throw SchemaException::tableDoesNotExist($table_name);
            }

            $this->tables[$table_name] = cache_fetch($this->cache, "Table-$table_name", function () use ($table_name) {
                // doctrine 3.4 から view のカラムが得られなくなっている？ ようなのでかなりアドホックだが暫定対応
                if ($this->schemaManger->tablesExist([$table_name])) {
                    $table = $this->schemaManger->introspectTable($table_name);
                }
                else {
                    $columns = \Closure::bind(function ($table) {
                        $database = $this->_conn->getDatabase();
                        $cplatform = new CompatiblePlatform($this->_platform);
                        /** @noinspection PhpDeprecationInspection */
                        $sql = $cplatform->getListTableColumnsSQL($table, $database);
                        $tableColumns = $this->_conn->fetchAllAssociative($sql);
                        return $this->_getPortableTableColumnList($table, $database, $tableColumns);
                    }, $this->schemaManger, $this->schemaManger)($table_name);
                    $table = new Table($table_name, $columns);
                }
                $table->setSchemaConfig($this->schemaManger->createSchemaConfig());

                return ($this->listeners['onIntrospectTable'] ?? fn() => null)($table) ?? $table;
            });
        }
        return $this->tables[$table_name];
    }

    /**
     * パターン一致したテーブルオブジェクトを取得する
     *
     * @param string|array $table_pattern 取得したいテーブルパターン
     * @return Table[] テーブルオブジェクト配列
     */
    public function getTables($table_pattern = [])
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
     * @param string $table_name 取得したいテーブル名
     * @param null|int|callable $filter 取得条件
     * @return Column[] テーブルのカラムオブジェクト配列
     */
    public function getTableColumns($table_name, $filter = null)
    {
        if (!isset($this->tableColumns[$table_name])) {
            $this->tableColumns[$table_name] = $this->getTable($table_name)->getColumns();
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
     *
     * @param string $table_name 取得したいテーブル名
     * @param string $column_name 取得したいカラム名
     * @param string $type select or affect
     * @param array $args 遅延カラムだった場合のコールバック引数
     * @return mixed カラム表現
     */
    public function getTableColumnExpression($table_name, $column_name, $type, ...$args)
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
     *
     * @param string $table_name 取得したいテーブル名
     * @return Index 主キーインデックスオブジェクト
     */
    public function getTablePrimaryKey($table_name)
    {
        return $this->getTable($table_name)->getPrimaryKey();
    }

    /**
     * テーブルの主キーカラムオブジェクトを取得する
     *
     * @param string $table_name 取得したいテーブル名
     * @return Column[] 主キーカラムオブジェクト配列
     */
    public function getTablePrimaryColumns($table_name)
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
     * @param string $table_name 取得したいテーブル名
     * @param string $ukname 取得したいユニークキー名（PRIMARY は特別扱いで主キーを返し、空文字は最初のキーを返す。将来的に nullable になるかもしれない）
     * @return Column[] ユニークキーカラムオブジェクト配列
     */
    public function getTableUniqueColumns($table_name, $ukname = '')
    {
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
     *
     * @param string $table_name 取得したいテーブル名
     * @return Column オートインクリメントカラムがあるならそのオブジェクト、無いなら null
     */
    public function getTableAutoIncrement($table_name)
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
     * @param string $table_name 取得したいテーブル名
     * @return ForeignKeyConstraint[] テーブルの外部キーオブジェクト配列
     */
    public function getTableForeignKeys($table_name)
    {
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
     * @param string|null $to_table 向かうテーブル名（被参照外部キー）
     * @param string|null $from_table 元テーブル名（参照外部キー）
     * @return ForeignKeyConstraint[] 外部キーオブジェクト配列
     */
    public function getForeignKeys($to_table = null, $from_table = null)
    {
        if ($from_table === null) {
            $from_table = $this->getTableNames();
        }

        $result = [];
        foreach (arrayize($from_table) as $from) {
            $fkeys = $this->getTableForeignKeys($from);
            foreach ($fkeys as $fk) {
                if ($to_table === null || $to_table === $fk->getForeignTableName()) {
                    $result[$fk->getName()] = $fk;
                }
            }
        }
        return $result;
    }

    /**
     * 外部キーから関連テーブルを取得する
     *
     * @param string|ForeignKeyConstraint $fkey 外部キー
     * @return array [fromTable => $toTable] の配列
     */
    public function getForeignTable($fkey)
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
     * @param string $table_name1 テーブル名1
     * @param string $table_name2 テーブル名2
     * @param ?string $fkeyname 制約名。未指定時は唯一の外部キー（複数ある場合は例外）。確定した外部キーオブジェクトが格納される
     * @param ?bool $direction キー（$table_name1 -> $table_name2 なら true）の方向が格納される
     * @return array [table1_column => table2_column]
     */
    public function getForeignColumns($table_name1, $table_name2, &$fkeyname = null, &$direction = null)
    {
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
            });
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
     *
     * @param string $localTable 外部キー定義テーブル名
     * @param string $foreignTable 参照先テーブル名
     * @param string|array $fkdata 外部キー情報
     * @param string|null $fkname 外部キー名。省略時は自動命名
     * @return string 追加する外部キー名
     */
    public function addForeignKeyLazy($localTable, $foreignTable, $fkdata, $fkname = null)
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
     *
     * @param ForeignKeyConstraint $fkey 追加する外部キーオブジェクト
     * @param string $lTable 追加するテーブル名
     * @return ForeignKeyConstraint 追加した外部キーオブジェクト
     */
    public function addForeignKey($fkey, $lTable = null)
    {
        /** @noinspection PhpDeprecationInspection */
        $lTable ??= optional($fkey->getLocalTable())->getName();
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

        return $fkey;
    }

    /**
     * テーブルの外部キーを削除する
     *
     * このメソッドで削除された外部キーはデータベースに反映されるわけでもないし、キャッシュにも乗らない。
     * あくまで「アプリ的にちょっとリレーションを外したい」といったときに使用する想定。
     *
     * @param ForeignKeyConstraint|string $fkey 削除する外部キーオブジェクトあるいは外部キー文字列
     * @param string $lTable 削除するテーブル名
     * @return ForeignKeyConstraint 削除した外部キーオブジェクト
     */
    public function ignoreForeignKey($fkey, $lTable = null)
    {
        // 文字列指定ならオブジェクト化
        if (is_string($fkey)) {
            $all = $this->getForeignKeys();
            if (!isset($all[$fkey])) {
                throw new \InvalidArgumentException("undefined foreign key '$fkey'.");
            }
            $fkey = $all[$fkey];
        }

        /** @noinspection PhpDeprecationInspection */
        $lTable ??= optional($fkey->getLocalTable())->getName();
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
    public function getRelation()
    {
        return array_each($this->getForeignKeys(), function (&$carry, ForeignKeyConstraint $fkey) {
            [$ltable, $ftable] = first_keyvalue($this->getForeignTable($fkey));
            $lcolumns = $fkey->getLocalColumns();
            $fcolumns = $fkey->getForeignColumns();
            foreach ($fcolumns as $n => $fcolumn) {
                $carry[$ltable][$lcolumns[$n]][$ftable][$fcolumn] = $fkey->getName();
            }
        }, []);
    }

    /**
     * 中間テーブルを介さずに結合できるカラムを返す
     *
     * @param string $to_table 向かうテーブル名（被参照外部キー）
     * @param string $from_table 元テーブル名（参照外部キー）
     * @param ?ForeignKeyConstraint $fkey 確定した外部キーが格納される
     * @return array [lcolmun => fcolumn]
     */
    public function getIndirectlyColumns($to_table, $from_table, &$fkey = null)
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
     *
     * @param string $to_table 向かうテーブル名（被参照外部キー）
     * @param string $from_table 元テーブル名（参照外部キー）
     * @param string $from_column 元カラム名
     * @return array 辿ったパス
     */
    public function followColumnName($to_table, $from_table, $from_column)
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
