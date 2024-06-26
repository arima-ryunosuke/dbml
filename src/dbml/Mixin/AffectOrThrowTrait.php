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
        $arguments[$arity] = ($arguments[$arity] ?? []) + ['primary' => 1];
        return $this->$method(...$arguments);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::insertArray()}
     *
     * 返り値として挿入した主キー配列の配列を返す（自動採番テーブルのみ）。
     * この機能は実験的な機能で、予告なく変更されることがある。
     *
     * @inheritdoc Database::insertArray()
     * @return array|string
     * @throws NonAffectedException
     */
    public function insertArrayOrThrowWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('insertArray', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::insertArray()}
     *
     * @inheritdoc TableGateway::insertArray()
     * @return array|string
     * @throws NonAffectedException
     */
    private function insertArrayOrThrowWithoutTable($data)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('insertArray', func_get_args());
    }

    /**
     * insertOrThrow のエイリアス
     *
     * updateOrThrow や deleteOrThrow を使う機会はそう多くなく、実質的に主キーを得たいがために insertOrThrow を使うことが多い。
     * となると対称性がなく、コードリーディング時に余計な思考を挟むことが多い（「なぜ insert だけ OrThrow なんだろう？」）のでエイリアスを用意した。
     *
     * @inheritdoc Database::insert()
     * @return array|string
     * @throws NonAffectedException
     */
    public function createWithTable($tableName, $data)
    {
        return $this->insertOrThrowWithTable(...func_get_args());
    }

    /**
     * insertOrThrow のエイリアス
     *
     * @inheritdoc TableGateway::insert()
     * @see createWithTable()
     */
    public function createWithoutTable($data)
    {
        return $this->insertOrThrowWithoutTable(...func_get_args());
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
    private function updateOrThrowWithTable($tableName, $data, $where = [])
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
    private function updateOrThrowWithoutTable($data, $where = [])
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
    private function deleteOrThrowWithTable($tableName, $where = [])
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
    private function deleteOrThrowWithoutTable($where = [])
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
    private function invalidOrThrowWithTable($tableName, $where, $invalid_columns = null)
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
    private function invalidOrThrowWithoutTable($where = [], $invalid_columns = null)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('invalid', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::revise()}
     *
     * @inheritdoc Database::revise()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reviseOrThrowWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('revise', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::revise()}
     *
     * @inheritdoc TableGateway::revise()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reviseOrThrowWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('revise', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::upgrade()}
     *
     * @inheritdoc Database::upgrade()
     * @return array|string
     * @throws NonAffectedException
     */
    private function upgradeOrThrowWithTable($tableName, $data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('upgrade', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::upgrade()}
     *
     * @inheritdoc TableGateway::upgrade()
     * @return array|string
     * @throws NonAffectedException
     */
    private function upgradeOrThrowWithoutTable($data, $where = [])
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->_invokeAffectOrThrow('upgrade', func_get_args());
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     * @return array|string
     * @throws NonAffectedException
     */
    private function removeOrThrowWithTable($tableName, $where = [])
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
    private function removeOrThrowWithoutTable($where = [])
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
    private function destroyOrThrowWithTable($tableName, $where = [])
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
    private function destroyOrThrowWithoutTable($where = [])
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
    private function reduceOrThrowWithTable($tableName, $limit = null, $orderBy = [], $groupBy = [], $where = [])
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
    private function reduceOrThrowWithoutTable($limit = null, $orderBy = [], $groupBy = [], $where = [])
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
