<?php

namespace ryunosuke\Test\dbml\Logging;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Psr\Log\AbstractLogger;
use ryunosuke\dbml\Logging\Logger;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\dbml\Utility\Adhoc;
use function ryunosuke\dbml\try_catch;

class MiddlewareTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $logger = new class() extends AbstractLogger {
            public array $logs = [];

            public function log($level, $message, array $context = []): void
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
        $connection = DriverManager::getConnection(Adhoc::parseParams([
            'url' => 'sqlite:///:memory:',
        ]), $config);

        $connection->getNativeConnection()->exec('CREATE TABLE logs(id PRIMARY KEY)');

        $connection->beginTransaction();
        $connection->rollBack();
        $connection->beginTransaction();
        $connection->commit();

        $connection->executeQuery('select 1');
        try_catch(fn() => $connection->executeQuery('select fail'));
        $connection->executeStatement('select 2');
        try_catch(fn() => $connection->executeStatement('select fail'));

        $statement = $connection->prepare('insert into logs values(? + ?)');
        $statement->bindValue(1, 1);
        $statement->bindValue(2, 2);
        $statement->executeStatement();
        $statement->bindValue(1, 1);
        $statement->bindValue(2, 2);
        try_catch(fn() => $statement->executeStatement());

        try_catch(fn() => $connection->executeQuery('fail1 1, 2', [1, 2]));
        try_catch(fn() => $connection->executeStatement('fail2 1, 2', [1, 2]));

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
