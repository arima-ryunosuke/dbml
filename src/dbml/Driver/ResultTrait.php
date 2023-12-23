<?php

namespace ryunosuke\dbml\Driver;

trait ResultTrait
{
    protected $checkSameMethod;

    public function setSameCheckMethod(string $method)
    {
        $this->checkSameMethod = $method;
    }

    public function fetchAssociative()
    {
        if ($this->checkSameMethod) {
            return $this->checkSameColumn(parent::fetchNumeric(), false);
        }
        return parent::fetchAssociative();
    }

    public function fetchAllAssociative(): array
    {
        if ($this->checkSameMethod) {
            return $this->checkSameColumn(parent::fetchAllNumeric(), true);
        }
        return parent::fetchAllAssociative();
    }

    private function checkSameColumn($rows, $all)
    {
        if (!$all) {
            if ($rows === false || $rows === null) {
                return false;
            }
            $rows = [$rows];
        }

        $metadata = $this->getMetadata();

        $duplicated = [];
        foreach ($metadata as $n => $meta) {
            assert(isset($meta['aliasColumnName']));
            $parts = explode('.', $meta['aliasColumnName']);
            $duplicated[end($parts)][$n] = null;
        }
        $duplicated = array_filter($duplicated, fn($indexes) => count($indexes) > 1);

        $aliasNames = array_column($metadata, 'aliasColumnName');

        foreach ($rows as $n => $row) {
            foreach ($duplicated as $cname => $indexes) {
                $values = array_intersect_key($row, $indexes);

                if ($this->checkSameMethod === 'noallow') {
                    throw new \UnexpectedValueException("columns '$cname' is same column or alias (cause $this->checkSameMethod).");
                }
                elseif ($this->checkSameMethod === 'strict') {
                    $value = array_pop($values);
                    if (!in_array($value, $values, true)) {
                        throw new \UnexpectedValueException("columns '$cname' is same column or alias (cause $this->checkSameMethod).");
                    }
                }
                elseif ($this->checkSameMethod === 'loose') {
                    if (count(array_unique(array_filter($values, fn($value) => $value !== null))) > 1) {
                        throw new \UnexpectedValueException("columns '$cname' is same column or alias (cause $this->checkSameMethod).");
                    }
                }
                else {
                    throw new \DomainException("checkSameColumn is invalid.");
                }
            }

            $rows[$n] = array_combine($aliasNames, $row);
        }

        if (!$all) {
            return $rows[0];
        }
        return $rows;
    }
}
