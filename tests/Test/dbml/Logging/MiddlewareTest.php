<?php

namespace ryunosuke\Test\dbml\Logging;

use Psr\Log\AbstractLogger;
use ryunosuke\dbml\Logging\Connection;
use ryunosuke\dbml\Logging\Driver;
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
                $this->logs[$level][] = $message;
            }
        };
        $middleware = new Middleware($logger);

        $driver = $middleware->wrap(new \Doctrine\DBAL\Driver\PDO\SQLite\Driver());
        $this->assertInstanceOf(Driver::class, $driver);

        $connection = $driver->connect(['url' => 'sqlite:///:memory:']);
        $this->assertInstanceOf(Connection::class, $connection);

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

        try_catch(fn() => $connection->prepare('select fail'));

        unset($connection);

        $this->assertEquals([
            "debug" => [
                "Executing prepare: {sql}",
            ],
            "info"  => [
                "Connecting",
                "BEGIN",
                "ROLLBACK",
                "BEGIN",
                "COMMIT",
                "Executing select: {sql}, elapsed: {elapsed}",
                "Executing affect: {sql}, elapsed: {elapsed}",
                "Executing statement: {sql} (parameters: {params}, types: {types}, elapsed: {elapsed})",
                "Disconnecting",
            ],
            "error" => [
                "Executing select: {sql}, elapsed: {elapsed}",
                "Executing affect: {sql}, elapsed: {elapsed}",
                "Executing statement: {sql} (parameters: {params}, types: {types}, elapsed: {elapsed})",
                "Executing prepare: {sql}",
            ],
        ], $logger->logs);
    }
}
