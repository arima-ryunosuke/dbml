<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait EntityForUpdateTrait
{
    /**
     * {@uses Database::entityArray()} の排他ロック版
     *
     * @inheritdoc Database::entityArray()
     */
    public function entityArrayForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArray($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::entityAssoc()} の排他ロック版
     *
     * @inheritdoc Database::entityAssoc()
     */
    public function entityAssocForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssoc($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::entityTuple()} の排他ロック版
     *
     * @inheritdoc Database::entityTuple()
     */
    public function entityTupleForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTuple($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }
}
