<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait YieldTrait
{
    /**
     * レコード群を配列で少しずつ返す（{@uses Database::yieldArray()} を参照）
     *
     * @inheritdoc Database::yieldArray()
     */
    public function yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->yield($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having))->setFetchMethod('array');
    }

    /**
     * レコード群を連想配列で少しずつ返す（{@uses Database::yieldAssoc()} を参照）
     *
     * @inheritdoc Database::yieldAssoc()
     */
    public function yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->yield($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having))->setFetchMethod('assoc');
    }

    /**
     * レコード群を[value]で少しずつ返す（{@uses Database::yieldLists()} を参照）
     *
     * @inheritdoc Database::yieldLists()
     */
    public function yieldLists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->yield($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having))->setFetchMethod('lists');
    }

    /**
     * レコード群を[key => value]で少しずつ返す（{@uses Database::yieldPairs()} を参照）
     *
     * @inheritdoc Database::yieldPairs()
     */
    public function yieldPairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->yield($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having))->setFetchMethod('pairs');
    }
}