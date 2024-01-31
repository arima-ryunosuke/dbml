<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait EntityMethodTrait
{
    /**
     * エンティティ群を配列で返す（{@uses Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::entity()
     */
    public function entityArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->array();
    }

    /**
     * エンティティ群を連想配列で返す（{@uses Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::entity()
     */
    public function entityAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->assoc();
    }

    /**
     * エンティティをオブジェクトで返す（{@uses Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::entity()
     */
    public function entityTuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->tuple();
    }
}
