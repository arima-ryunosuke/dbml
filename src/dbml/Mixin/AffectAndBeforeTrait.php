<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;
use function ryunosuke\dbml\parameter_length;

trait AffectAndBeforeTrait
{
    private function _invokeAffectAndBefore($method, $arguments)
    {
        $arity = parameter_length([$this, $method]);
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[$arity] = ($arguments[$arity] ?? []) + ['return' => 1];
        return $this->$method(...$arguments);
    }

    /**
     * レコードを返す {@uses Database::updateArray()}
     *
     * @inheritdoc Database::updateArray()
     */
    private function updateArrayAndBeforeWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('updateArray', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::updateArray()}
     *
     * @inheritdoc TableGateway::updateArray()
     */
    private function updateArrayAndBeforeWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('updateArray', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::deleteArray()}
     *
     * @inheritdoc Database::deleteArray()
     */
    private function deleteArrayAndBeforeWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'deleteArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('deleteArray', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::deleteArray()}
     *
     * @inheritdoc TableGateway::deleteArray()
     */
    private function deleteArrayAndBeforeWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'deleteArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('deleteArray', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     */
    private function modifyArrayAndBeforeWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('modifyArray', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::modifyArray()}
     *
     * @inheritdoc TableGateway::modifyArray()
     */
    private function modifyArrayAndBeforeWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('modifyArray', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     * @return array|string
     */
    private function updateAndBeforeWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('update', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     * @return array|string
     */
    private function updateAndBeforeWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('update', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     * @return array|string
     */
    private function deleteAndBeforeWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('delete', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     * @return array|string
     */
    private function deleteAndBeforeWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('delete', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     * @return array|string
     */
    private function invalidAndBeforeWithTable($tableName, $where, $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('invalid', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     * @return array|string
     */
    private function invalidAndBeforeWithoutTable($where = [], $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('invalid', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::revise()}
     *
     * @inheritdoc Database::revise()
     * @return array|string
     */
    private function reviseAndBeforeWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('revise', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::revise()}
     *
     * @inheritdoc TableGateway::revise()
     * @return array|string
     */
    private function reviseAndBeforeWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('revise', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::upgrade()}
     *
     * @inheritdoc Database::upgrade()
     * @return array|string
     */
    private function upgradeAndBeforeWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('upgrade', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::upgrade()}
     *
     * @inheritdoc TableGateway::upgrade()
     * @return array|string
     */
    private function upgradeAndBeforeWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('upgrade', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     * @return array|string
     */
    private function removeAndBeforeWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('remove', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::remove()}
     *
     * @inheritdoc TableGateway::remove()
     * @return array|string
     */
    private function removeAndBeforeWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('remove', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     * @return array|string
     */
    private function destroyAndBeforeWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('destroy', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::destroy()}
     *
     * @inheritdoc TableGateway::destroy()
     * @return array|string
     */
    private function destroyAndBeforeWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('destroy', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::reduce()}
     *
     * @inheritdoc Database::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceAndBeforeWithTable($tableName, $limit = null, $orderBy = [], $groupBy = [], $where = [])
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('reduce', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::reduce()}
     *
     * @inheritdoc TableGateway::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceAndBeforeWithoutTable($limit = null, $orderBy = [], $groupBy = [], $where = [])
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('reduce', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     * @return array|string
     */
    private function upsertAndBeforeWithTable($tableName, $insertData, $updateData = [])
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('upsert', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::upsert()}
     *
     * @inheritdoc TableGateway::upsert()
     * @return array|string
     */
    private function upsertAndBeforeWithoutTable($insertData, $updateData = [])
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('upsert', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndBeforeWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('modify', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndBeforeWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('modify', func_get_args());
    }

    /**
     * レコードを返す {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     * @return array|string
     */
    private function replaceAndBeforeWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('replace', func_get_args());
    }

    /**
     * レコードを返す {@uses TableGateway::replace()}
     *
     * @inheritdoc TableGateway::replace()
     * @return array|string
     */
    private function replaceAndBeforeWithoutTable($data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndBefore('replace', func_get_args());
    }
}
