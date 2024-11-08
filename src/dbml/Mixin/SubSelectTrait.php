<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait SubSelectTrait
{
    /**
     * 子供レコード（array）を表すサブビルダを返す（{@uses Database::subselect()} を参照）
     *
     * @inheritdoc Database::subselect()
     */
    private function subselectArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->subselect($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->array();
    }

    /**
     * 子供レコード（assoc）を表すサブビルダを返す（{@uses Database::subselect()} を参照）
     *
     * @inheritdoc Database::subselect()
     */
    private function subselectAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->subselect($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->assoc();
    }

    /**
     * 子供レコード（lists）を表すサブビルダを返す（{@uses Database::subselect()} を参照）
     *
     * @inheritdoc Database::subselect()
     */
    private function subselectLists($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->subselect($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lists();
    }

    /**
     * 子供レコード（pairs）を表すサブビルダを返す（{@uses Database::subselect()} を参照）
     *
     * @inheritdoc Database::subselect()
     */
    private function subselectPairs($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->subselect($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->pairs();
    }

    /**
     * 子供レコード（tuple）を表すサブビルダを返す（{@uses Database::subselect()} を参照）
     *
     * @inheritdoc Database::subselect()
     */
    private function subselectTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->subselect($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->tuple();
    }

    /**
     * 子供レコード（value）を表すサブビルダを返す（{@uses Database::subselect()} を参照）
     *
     * @inheritdoc Database::subselect()
     */
    private function subselectValue($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->subselect($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->value();
    }
}
