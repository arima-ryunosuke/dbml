<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;
use function ryunosuke\dbml\parameter_length;

trait AffectOrThrowTrait
{
    private function _invokeAffectOrThrow($method, $arguments)
    {
        $arity = parameter_length([$this, $method]);
        $arguments = parameter_default([$this, $method], $arguments);
        $arguments[$arity] = ['primary' => 1, 'throw' => true] + ($arguments[$arity] ?? []);
        return $this->$method(...$arguments);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     * @return array|string
     * @throws NonAffectedException
     */
    private function insertOrThrowWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('insert', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     * @return array|string
     * @throws NonAffectedException
     */
    private function insertOrThrowWithoutTable($data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('insert', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     * @return array|string
     * @throws NonAffectedException
     */
    private function updateOrThrowWithTable($tableName, $data, $identifier = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('update', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     * @return array|string
     * @throws NonAffectedException
     */
    private function updateOrThrowWithoutTable($data, $identifier = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('update', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     * @return array|string
     * @throws NonAffectedException
     */
    private function deleteOrThrowWithTable($tableName, $identifier = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('delete', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     * @return array|string
     * @throws NonAffectedException
     */
    private function deleteOrThrowWithoutTable($identifier = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('delete', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     * @return array|string
     * @throws NonAffectedException
     */
    private function invalidOrThrowWithTable($tableName, $identifier, $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('invalid', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     * @return array|string
     * @throws NonAffectedException
     */
    private function invalidOrThrowWithoutTable($identifier = [], $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('invalid', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     * @return array|string
     * @throws NonAffectedException
     */
    private function removeOrThrowWithTable($tableName, $identifier = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('remove', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::remove()}
     *
     * @inheritdoc TableGateway::remove()
     * @return array|string
     * @throws NonAffectedException
     */
    private function removeOrThrowWithoutTable($identifier = [])
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('remove', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     * @return array|string
     * @throws NonAffectedException
     */
    private function destroyOrThrowWithTable($tableName, $identifier = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('destroy', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::destroy()}
     *
     * @inheritdoc TableGateway::destroy()
     * @return array|string
     * @throws NonAffectedException
     */
    private function destroyOrThrowWithoutTable($identifier = [])
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('destroy', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::reduce()}
     *
     * @inheritdoc Database::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceOrThrowWithTable($tableName, $limit = null, $orderBy = [], $groupBy = [], $identifier = [])
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('reduce', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::reduce()}
     *
     * @inheritdoc TableGateway::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceOrThrowWithoutTable($limit = null, $orderBy = [], $groupBy = [], $identifier = [])
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('reduce', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     * @return array|string
     * @throws NonAffectedException
     */
    private function upsertOrThrowWithTable($tableName, $insertData, $updateData = [])
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('upsert', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::upsert()}
     *
     * @inheritdoc TableGateway::upsert()
     * @return array|string
     * @throws NonAffectedException
     */
    private function upsertOrThrowWithoutTable($insertData, $updateData = [])
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('upsert', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     * @throws NonAffectedException
     */
    private function modifyOrThrowWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('modify', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::modify()}
     *
     * @inheritdoc TableGateway::modify()
     * @return array|string
     * @throws NonAffectedException
     */
    private function modifyOrThrowWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('modify', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     * @return array|string
     * @throws NonAffectedException
     */
    private function replaceOrThrowWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('replace', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::replace()}
     *
     * @inheritdoc TableGateway::replace()
     * @return array|string
     * @throws NonAffectedException
     */
    private function replaceOrThrowWithoutTable($data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('replace', func_get_args());
    }
}
