<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait EntityForAffectTrait
{
    /**
     * {@uses Database::entityArray()} の排他ロック兼例外送出版
     *
     * @inheritdoc Database::entityArray()
     */
    public function entityArrayForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchArrayOrThrow($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::entityAssoc()} の排他ロック兼例外送出版
     *
     * @inheritdoc Database::entityAssoc()
     */
    public function entityAssocForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchAssocOrThrow($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }

    /**
     * {@uses Database::entityTuple()} の排他ロック兼例外送出版
     *
     * @inheritdoc Database::entityTuple()
     */
    public function entityTupleForAffect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForUpdate());
    }
}
