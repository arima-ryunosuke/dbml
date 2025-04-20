<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;

trait SelectOrThrowTrait
{
    /**
     * {@uses Database::selectArray()} の例外送出版（{@link Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::selectArray()
     */
    #[AssumeType('entities', 'shapes')]
    private function selectArrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArrayOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectAssoc()} の例外送出版（{@link Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::selectAssoc()
     */
    #[AssumeType('entities', 'shapes')]
    private function selectAssocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssocOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectLists()} の例外送出版（{@link Database::fetchLists()} も参照）
     *
     * @inheritdoc Database::selectLists()
     */
    private function selectListsOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchListsOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectPairs()} の例外送出版（{@link Database::fetchPairs()} も参照）
     *
     * @inheritdoc Database::selectPairs()
     */
    private function selectPairsOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchPairsOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectTuple()} の例外送出版（{@link Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::selectTuple()
     */
    #[AssumeType('entity', 'shape')]
    private function selectTupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::selectValue()} の例外送出版（{@link Database::fetchValue()} も参照）
     *
     * @inheritdoc Database::selectValue()
     */
    private function selectValueOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchValueOrThrow($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }
}
