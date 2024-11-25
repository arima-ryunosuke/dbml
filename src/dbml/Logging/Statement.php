<?php

namespace ryunosuke\dbml\Logging;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

final class Statement extends AbstractStatementMiddleware
{
    private LoggerInterface $logger;
    private string          $sql;

    private array $params = [];

    private array $types = [];

    public function __construct(StatementInterface $statement, LoggerInterface $logger, string $sql)
    {
        parent::__construct($statement);

        $this->logger = $logger;
        $this->sql = $sql;
    }

    public function bindValue($param, $value, $type = ParameterType::STRING): void
    {
        $this->params[$param] = $value;
        $this->types[$param] = $type;

        parent::bindValue($param, $value, $type);
    }

    public function execute($params = null): ResultInterface
    {
        $start = microtime(true);

        try {
            $level = 'info';
            return parent::execute($params);
        }
        catch (\Throwable $t) {
            $level = 'error';
            throw $t;
        }
        finally {
            $this->logger->$level('Executing statement: {sql} parameters: {params}, types: {types}, time: {time}', [
                'sql'    => $this->sql,
                'params' => $params ?? $this->params,
                'types'  => $this->types,
                'time'   => $start,
            ]);
        }
    }
}
