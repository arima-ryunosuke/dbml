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
        $this->logger->info('Disconnecting');
    }

    public function prepare(string $sql): DriverStatement
    {
        $this->logger->debug('Executing prepare: {sql}', ['sql' => $sql]);

        return new Statement(parent::prepare($sql), $this->logger, $sql);
    }

    public function query(string $sql): Result
    {
        $start = microtime(true);

        try {
            return parent::query($sql);
        }
        finally {
            $this->logger->info('Executing select: {sql}, elapsed: {elapsed}', ['sql' => $sql, 'elapsed' => microtime(true) - $start]);
        }
    }

    public function exec(string $sql): int
    {
        $start = microtime(true);

        try {
            return parent::exec($sql);
        }
        finally {
            $this->logger->info('Executing affect: {sql}, elapsed: {elapsed}', ['sql' => $sql, 'elapsed' => microtime(true) - $start]);
        }
    }

    public function beginTransaction()
    {
        $this->logger->info('BEGIN');

        return parent::beginTransaction();
    }

    public function commit()
    {
        $this->logger->info('COMMIT');

        return parent::commit();
    }

    public function rollBack()
    {
        $this->logger->info('ROLLBACK');

        return parent::rollBack();
    }
}
