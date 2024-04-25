<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Result;
use ryunosuke\dbml\Query\Parser;

/**
 * RDBMS 特有の処理を記述するクラス
 *
 * ライブラリ内部で $native instanceof したくないのでそういうのはこのクラスが吸収する。
 * あと sqlite だけでできるだけカバレッジを埋めたい裏事情もある。
 *
 * @codeCoverageIgnore 汚いものを押し付けただけなのでまだカバレッジしない
 */
class CompatibleConnection
{
    private static \WeakMap $storage;

    /** @var Connection */
    private $connection;

    /** @var DriverConnection */
    private $driverConnection;

    /** @var \PDO|\mysqli|\PgSql\Connection */
    private $nativeConnection;

    public function __construct(Connection $connection)
    {
        self::$storage ??= new \WeakMap();

        $this->connection = $connection;

        $driverConnection = $connection->getWrappedConnection();
        while ($driverConnection instanceof AbstractConnectionMiddleware) {
            $driverConnection = (fn() => $this->wrappedConnection)->bindTo($driverConnection, AbstractConnectionMiddleware::class)();
        }
        $this->driverConnection = $driverConnection;
        $this->nativeConnection = $driverConnection->getNativeConnection();

        self::$storage[$this->connection] = [];
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getName()
    {
        if (isset(self::$storage[$this->connection][__FUNCTION__])) {
            return self::$storage[$this->connection][__FUNCTION__];
        }

        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            return self::$storage[$this->connection][__FUNCTION__] = 'pdo-' . $this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        if ($this->driverConnection instanceof Driver\SQLite3\Connection) {
            return self::$storage[$this->connection][__FUNCTION__] = 'sqlite3';
        }
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            return self::$storage[$this->connection][__FUNCTION__] = 'mysqli';
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            return self::$storage[$this->connection][__FUNCTION__] = 'pgsql';
        }

        throw DBALException::notSupported(__METHOD__);
    }

