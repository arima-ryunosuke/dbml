<?php
//@formatter:off
/**
 * @see \Doctrine\DBAL\Driver\SQLite3\Result
 * @copyright 2006 Doctrine Project
 * @link https://raw.githubusercontent.com/doctrine/dbal/master/LICENSE
 */

declare(strict_types=1);

namespace ryunosuke\dbal\Driver\SQLite3;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Exception\InvalidColumnIndex;
use SQLite3Result;

use const SQLITE3_ASSOC;
use const SQLITE3_NUM;

class Result implements ResultInterface
{
    protected ?SQLite3Result $result;

    /** @internal The result can be only instantiated by its driver connection or statement. */
    public function __construct(SQLite3Result $result, private readonly int $changes)
    {
        $this->result = $result;
    }

    public function fetchNumeric(): array|false
    {
        if ($this->result === null) {
            return false;
        }

        return $this->result->fetchArray(SQLITE3_NUM);
    }

    public function fetchAssociative(): array|false
    {
        if ($this->result === null) {
            return false;
        }

        return $this->result->fetchArray(SQLITE3_ASSOC);
    }

    public function fetchOne(): mixed
    {
        return FetchUtils::fetchOne($this);
    }

    /** @inheritDoc */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /** @inheritDoc */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /** @inheritDoc */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        return $this->changes;
    }

    public function columnCount(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return $this->result->numColumns();
    }

    public function getColumnName(int $index): string
    {
        if ($this->result === null) {
            throw InvalidColumnIndex::new($index);
        }

        $name = $this->result->columnName($index);

        if ($name === false) {
            throw InvalidColumnIndex::new($index);
        }

        return $name;
    }

    public function free(): void
    {
        if ($this->result === null) {
            return;
        }

        $this->result->finalize();
        $this->result = null;
    }
}
