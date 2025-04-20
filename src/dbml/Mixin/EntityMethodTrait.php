<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;

trait EntityMethodTrait
{
    /**
     * エンティティ群を配列で返す（{@uses Database::fetchArray()} も参照）
     *
     * @inheritdoc Database::entity()
     */
    #[AssumeType('entities')]
    public function entityArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->array();
    }

    /**
     * エンティティ群を連想配列で返す（{@uses Database::fetchAssoc()} も参照）
     *
     * @inheritdoc Database::entity()
     */
    #[AssumeType('entities')]
    public function entityAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->assoc();
    }

    /**
     * エンティティをオブジェクトで返す（{@uses Database::fetchTuple()} も参照）
     *
     * @inheritdoc Database::entity()
     */
    #[AssumeType('entity')]
    public function entityTuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->tuple();
    }
}
