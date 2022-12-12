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
        return $this->getDatabase()->fetchArray($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::entityAssoc()} の共有ロック版
     *
     * @inheritdoc Database::entityAssoc()
     */
    public function entityAssocInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssoc($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }

    /**
     * {@uses Database::entityTuple()} の共有ロック版
     *
     * @inheritdoc Database::entityTuple()
     */
    public function entityTupleInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTuple($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockInShare());
    }
}
