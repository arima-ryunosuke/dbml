<?php

namespace ryunosuke\Test\dbml\Logging;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;
use Psr\Log\AbstractLogger;
use ryunosuke\dbml\Logging\Logger;
use ryunosuke\dbml\Logging\Middleware;
use function ryunosuke\dbml\try_catch;

class MiddlewareTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $logger = new class() extends AbstractLogger {
            public array $logs = [];

            public function log($level, $message, array $context = [])
            {
                $simple = Logger::simple();
                $this->logs[$level][] = [
                    'message' => $message,
                    'query'   => $simple($context['sql'] ?? '', $context['params'] ?? [], [], []),
                ];
            }
        };
        $config = new Configuration();
        $config->setMiddlewares(['logging' => new Middleware($logger)]);
        $connection = DriverManager::getConnection([
            'url'          => 'sqlite:///:memory:',
            'wrapperClass' => Connection::class,
        ], $config);

        $connection->getNativeConnection()->exec('CREATE TABLE logs(id PRIMARY KEY)');

        $connection->beginTransaction();
        $connection->rollBack();
        $connection->beginTransaction();
        $connection->commit();

        $connection->query('select 1');
        try_catch(fn() => $connection->query('select fail'));
        $connection->exec('select 2');
        try_catch(fn() => $connection->exec('select fail'));

        $dummy = 1;
        $statement = $connection->prepare('insert into logs values(? + ?)');
        $statement->bindParam(1, $dummy);
        $statement->bindValue(2, 2);
        $statement->execute();
        $statement->bindValue(1, $dummy);
        $statement->bindValue(2, 2);
        try_catch(fn() => $statement->execute());

        try_catch(fn() => $connection->executeQuery('fail1 ?, ?', [1, 2]));
        try_catch(fn() => $connection->executeStatement('fail2 ?, ?', [1, 2]));

        unset($connection);
        unset($statement);
        gc_collect_cycles();

        $this->assertEquals([
            "debug" => [
                [
                    "message" => "Executing prepare: {sql} parameters: {params}, types: {types}, time: {time}",
                    "query"   => "insert into logs values(undefined(0) + undefined(1))",
                ],
            ],
            "info"  => [
                [
                    "message" => "Connecting",
                    "query"   => "",
                ],
                [
                    "message" => "BEGIN",
                    "query"   => "",
                ],
                [
                    "message" => "ROLLBACK",
                    "query"   => "",
                ],
                [
                    "message" => "BEGIN",
                    "query"   => "",
                ],
                [
                    "message" => "COMMIT",
                    "query"   => "",
                ],
                [
                    "message" => "Executing select: {sql}, time: {time}",
                    "query"   => "select 1",
                ],
                [
                    "message" => "Executing affect: {sql}, time: {time}",
                    "query"   => "select 2",
                ],
                [
                    "message" => "Executing statement: {sql} parameters: {params}, types: {types}, time: {time}",
                    "query"   => "insert into logs values(1 + 2)",
                ],
                [
                    "message" => "Disconnecting",
                    "query"   => "",
                ],
            ],
            "error" => [
                [
                    "message" => "Executing select: {sql}, time: {time}",
                    "query"   => "select fail",
                ],
                [
                    "message" => "Executing affect: {sql}, time: {time}",
                    "query"   => "select fail",
                ],
                [
                    "message" => "Executing statement: {sql} parameters: {params}, types: {types}, time: {time}",
                    "query"   => "insert into logs values(1 + 2)",
                ],
                [
                    "message" => "Executing prepare: {sql} parameters: {params}, types: {types}, time: {time}",
                    "query"   => "fail1 1, 2",
                ],
                [
                    "message" => "Executing prepare: {sql} parameters: {params}, types: {types}, time: {time}",
                    "query"   => "fail2 1, 2",
                ],
            ],
        ], $logger->logs);
    }
}


class Connection extends \Doctrine\DBAL\Connection
{
    public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        $connection = $this->getWrappedConnection();

        if (count($params) > 0) {
            return new Result($connection->prepare($sql, $params, $types)->execute(), $this);
        }

        return new Result($connection->query($sql), $this);
    }

    public function executeStatement($sql, array $params = [], array $types = [])
    {
        $connection = $this->getWrappedConnection();

        if (count($params) > 0) {
            return $connection->prepare($sql, $params, $types)->execute()->rowCount();
        }

        return $connection->exec($sql);
    }
}
