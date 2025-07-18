<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\SelectBuilder;

trait YieldTrait
{
    private function _convertSelectBuilder($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)
    {
        if ($tableDescriptor instanceof SelectBuilder) {
            return $tableDescriptor->build(array_combine(SelectBuilder::CLAUSES, [[], $where, $orderBy, $limit, $groupBy, $having]), true);
        }
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having);
    }

    /**
     * レコード群を配列で少しずつ返す（{@uses Database::yieldArray()} を参照）
     *
     * @inheritdoc Database::yieldArray()
     */
    #[AssumeType('iterable')]
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [], ...$opt)
    {
        return $this->_convertSelectBuilder($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->yield($opt['chunk'] ?? null, 'array');
    }

    /**
     * レコード群を連想配列で少しずつ返す（{@uses Database::yieldAssoc()} を参照）
     *
     * @inheritdoc Database::yieldAssoc()
     */
    #[AssumeType('iterable')]
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [], ...$opt)
    {
        return $this->_convertSelectBuilder($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->yield($opt['chunk'] ?? null, 'assoc');
    }

    /**
     * レコード群を[value]で少しずつ返す（{@uses Database::yieldLists()} を参照）
     *
     * @inheritdoc Database::yieldLists()
     */
    public function yieldLists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [], ...$opt)
    {
        return $this->_convertSelectBuilder($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->yield($opt['chunk'] ?? null, 'lists');
    }

    /**
     * レコード群を[key => value]で少しずつ返す（{@uses Database::yieldPairs()} を参照）
     *
     * @inheritdoc Database::yieldPairs()
     */
    public function yieldPairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [], ...$opt)
    {
        return $this->_convertSelectBuilder($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->yield($opt['chunk'] ?? null, 'pairs');
    }
}
