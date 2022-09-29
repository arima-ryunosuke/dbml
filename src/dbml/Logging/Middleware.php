<?php

namespace ryunosuke\dbml\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Psr\Log\LoggerInterface;

final class Middleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new Driver($driver, $this->logger);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
