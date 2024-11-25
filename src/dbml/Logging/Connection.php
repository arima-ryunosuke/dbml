<?php

namespace ryunosuke\dbml\Logging;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Psr\Log\LoggerInterface;

final class Connection extends AbstractConnectionMiddleware
{
    private LoggerInterface $logger;

    public function __construct(ConnectionInterface $connection, LoggerInterface $logger)
    {
        parent::__construct($connection);

        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->logger->info('Disconnecting', ['time' => microtime(true)]);
    }

    public function prepare(string $sql, $params = [], $types = []): DriverStatement
    {
        $start = microtime(true);

        try {
            $level = 'debug';
            return new Statement(parent::prepare($sql), $this->logger, $sql);
        }
        catch (\Throwable $t) {
            $level = 'error';
            throw $t;
        }
        finally {
            $this->logger->$level('Executing prepare: {sql} parameters: {params}, types: {types}, time: {time}', ['sql' => $sql, 'params' => $params, 'types' => $types, 'time' => $start]);
        }
    }

    public function query(string $sql): Result
    {
        $start = microtime(true);

        try {
            $level = 'info';
            return parent::query($sql);
        }
        catch (\Throwable $t) {
            $level = 'error';
            throw $t;
        }
        finally {
            $this->logger->$level('Executing select: {sql}, time: {time}', ['sql' => $sql, 'time' => $start]);
        }
    }

    public function exec(string $sql): int
    {
        $start = microtime(true);

        try {
            $level = 'info';
            return parent::exec($sql);
        }
        catch (\Throwable $t) {
            $level = 'error';
            throw $t;
        }
        finally {
            $this->logger->$level('Executing affect: {sql}, time: {time}', ['sql' => $sql, 'time' => $start]);
        }
    }

    public function beginTransaction(): void
    {
        $this->logger->info('BEGIN', ['time' => microtime(true)]);

        parent::beginTransaction();
    }

    public function commit(): void
    {
        $this->logger->info('COMMIT', ['time' => microtime(true)]);

        parent::commit();
    }

    public function rollBack(): void
    {
        $this->logger->info('ROLLBACK', ['time' => microtime(true)]);

        parent::rollBack();
    }
}
