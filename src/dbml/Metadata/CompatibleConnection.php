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
    /** @var Connection */
    private $connection;

    /** @var DriverConnection */
    private $driverConnection;

    /** @var \PDO|\mysqli|\PgSql\Connection */
    private $nativeConnection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $driverConnection = $connection->getWrappedConnection();
        while ($driverConnection instanceof AbstractConnectionMiddleware) {
            $driverConnection = (fn() => $this->wrappedConnection)->bindTo($driverConnection, AbstractConnectionMiddleware::class)();
        }
        $this->driverConnection = $driverConnection;
        $this->nativeConnection = $driverConnection->getNativeConnection();
    }

    public function getName()
    {
        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            return 'pdo-' . $this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
        if ($this->driverConnection instanceof Driver\SQLite3\Connection) {
            return 'sqlite3';
        }
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            return 'mysqli';
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            return 'pgsql';
        }

        throw DBALException::notSupported(__METHOD__);
    }

    public function isSupportedNamedPlaceholder()
    {
        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            // pdo-sqlite はテスト用に対応していないとみなす
            if ($this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                return false;
            }
            // pdo-mysql はエミュレーションモードを切ると使えない…と思ったら PDO が名前付きを連番に書き換えてくれるようだ
            //if ($this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            //    return false;
            //}
            return true;
        }
        // mysqli は本当に対応していない
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            return false;
        }
        // PgSql は対応していない…訳では無いが特殊なので doctrine に任せる
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            return false;
        }

        return true;
    }

    public function isSupportedTablePrefix()
    {
        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            if ($this->nativeConnection->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
                return true;
            }
        }
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            return true;
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            return true;
        }
        return false;
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
        catch (\PDOException $t) {
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
            // 直接代入はいつか壊れるが、いずれ WeakMap に変更したいのでとりあえず暫定
            $this->driverConnection->bufferMode = $mode;
        }
    }

    public function setTablePrefix()
    {
        // \PDO::ATTR_FETCH_TABLE_NAMES をサポートしてるならそれで（そっちのほうが汎用性が高い）
        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            if ($this->tryPDOAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, true)) {
                return function () {
                    $this->nativeConnection->setAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, false);
                };
            }
        }

        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            // 直接代入はいつか壊れるが、いずれ WeakMap に変更したいのでとりあえず暫定
            $this->driverConnection->tablePrefix = true;
            return function () {
                $this->driverConnection->tablePrefix = false;
            };
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            // 直接代入はいつか壊れるが、いずれ WeakMap に変更したいのでとりあえず暫定
            $this->driverConnection->tablePrefix = true;
            return function () {
                $this->driverConnection->tablePrefix = false;
            };
        }
    }

    public function customResult(Result $result, $groupByName)
    {
        $driverResult = (fn() => $this->result)->bindTo($result, Result::class)($result);

        if ($this->driverConnection instanceof Driver\PDO\Connection) {
            if ($groupByName) {
                $result = new class($driverResult, $this->connection) extends Result {
                    private \PDOStatement $statement;

                    public function __construct(Driver\PDO\Result $result, Connection $connection)
                    {
                        parent::__construct($result, $connection);

                        $this->statement = (fn() => $this->statement)->bindTo($result, Driver\PDO\Result::class)();
                    }

                    public function fetchAssociative()
                    {
                        return $this->statement->fetch(\PDO::FETCH_ASSOC | \PDO::FETCH_NAMED);
                    }

                    public function fetchAllAssociative(): array
                    {
                        return $this->statement->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_NAMED);
                    }
                };
            }
        }
        if ($this->driverConnection instanceof Driver\SQLite3\Connection) {
            /** @var \ryunosuke\dbml\Driver\SQLite3\Result $driverResult */
            if ($groupByName) {
                $driverResult->groupByName();
            }
        }
        if ($this->driverConnection instanceof Driver\Mysqli\Connection) {
            /** @var \ryunosuke\dbml\Driver\Mysqli\Result $driverResult */
            if ($this->driverConnection->bufferMode ?? true) {
                $driverResult->storeResult();
            }
            if ($this->driverConnection->tablePrefix ?? false) {
                $driverResult->prefixTableName();
            }
            if ($groupByName) {
                $driverResult->groupByName();
            }
        }
        if ($this->driverConnection instanceof Driver\PgSQL\Connection) {
            /** @var \ryunosuke\dbml\Driver\PgSQL\Result $driverResult */
            if ($this->driverConnection->tablePrefix ?? false) {
                $driverResult->prefixTableName();
            }
            if ($groupByName) {
                $driverResult->groupByName();
            }
        }

        return $result;
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

    public function executeAsync($sqls, &$affectedRows = null)
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
            $tick = function ($native, $waitForEnd) {
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
                            $types = array_column($myresult->fetch_fields(), 'type', 'name');

                            $result = [];
                            foreach ($myresult as $n => $row) {
                                foreach ($row as $column => $value) {
                                    $result[$n][$column] = \ryunosuke\dbml\Driver\Mysqli\Result::mapType($types[$column], $value);
                                }
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
            $tick = function ($native, $waitForEnd) {
                if (!$waitForEnd && @pg_connection_busy($native)) {
                    return null;
                }

                $pgresult = @pg_get_result($native);
                if ($pgresult === false || pg_result_status($pgresult) === PGSQL_FATAL_ERROR) {
                    throw new Driver\PgSQL\Exception(@pg_last_error($native) ?: 'unknown');
                }

                if (pg_num_fields($pgresult) !== 0) {
                    $types = [];
                    $numFields = pg_num_fields($pgresult);
                    for ($i = 0; $i < $numFields; ++$i) {
                        $name = pg_field_name($pgresult, $i);
                        $types[$name] = pg_field_type($pgresult, $i);
                    }

                    $result = [];
                    foreach (pg_fetch_all($pgresult) as $n => $row) {
                        foreach ($row as $column => $value) {
                            $result[$n][$column] = \ryunosuke\dbml\Driver\PgSQL\Result::mapType($types[$column], $value);
                        }
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
