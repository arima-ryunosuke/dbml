<?php

namespace ryunosuke\dbml\Driver;

use ArrayIterator;
use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result as DriverResult;

class ArrayResult implements DriverResult, ResultInterface
{
    use ResultTrait;

    private ArrayIterator $rows;
    private array         $metadata;
    private array         $columns;

    public function __construct(array $data, array $metadata)
    {
        $this->rows = new ArrayIterator($data);
        $this->metadata = $metadata;
        $this->columns = array_column($this->metadata, 'aliasColumnName');
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function fetchNumeric()
    {
        if (!$this->rows->valid()) {
            return false;
        }

        $result = $this->rows->current();
        $this->rows->next();
        return $result;
    }

    public function fetchAssociative()
    {
        if (!$this->rows->valid()) {
            return false;
        }

        if ($this->checkSameMethod) {
            return $this->checkSameColumn($this->fetchNumeric(), false);
        }
        return array_combine($this->columns, $this->fetchNumeric());
    }

    public function fetchOne()
    {
        if (!$this->rows->valid()) {
            return false;
        }

        $row = $this->fetchNumeric();
        return reset($row);
    }

    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    public function fetchAllAssociative(): array
    {
        if ($this->checkSameMethod) {
            return $this->checkSameColumn($this->fetchAllNumeric(), true);
        }
        return FetchUtils::fetchAllAssociative($this);
    }

    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }

    public function columnCount(): int
    {
        return count($this->columns);
    }

    public function free(): void
    {
        unset($this->rows);
        $this->metadata = [];
    }
}
