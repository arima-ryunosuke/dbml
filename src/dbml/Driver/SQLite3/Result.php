<?php

namespace ryunosuke\dbml\Driver\SQLite3;

use ryunosuke\dbml\Driver\AbstractResult;
use SQLite3Result;
use const SQLITE3_ASSOC;
use const SQLITE3_NUM;

/**
 * @see \Doctrine\DBAL\Driver\SQLite3\Result
 * @copyright 2006 Doctrine Project
 * @link https://raw.githubusercontent.com/doctrine/dbal/master/LICENSE
 *
 * @codeCoverageIgnore
 */
final class Result extends AbstractResult
{
    private ?SQLite3Result $result;
    private int            $changes;

    /** @internal The result can be only instantiated by its driver connection or statement. */
    public function __construct(SQLite3Result $result, int $changes)
    {
        $this->result = $result;
        $this->changes = $changes;
    }

    /** @inheritdoc */
    public function fetchNumeric()
    {
        if ($this->result === null) {
            return false;
        }

        return $this->result->fetchArray(SQLITE3_NUM);
    }

    /** @inheritdoc */
    public function fetchAssociative()
    {
        if ($this->result === null) {
            return false;
        }

        if ($this->groupByName) {
            $nums = $this->result->fetchArray(SQLITE3_NUM) ?: [];
            $columns = array_map(fn($n) => $this->result->columnName($n), array_keys($nums));
            return $this->_fetchGroup($columns, $nums) ?: false;
        }

        return $this->result->fetchArray(SQLITE3_ASSOC);
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

    public function free(): void
    {
        if ($this->result === null) {
            return;
        }

        $this->result->finalize();
        $this->result = null;
    }
}
