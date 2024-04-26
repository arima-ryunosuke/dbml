<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;
use function ryunosuke\dbml\parameter_length;

trait AffectIgnoreTrait
{
    private function _invokeAffectIgnore($method, $arguments)
    {
        $arity = parameter_length([$this, $method]);
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[$arity] = ['primary' => 2, 'ignore' => true] + ($arguments[$arity] ?? []);
        return $this->$method(...$arguments);
    }

    /**
     * IGNORE 付き {@uses Database::insertSelect()}
     *
     * @inheritdoc Database::insertSelect()
     */
    private function insertSelectIgnoreWithTable($tableName, $sql, $columns = [], iterable $params = [])
    {
        assert(parameter_default([$this, 'insertSelect']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('insertSelect', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::insertSelect()}
     *
     * @inheritdoc TableGateway::insertSelect()
     */
    private function insertSelectIgnoreWithoutTable($sql, $columns = [], iterable $params = [])
    {
        assert(parameter_default([$this, 'insertSelect']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('insertSelect', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::insertArray()}
     *
     * @inheritdoc Database::insertArray()
     */
    private function insertArrayIgnoreWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('insertArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::insertArray()}
     *
     * @inheritdoc TableGateway::insertArray()
     */
    private function insertArrayIgnoreWithoutTable($data)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('insertArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::updateArray()}
     *
     * @inheritdoc Database::updateArray()
     */
    private function updateArrayIgnoreWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('updateArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::updateArray()}
     *
     * @inheritdoc TableGateway::updateArray()
     */
    private function updateArrayIgnoreWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('updateArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     */
    private function modifyArrayIgnoreWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('modifyArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::modifyArray()}
     *
     * @inheritdoc TableGateway::modifyArray()
     */
    private function modifyArrayIgnoreWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('modifyArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::changeArray()}
     *
     * @inheritdoc Database::changeArray()
     */
    private function changeArrayIgnoreWithTable($tableName, $dataarray, $where, $uniquekey = 'PRIMARY', $returning = [])
    {
        assert(parameter_default([$this, 'changeArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('changeArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::changeArray()}
     *
     * @inheritdoc TableGateway::changeArray()
     */
    private function changeArrayIgnoreWithoutTable($dataarray, $where, $uniquekey = 'PRIMARY', $returning = [])
    {
        assert(parameter_default([$this, 'changeArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('changeArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::affectArray()}
     *
     * @inheritdoc Database::affectArray()
     */
    private function affectArrayIgnoreWithTable($tableName, $dataarray)
    {
        assert(parameter_default([$this, 'affectArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('affectArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::affectArray()}
     *
     * @inheritdoc TableGateway::affectArray()
     */
    private function affectArrayIgnoreWithoutTable($dataarray)
    {
        assert(parameter_default([$this, 'affectArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('affectArray', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::save()}
     *
     * @inheritdoc Database::save()
     */
    private function saveIgnoreWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'save']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('save', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::save()}
     *
     * @inheritdoc TableGateway::save()
     */
    private function saveIgnoreWithoutTable($data)
    {
        assert(parameter_default([$this, 'save']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('save', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     */
    private function insertIgnoreWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('insert', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     */
    private function insertIgnoreWithoutTable($data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('insert', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     */
    private function updateIgnoreWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('update', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     */
    private function updateIgnoreWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('update', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     */
    private function deleteIgnoreWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('delete', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     */
    private function deleteIgnoreWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('delete', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     */
    private function invalidIgnoreWithTable($tableName, $where, $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('invalid', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     */
    private function invalidIgnoreWithoutTable($where = [], $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('invalid', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::revise()}
     *
     * @inheritdoc Database::revise()
     */
    private function reviseIgnoreWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('revise', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::revise()}
     *
     * @inheritdoc TableGateway::revise()
     */
    private function reviseIgnoreWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('revise', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::upgrade()}
     *
     * @inheritdoc Database::upgrade()
     */
    private function upgradeIgnoreWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('upgrade', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::upgrade()}
     *
     * @inheritdoc TableGateway::upgrade()
     */
    private function upgradeIgnoreWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('upgrade', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     */
    private function removeIgnoreWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('remove', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::remove()}
     *
     * @inheritdoc TableGateway::remove()
     */
    private function removeIgnoreWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('remove', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     */
    private function destroyIgnoreWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('destroy', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::destroy()}
     *
     * @inheritdoc TableGateway::destroy()
     */
    private function destroyIgnoreWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('destroy', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::create()}
     *
     * @inheritdoc Database::create()
     */
    private function createIgnoreWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'create']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('create', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::create()}
     *
     * @inheritdoc TableGateway::create()
     */
    private function createIgnoreWithoutTable($data)
    {
        assert(parameter_default([$this, 'create']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('create', func_get_args());
    }

    /**
     * IGNORE 付き {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     */
    private function modifyIgnoreWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('modify', func_get_args());
    }

    /**
     * IGNORE 付き {@uses TableGateway::modify()}
     *
     * @inheritdoc TableGateway::modify()
     */
    private function modifyIgnoreWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectIgnore('modify', func_get_args());
    }
}
