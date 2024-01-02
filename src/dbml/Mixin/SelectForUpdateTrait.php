<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait SelectForUpdateTrait
{
    /**
     * {@uses Database::selectArray()} の排他ロック版（{@link Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::selectArray()
     */
    public function selectArrayForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->array();
    }

    /**
     * {@uses Database::selectAssoc()} の排他ロック版（{@link Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::selectAssoc()
     */
    public function selectAssocForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->assoc();
    }

    /**
     * {@uses Database::selectLists()} の排他ロック版（{@link Database::fetchLists()} も参照）
     *
     * @inheritdoc Database::selectLists()
     */
    public function selectListsForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->lists();
    }

    /**
     * {@uses Database::selectPairs()} の排他ロック版（{@link Database::fetchPairs()} も参照）
     *
     * @inheritdoc Database::selectPairs()
     */
    public function selectPairsForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->pairs();
    }

    /**
     * {@uses Database::selectTuple()} の排他ロック版（{@link Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::selectTuple()
     */
    public function selectTupleForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->tuple();
    }

    /**
     * {@uses Database::selectValue()} の排他ロック版（{@link Database::fetchValue()} も参照）
     *
     * @inheritdoc Database::selectValue()
     */
    public function selectValueForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->value();
    }
}