    public function isSupportedNamedPlaceholder()
    {
        if (isset(self::$storage[$this->connection][__FUNCTION__])) {
            return self::$storage[$this->connection][__FUNCTION__];
        }

        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            // pdo-sqlite はテスト用に対応していないとみなす
            if ($this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                return self::$storage[$this->connection][__FUNCTION__] = false;
            }
            // pdo-mysql はエミュレーションモードを切ると使えない…と思ったら PDO が名前付きを連番に書き換えてくれるようだ
            //if ($this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            //    return false;
            //}
            return self::$storage[$this->connection][__FUNCTION__] = true;
        }
        // mysqli は本当に対応していない
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            return self::$storage[$this->connection][__FUNCTION__] = false;
        }
        // PgSql は対応していない…訳では無いが特殊なので doctrine に任せる
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            return self::$storage[$this->connection][__FUNCTION__] = false;
        }

        return self::$storage[$this->connection][__FUNCTION__] = true;
    }

    public function getSupportedMetadata(): array
    {
        if (isset(self::$storage[$this->connection][__FUNCTION__])) {
            return self::$storage[$this->connection][__FUNCTION__];
        }

        $base = [
            'actualTableName'  => false,
            'actualColumnName' => false,
            'aliasTableName'   => false,
            'aliasColumnName'  => false,
            'nativeType'       => false,
            'table&&column'    => false, // actual,alias を問わず table,column の両方をサポートしているかのショートカット
        ];

        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            // https://learn.microsoft.com/ja-jp/sql/connect/php/pdostatement-getcolumnmeta?view=sql-server-ver16
            // > データベースで列を含むテーブルの名前を指定します。 常に空白です。
            if ($this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlsrv') {
                return self::$storage[$this->connection][__FUNCTION__] = array_replace($base, [
                    'aliasColumnName' => true,
                    'nativeType'      => true,
                ]);
            }
            /** @see \PDOStatement::getColumnMeta() */
            return self::$storage[$this->connection][__FUNCTION__] = array_replace($base, [
                'actualTableName' => true,
                'aliasColumnName' => true,
                'nativeType'      => true,
                'table&&column'   => true,
            ]);
        }
        if ($this->driverConnection instanceof Driver\SQLite3\Connection) {
            /** @see \SQLite3Result::columnName(), \SQLite3Result::columnType() */
            return self::$storage[$this->connection][__FUNCTION__] = array_replace($base, [
                'aliasColumnName' => true,
                'nativeType'      => true,
            ]);
        }
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            /** @see \mysqli_result::fetch_fields() */
            return self::$storage[$this->connection][__FUNCTION__] = array_replace($base, [
                'actualTableName'  => true,
                'actualColumnName' => true,
                'aliasTableName'   => true,
                'aliasColumnName'  => true,
                'nativeType'       => true,
                'table&&column'    => true,
            ]);
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            /** @see \pg_field_table(), \pg_field_name(), \pg_field_type() */
            return self::$storage[$this->connection][__FUNCTION__] = array_replace($base, [
                'actualTableName' => true,
                'aliasColumnName' => true,
                'nativeType'      => true,
                'table&&column'   => true,
            ]);
        }
        return self::$storage[$this->connection][__FUNCTION__] = $base;
    }

    public function tryPDOAttribute($attribute_name, $attribute_value)
    {
        static $supported_cache = [];

        $driverName = $this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        try {
            if ($supported_cache[$driverName][$attribute_name] ?? true) {
                $supported_cache[$driverName][$attribute_name] = $this->nativeConnection->setAttribute($attribute_name, $attribute_value);
            }
        }
        catch (\PDOException) {
            $supported_cache[$driverName][$attribute_name] = false;
        }

        return $supported_cache[$driverName][$attribute_name];
    }

    public function setBufferMode($mode)
    {
        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            $this->tryPDOAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $mode);
        }
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            self::$storage[$this->connection]['setBufferMode'] = $mode;
        }
    }

    public function customResult(Result $result, $checkSameColumn)
    {
        $driverResult = (fn() => $this->result)->bindTo($result, Result::class)($result);

        if ($driverResult instanceof \ryunosuke\dbml\Driver\ResultInterface) {
            if ($checkSameColumn) {
                $driverResult->setSameCheckMethod($checkSameColumn);
            }
        }

        if ($driverResult instanceof \ryunosuke\dbml\Driver\Mysqli\Result) {
            if (self::$storage[$this->connection]['setBufferMode'] ?? true) {
                $driverResult->storeResult();
            }
        }

        return $result;
    }

    public function getMetadata(Result $result): array
    {
        $driverResult = (fn() => $this->result)->bindTo($result, Result::class)($result);

        if ($driverResult instanceof \ryunosuke\dbml\Driver\ResultInterface) {
            $metadata = $driverResult->getMetadata();
            // アクセステーブルのメタデータに対応していない場合、クエリレベルで強制的に付与しているので分解しなければならない
            /** @see \ryunosuke\dbml\Database::_toTablePrefix() */
            if (!$this->getSupportedMetadata()['table&&column']) {
                foreach ($metadata as $n => $metadatum) {
                    $parts = explode('.', $metadatum['aliasColumnName'], 2);
                    if (count($parts) === 2 && !strlen($metadatum['aliasTableName'] ?? '')) {
                        $metadata[$n]['aliasTableName'] = $parts[0];
                        $metadata[$n]['aliasColumnName'] = $parts[1];
                    }
                }
            }
            return $metadata;
        }

        return [];
    }

    public function alternateMatchedRows()
    {
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            if ($this->nativeConnection->info !== null && preg_match('#Rows matched:\s*(\d+)\s*Changed: \s*(\d+)\s*Warnings: \s*(\d+)#ui', $this->nativeConnection->info, $matches)) {
                return (int) $matches[1];
            }
        }
        return null;
    }

    public function executeAsync($sqls, $converter, &$affectedRows = null)
    {
        $parser = new Parser($this->connection->getDatabasePlatform()->createSQLParser());

        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            $next = function ($native, $sql, $params) use ($parser) {
                // mysql に非同期 prepare は存在しないため埋め込んで実行する
                $query = $parser->convertQuotedSQL($sql, $params, fn($v) => "'{$native->escape_string($v)}'");
                $result = $native->query($query, MYSQLI_ASYNC);
                if ($result === false) {
                    throw new Driver\Mysqli\Exception\ConnectionError($native->error);
                }
                return $result;
            };
            $tick = function ($native, $waitForEnd) use ($converter) {
                do {
                    $read = $error = $reject = [$native];
                    if (\mysqli::poll($read, $error, $reject, $waitForEnd ? 1 : 0) === false) {
                        throw new \RuntimeException("mysqli::poll returned false");
                    }

                    foreach ($read as $mysqli) {
                        $myresult = $mysqli->reap_async_query();
                        if ($myresult === false) {
                            throw new Driver\Mysqli\Exception\ConnectionError($mysqli->error);
                        }

                        $result = null;
                        if ($myresult instanceof \mysqli_result) {
                            $metadata = \ryunosuke\dbml\Driver\Mysqli\Result::getMetadataFrom($myresult);

                            $result = [];
                            foreach ($myresult->fetch_all(MYSQLI_NUM) as $i => $row) {
                                foreach ($row as $n => $value) {
                                    $result[$i][$metadata[$n]['aliasColumnName']] = \ryunosuke\dbml\Driver\Mysqli\Result::mapType($metadata[$n]['nativeType'], $value);
                                }
                                $result[$i] = $converter($result[$i], $metadata);
                            }
                            $myresult->free();
                        }
                        if ($myresult === true) {
                            $result = $mysqli->affected_rows;
                        }
                        return $result;
                    }
                    // クエリタイムアウトとか接続が切れたとか致命的だと思うのでハンドリングしない
                    foreach ($error as $mysqli) {
                        throw new \RuntimeException("mysqli::poll reported error {$mysqli->sqlstate}");
                    }
                    // 1接続で1クエリを投げているだけなので reject(もうクエリがない) されることはない
                    foreach ($reject as $mysqli) {
                        throw new \RuntimeException("mysqli::poll reported reject {$mysqli->sqlstate}");
                    }
                } while (!$read && $waitForEnd);
            };
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            $next = function ($native, $sql, $params) use ($parser) {
                $query = $parser->convertDollarSQL($sql, $params);
                $return = @pg_send_query_params($native, $query, $params);
                if ($return === 0) {
                    throw new Driver\PgSQL\Exception(pg_last_error($native) ?: 'unknown');
                }
                return $return;
            };
            $tick = function ($native, $waitForEnd) use ($converter) {
                if (!$waitForEnd && @pg_connection_busy($native)) {
                    return null;
                }

                $pgresult = @pg_get_result($native);
                if ($pgresult === false || pg_result_status($pgresult) === PGSQL_FATAL_ERROR) {
                    throw new Driver\PgSQL\Exception(@pg_last_error($native) ?: 'unknown');
                }

                if (pg_num_fields($pgresult) !== 0) {
                    $metadata = \ryunosuke\dbml\Driver\PgSQL\Result::getMetadataFrom($pgresult);

                    $result = [];
                    foreach (pg_fetch_all($pgresult, PGSQL_NUM) as $i => $row) {
                        foreach ($row as $n => $value) {
                            $result[$i][$metadata[$n]['aliasColumnName']] = \ryunosuke\dbml\Driver\PgSQL\Result::mapType($metadata[$n]['nativeType'], $value);
                        }
                        $result[$i] = $converter($result[$i], $metadata);
                    }
                }
                else {
                    $result = pg_affected_rows($pgresult);
                }
                pg_free_result($pgresult);
                return $result;
            };
        }

        if (!isset($next, $tick)) {
            // カバレッジのために無名クラス自体は返すようにする
            $next = function () {
                throw DBALException::notSupported(__METHOD__);
            };
            $tick = function () { };
        }

        return new class($this->nativeConnection, $sqls, $next, $tick, $affectedRows) {
            private            $native;
            private \Generator $sqls;
            private \Closure   $next;
            private \Closure   $tick;
            private bool       $waiting = false;
            private array      $results = [];
            private            $affectedRows;

            public function __construct($native, $sqls, $next, $tick, &$affectedRows)
            {
                $this->native = $native;
                $this->sqls = $sqls;
                $this->next = $next;
                $this->tick = $tick;
                $this->affectedRows = &$affectedRows;

                $this->tick();
                register_tick_function([$this, 'tick']);
            }

            public function __destruct()
            {
                unregister_tick_function([$this, 'tick']);

                // 結果を受け取らないまま gc されるのは大抵の場合よくないので下手に制御せず例外を投げる（トランザクションが閉じている・負荷次第でたまにコケる・実行されたりされなかったりする）
                if (!(!$this->waiting && !$this->sqls->valid())) {
                    throw new \UnexpectedValueException("query not completed");
                }
            }

            public function __invoke($index = null)
            {
                while (true) {
                    if ($this->tick(true) === null) {
                        break;
                    }
                    if ($index !== null && isset($this->results[$index])) {
                        break;
                    }
                }
                if ($index === null) {
                    return $this->results;
                }
                else {
                    return $this->results[$index];
                }
            }

            public function tick($waitForEnd = false)
            {
                // やることがない
                if (!$this->waiting && !$this->sqls->valid()) {
                    return null;
                }

                // 完了してるなら次のクエリへ
                if (!$this->waiting) {
                    $params = $this->sqls->current();
                    $query = $this->sqls->key();
                    $this->sqls->next();
                    ($this->next)($this->native, $query, $params);
                    $this->waiting = true;
                    return 1;
                }
                else {
                    $result = ($this->tick)($this->native, $waitForEnd);
                    if ($result === null) {
                        return 0;
                    }
                    assert(!is_bool($result));
                    if (is_array($result)) {
                        $this->results[] = $result;
                        $this->waiting = false;
                    }
                    elseif (is_int($result)) {
                        $this->affectedRows = $this->results[] = $result;
                        $this->waiting = false;
                    }
                    return -1;
                }
            }
        };
    }
}
