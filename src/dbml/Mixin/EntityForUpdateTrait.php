<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;

trait EntityForUpdateTrait
{
    /**
     * {@uses Database::entityArray()} の排他ロック版
     *
     * @inheritdoc Database::entityArray()
     */
    #[AssumeType('entities')]
    public function entityArrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->array();
    }

    /**
     * {@uses Database::entityAssoc()} の排他ロック版
     *
     * @inheritdoc Database::entityAssoc()
     */
    #[AssumeType('entities')]
    public function entityAssocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->assoc();
    }

    /**
     * {@uses Database::entityTuple()} の排他ロック版
     *
     * @inheritdoc Database::entityTuple()
     */
    #[AssumeType('entity')]
    public function entityTupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate()->tuple();
    }
}
