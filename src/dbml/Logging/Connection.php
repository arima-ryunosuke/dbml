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

    public function prepare(string $sql): DriverStatement
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
            $this->logger->$level('Executing prepare: {sql}, time: {time}', ['sql' => $sql, 'time' => $start]);
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

    public function beginTransaction()
    {
        $this->logger->info('BEGIN', ['time' => microtime(true)]);

        return parent::beginTransaction();
    }

    public function commit()
    {
        $this->logger->info('COMMIT', ['time' => microtime(true)]);

        return parent::commit();
    }

    public function rollBack()
    {
        $this->logger->info('ROLLBACK', ['time' => microtime(true)]);

        return parent::rollBack();
    }
}
