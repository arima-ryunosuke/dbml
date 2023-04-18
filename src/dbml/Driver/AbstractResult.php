<?php

namespace ryunosuke\dbml\Driver;

use Doctrine\DBAL\Driver\FetchUtils;
use Doctrine\DBAL\Driver\Result;

abstract class AbstractResult implements Result
{
    protected bool $groupByName = false;

    public function groupByName()
    {
        $this->groupByName = true;
    }

    protected function _fetchGroup(array $columns, array $values)
    {
        $settled = [];
        $result = [];
        foreach ($columns as $n => $column) {
            $settled[$column] = ($settled[$column] ?? 0) + 1;

            if ($settled[$column] === 1) {
                $result[$column] = $values[$n];
            }
            elseif ($settled[$column] === 2) {
                $result[$column] = [$result[$column], $values[$n]];
            }
            else {
                $result[$column][] = $values[$n];
            }
        }
        return $result;
    }

    /** @inheritdoc */
    public function fetchOne()
    {
        return FetchUtils::fetchOne($this);
    }

    /** @inheritdoc */
    public function fetchAllNumeric(): array
    {
        return FetchUtils::fetchAllNumeric($this);
    }

    /** @inheritdoc */
    public function fetchAllAssociative(): array
    {
        return FetchUtils::fetchAllAssociative($this);
    }

    /** @inheritdoc */
    public function fetchFirstColumn(): array
    {
        return FetchUtils::fetchFirstColumn($this);
    }
}
