<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait SelectForAffectTrait
{
    /**
     * {@uses Database::selectArray()} の排他ロック兼例外送出版（{@link Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::selectArray()
     */
    public function selectArrayForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArrayOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::selectAssoc()} の排他ロック兼例外送出版（{@link Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::selectAssoc()
     */
    public function selectAssocForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssocOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::selectLists()} の排他ロック兼例外送出版（{@link Database::fetchLists()} も参照）
     *
     * @inheritdoc Database::selectLists()
     */
    public function selectListsForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchListsOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::selectPairs()} の排他ロック兼例外送出版（{@link Database::fetchPairs()} も参照）
     *
     * @inheritdoc Database::selectPairs()
     */
    public function selectPairsForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchPairsOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::selectTuple()} の排他ロック兼例外送出版（{@link Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::selectTuple()
     */
    public function selectTupleForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::selectValue()} の排他ロック兼例外送出版（{@link Database::fetchValue()} も参照）
     *
     * @inheritdoc Database::selectValue()
     */
    public function selectValueForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchValueOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }
}
