<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait SelectMethodTrait
{
    /**
     * レコード群を配列で返す（{@uses Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function selectArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->array();
    }

    /**
     * レコード群を連想配列で返す（{@uses Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function selectAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->assoc();
    }

    /**
     * レコード群を[value]で返す（{@uses Database::fetchLists()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function selectLists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lists();
    }

    /**
     * レコード群を[key => value]で返す（{@uses Database::fetchPairs()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function selectPairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->pairs();
    }

    /**
     * レコードを配列で返す（{@uses Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function selectTuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->tuple();
    }

    /**
     * カラム値をスカラーで返す（{@uses Database::fetchValue()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function selectValue($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->value();
    }
}
