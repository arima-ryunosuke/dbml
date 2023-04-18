<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Result;

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
}
