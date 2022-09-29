<?php

namespace ryunosuke\Test\dbml\Logging;

use Psr\Log\AbstractLogger;
use ryunosuke\dbml\Logging\Connection;
use ryunosuke\dbml\Logging\Driver;
use ryunosuke\dbml\Logging\Middleware;

class MiddlewareTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $logger = new class() extends AbstractLogger {
            public array $logs = [];

            public function log($level, $message, array $context = [])
            {
                $this->logs[] = $message;
            }
        };
        $middleware = new Middleware($logger);

        $driver = $middleware->wrap(new \Doctrine\DBAL\Driver\PDO\SQLite\Driver());
        $this->assertInstanceOf(Driver::class, $driver);

        $connection = $driver->connect(['url' => 'sqlite:///:memory:']);
        $this->assertInstanceOf(Connection::class, $connection);

        $connection->beginTransaction();
        $connection->rollBack();
        $connection->beginTransaction();
        $connection->commit();

        $connection->query('select 1');
        $connection->exec('select 2');

        $statement = $connection->prepare('select ?, ?');
        $statement->bindParam(1, $dummy);
        $statement->bindValue(2, 2);
        $statement->execute();

        unset($connection);

        $this->assertEquals([
            'Connecting',
            'BEGIN',
            'ROLLBACK',
            'BEGIN',
            'COMMIT',
            'Executing select: {sql}, elapsed: {elapsed}',
            'Executing affect: {sql}, elapsed: {elapsed}',
            'Executing prepare: {sql}',
            'Executing statement: {sql} (parameters: {params}, types: {types}, elapsed: {elapsed})',
            'Disconnecting',
        ], $logger->logs);
    }
}
