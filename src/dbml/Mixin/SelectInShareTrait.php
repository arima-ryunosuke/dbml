<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait SelectInShareTrait
{
    /**
     * {@uses Database::selectArray()} の共有ロック版（{@link Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::selectArray()
     */
    public function selectArrayInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArray($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::selectAssoc()} の共有ロック版（{@link Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::selectAssoc()
     */
    public function selectAssocInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssoc($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::selectLists()} の共有ロック版（{@link Database::fetchLists()} も参照）
     *
     * @inheritdoc Database::selectLists()
     */
    public function selectListsInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchLists($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::selectPairs()} の共有ロック版（{@link Database::fetchPairs()} も参照）
     *
     * @inheritdoc Database::selectPairs()
     */
    public function selectPairsInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchPairs($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::selectTuple()} の共有ロック版（{@link Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::selectTuple()
     */
    public function selectTupleInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTuple($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::selectValue()} の共有ロック版（{@link Database::fetchValue()} も参照）
     *
     * @inheritdoc Database::selectValue()
     */
    public function selectValueInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchValue($this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }
}
