<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;
use function ryunosuke\dbml\parameter_length;

trait AffectAndPrimaryTrait
{
    private function _invokeAffectAndPrimary($method, $arguments)
    {
        $arity = parameter_length([$this, $method]);
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[$arity] = ($arguments[$arity] ?? []) + ['primary' => 3];
        return $this->$method(...$arguments);
    }

    /**
     * 主キーを返す {@uses Database::insertArray()}
     *
     * @inheritdoc Database::insertArray()
     * @return array|string
     */
    private function insertArrayAndPrimaryWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('insertArray', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::insertArray()}
     *
     * @inheritdoc TableGateway::insertArray()
     * @return array|string
     */
    private function insertArrayAndPrimaryWithoutTable($data)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('insertArray', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     * @return array|string
     */
    private function modifyArrayAndPrimaryWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('modifyArray', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::modifyArray()}
     *
     * @inheritdoc TableGateway::modifyArray()
     * @return array|string
     */
    private function modifyArrayAndPrimaryWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('modifyArray', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     * @return array|string
     */
    private function insertAndPrimaryWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('insert', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     * @return array|string
     */
    private function insertAndPrimaryWithoutTable($data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('insert', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     * @return array|string
     */
    private function updateAndPrimaryWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('update', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     * @return array|string
     */
    private function updateAndPrimaryWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('update', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     * @return array|string
     */
    private function deleteAndPrimaryWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('delete', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     * @return array|string
     */
    private function deleteAndPrimaryWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('delete', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     * @return array|string
     */
    private function invalidAndPrimaryWithTable($tableName, $where, $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('invalid', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     * @return array|string
     */
    private function invalidAndPrimaryWithoutTable($where = [], $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('invalid', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::revise()}
     *
     * @inheritdoc Database::revise()
     * @return array|string
     */
    private function reviseAndPrimaryWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('revise', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::revise()}
     *
     * @inheritdoc TableGateway::revise()
     * @return array|string
     */
    private function reviseAndPrimaryWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('revise', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::upgrade()}
     *
     * @inheritdoc Database::upgrade()
     * @return array|string
     */
    private function upgradeAndPrimaryWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('upgrade', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::upgrade()}
     *
     * @inheritdoc TableGateway::upgrade()
     * @return array|string
     */
    private function upgradeAndPrimaryWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('upgrade', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     * @return array|string
     */
    private function removeAndPrimaryWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('remove', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::remove()}
     *
     * @inheritdoc TableGateway::remove()
     * @return array|string
     */
    private function removeAndPrimaryWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('remove', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     * @return array|string
     */
    private function destroyAndPrimaryWithTable($tableName, $where = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('destroy', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::destroy()}
     *
     * @inheritdoc TableGateway::destroy()
     * @return array|string
     */
    private function destroyAndPrimaryWithoutTable($where = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('destroy', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     * @return array|string
     */
    private function upsertAndPrimaryWithTable($tableName, $insertData, $updateData = [])
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('upsert', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::upsert()}
     *
     * @inheritdoc TableGateway::upsert()
     * @return array|string
     */
    private function upsertAndPrimaryWithoutTable($insertData, $updateData = [])
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('upsert', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndPrimaryWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('modify', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndPrimaryWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('modify', func_get_args());
    }

    /**
     * 主キーを返す {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     * @return array|string
     */
    private function replaceAndPrimaryWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('replace', func_get_args());
    }

    /**
     * 主キーを返す {@uses TableGateway::replace()}
     *
     * @inheritdoc TableGateway::replace()
     * @return array|string
     */
    private function replaceAndPrimaryWithoutTable($data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectAndPrimary('replace', func_get_args());
    }
}
