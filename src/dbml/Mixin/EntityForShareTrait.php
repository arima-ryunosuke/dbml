<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;

trait EntityForShareTrait
{
    /**
     * {@uses Database::entityArray()} の共有ロック版
     *
     * @inheritdoc Database::entityArray()
     */
    #[AssumeType('entities')]
    public function entityArrayForShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForShare()->array();
    }

    /**
     * {@uses Database::entityAssoc()} の共有ロック版
     *
     * @inheritdoc Database::entityAssoc()
     */
    #[AssumeType('entities')]
    public function entityAssocForShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForShare()->assoc();
    }

    /**
     * {@uses Database::entityTuple()} の共有ロック版
     *
     * @inheritdoc Database::entityTuple()
     */
    #[AssumeType('entity')]
    public function entityTupleForShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lockForShare()->tuple();
    }
}
