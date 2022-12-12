<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait SelectOrThrowTrait
{
    /**
     * {@uses Database::selectArray()} の例外送出版（{@link Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::selectArray()
     */
    public function selectArrayOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArrayOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectAssoc()} の例外送出版（{@link Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::selectAssoc()
     */
    public function selectAssocOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssocOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectLists()} の例外送出版（{@link Database::fetchLists()} も参照）
     *
     * @inheritdoc Database::selectLists()
     */
    public function selectListsOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchListsOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectPairs()} の例外送出版（{@link Database::fetchPairs()} も参照）
     *
     * @inheritdoc Database::selectPairs()
     */
    public function selectPairsOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchPairsOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectTuple()} の例外送出版（{@link Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::selectTuple()
     */
    public function selectTupleOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectValue()} の例外送出版（{@link Database::fetchValue()} も参照）
     *
     * @inheritdoc Database::selectValue()
     */
    public function selectValueOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchValueOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }
}
