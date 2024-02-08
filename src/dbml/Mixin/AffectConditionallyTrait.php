<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;
use function ryunosuke\dbml\parameter_length;

trait AffectConditionallyTrait
{
    abstract protected function getConditionPosition();

    private function _invokeAffectConditionally($method, $arguments)
    {
        $condition = $arguments[$this->getConditionPosition()];
        unset($arguments[$this->getConditionPosition()]);
        $arguments = array_values($arguments);

        $arity = parameter_length([$this, $method]);
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[$arity] = ['primary' => 2, 'where' => $condition] + ($arguments[$arity] ?? []);
        return $this->$method(...$arguments);
    }

    /**
     * 条件付き {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     * @return array|string
     */
    private function insertConditionallyWithTable($tableName, $condition, $data)
    {
        assert(array_values(parameter_default([$this, 'insert'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('insert', func_get_args());
    }

    /**
     * 条件付き {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     * @return array|string
     */
    private function insertConditionallyWithoutTable($condition, $data)
    {
        assert(array_values(parameter_default([$this, 'insert'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('insert', func_get_args());
    }

    /**
     * 条件付き {@uses Database::create()}
     *
     * @inheritdoc Database::create()
     * @return array|string
     */
    private function createConditionallyWithTable($tableName, $condition, $data)
    {
        assert(array_values(parameter_default([$this, 'create'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('create', func_get_args());
    }

    /**
     * 条件付き {@uses TableGateway::create()}
     *
     * @inheritdoc TableGateway::create()
     * @return array|string
     */
    private function createConditionallyWithoutTable($condition, $data)
    {
        assert(array_values(parameter_default([$this, 'create'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('create', func_get_args());
    }

    /**
     * 条件付き {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     * @return array|string
     */
    private function upsertConditionallyWithTable($tableName, $condition, $insertData, $updateData = [])
    {
        assert(array_values(parameter_default([$this, 'upsert'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('upsert', func_get_args());
    }

    /**
     * 条件付き {@uses TableGateway::upsert()}
     *
     * @inheritdoc TableGateway::upsert()
     * @return array|string
     */
    private function upsertConditionallyWithoutTable($condition, $insertData, $updateData = [])
    {
        assert(array_values(parameter_default([$this, 'upsert'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('upsert', func_get_args());
    }

    /**
     * 条件付き {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyConditionallyWithTable($tableName, $condition, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(array_values(parameter_default([$this, 'modify'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('modify', func_get_args());
    }

    /**
     * 条件付き {@uses TableGateway::modify()}
     *
     * @inheritdoc TableGateway::modify()
     * @return array|string
     */
    private function modifyConditionallyWithoutTable($condition, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(array_values(parameter_default([$this, 'modify'])) === array_values(parameter_default([$this, __FUNCTION__])));
        return $this->_invokeAffectConditionally('modify', func_get_args());
    }
}
