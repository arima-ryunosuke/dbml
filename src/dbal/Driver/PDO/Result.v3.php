<?php
//@formatter:off
/**
 * @see \Doctrine\DBAL\Driver\PDO\Result
 * @copyright 2006 Doctrine Project
 * @link https://raw.githubusercontent.com/doctrine/dbal/master/LICENSE
 */

declare(strict_types=1);

namespace ryunosuke\dbal\Driver\PDO;

use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use PDO;
use PDOException;
use PDOStatement;

class Result implements ResultInterface
{
    protected PDOStatement $statement;

    /** @internal The result can be only instantiated by its driver connection or statement. */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->fetch(PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return $this->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->fetchAll(PDO::FETCH_COLUMN);
    }

    public function rowCount(): int
    {
        try {
            return $this->statement->rowCount();
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function columnCount(): int
    {
        try {
            return $this->statement->columnCount();
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function free(): void
    {
        $this->statement->closeCursor();
    }

    /**
     * @phpstan-param PDO::FETCH_* $mode
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function fetch(int $mode)
    {
        try {
            return $this->statement->fetch($mode);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * @phpstan-param PDO::FETCH_* $mode
     *
     * @return list<mixed>
     *
     * @throws Exception
     */
    private function fetchAll(int $mode): array
    {
        try {
            return $this->statement->fetchAll($mode);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }
}
