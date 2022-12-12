<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait EntityOrThrowTrait
{
    /**
     * {@uses Database::entityArray()} の例外送出版
     *
     * @inheritdoc Database::entityArray()
     */
    public function entityArrayOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArrayOrThrow($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::entityAssoc()} の例外送出版
     *
     * @inheritdoc Database::entityAssoc()
     */
    public function entityAssocOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssocOrThrow($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }

    /**
     * {@uses Database::entityTuple()} の例外送出版
     *
     * @inheritdoc Database::entityTuple()
     */
    public function entityTupleOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having));
    }
}
