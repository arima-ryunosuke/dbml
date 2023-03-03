<?php

namespace ryunosuke\dbml\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Psr\Log\LoggerInterface;

final class Driver extends AbstractDriverMiddleware
{
    private LoggerInterface $logger;

    public function __construct(DriverInterface $driver, LoggerInterface $logger)
    {
        parent::__construct($driver);

        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function connect(array $params)
    {
        $this->logger->info('Connecting', ['time' => microtime(true)]);

        return new Connection(parent::connect($params), $this->logger);
    }
}
