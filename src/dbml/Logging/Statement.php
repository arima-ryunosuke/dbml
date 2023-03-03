<?php

namespace ryunosuke\dbml\Logging;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;

use function array_slice;
use function func_get_args;

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

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        $this->params[$param] = &$variable;
        $this->types[$param] = $type;

        /** @noinspection PhpDeprecationInspection */
        return parent::bindParam($param, $variable, $type, ...array_slice(func_get_args(), 3));
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $value;
        $this->types[$param] = $type;

        return parent::bindValue($param, $value, $type);
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
            $this->logger->$level('Executing statement: {sql} (parameters: {params}, types: {types}, elapsed: {elapsed})', [
                'sql'     => $this->sql,
                'params'  => $params ?? $this->params,
                'types'   => $this->types,
                'elapsed' => microtime(true) - $start,
            ]);
        }
    }
}
