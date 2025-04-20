<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Gateway\TableGateway;

trait FindTrait
{
    /**
     * 主キー指定でレコードを取得する
     *
     * @inheritdoc TableGateway::selectFind()
     */
    #[AssumeType('entity', 'shape')]
    public function find($variadic_primary, $tableDescriptor = [])
    {
        return $this->getDatabase()->fetchTuple($this->selectFind(...func_get_args()));
    }

    /**
     * {@uses TableGateway::find()} の例外送出版
     *
     * @inheritdoc TableGateway::find()
     */
    #[AssumeType('entity', 'shape')]
    public function findOrThrow($variadic_primary, $tableDescriptor = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->selectFind(...func_get_args()));
    }

    /**
     * {@uses TableGateway::find()} の共有ロック版
     *
     * @inheritdoc TableGateway::find()
     */
    #[AssumeType('entity', 'shape')]
    public function findInShare($variadic_primary, $tableDescriptor = [])
    {
        return $this->getDatabase()->fetchTuple($this->selectFind(...func_get_args())->lockInShare());
    }

    /**
     * {@uses TableGateway::find()} の排他ロック版
     *
     * @inheritdoc TableGateway::find()
     */
    #[AssumeType('entity', 'shape')]
    public function findForUpdate($variadic_primary, $tableDescriptor = [])
    {
        return $this->getDatabase()->fetchTuple($this->selectFind(...func_get_args())->lockForUpdate());
    }

    /**
     * {@uses TableGateway::find()} の排他ロック兼例外送出版
     *
     * @inheritdoc TableGateway::find()
     */
    #[AssumeType('entity', 'shape')]
    public function findForAffect($variadic_primary, $tableDescriptor = [])
    {
        return $this->getDatabase()->fetchTupleOrThrow($this->selectFind(...func_get_args())->lockForUpdate());
    }
}
