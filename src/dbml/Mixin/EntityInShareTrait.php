<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait EntityInShareTrait
{
    /**
     * {@uses Database::entityArray()} の共有ロック版
     *
     * @inheritdoc Database::entityArray()
     */
    public function entityArrayInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare()->array();
    }

    /**
     * {@uses Database::entityAssoc()} の共有ロック版
     *
     * @inheritdoc Database::entityAssoc()
     */
    public function entityAssocInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare()->assoc();
    }

    /**
     * {@uses Database::entityTuple()} の共有ロック版
     *
     * @inheritdoc Database::entityTuple()
     */
    public function entityTupleInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare()->tuple();
    }
}
