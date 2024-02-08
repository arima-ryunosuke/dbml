<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Query\Statement;
use function ryunosuke\dbml\parameter_default;
use function ryunosuke\dbml\try_finally;

trait PrepareTrait
{
    /**
     * クエリビルダ構文で SELECT 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::select()} も参照）
     *
     * @inheritdoc Database::select()
     * @return Statement
     */
    public function prepareSelect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->prepare($this->select(...func_get_args()));
    }

    /**
     * クエリビルダ構文で INSERT 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::insert()} も参照）
     *
     * @inheritdoc Database::insert()
     * @return Statement
     */
    private function prepareInsertWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'insert'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で INSERT 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses TableGateway::insert()} も参照）
     *
     * @inheritdoc TableGateway::insert()
     * @return Statement
     */
    private function prepareInsertWithoutTable($data)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'insert'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で UPDATE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::update()} も参照）
     *
     * @inheritdoc Database::update()
     * @return Statement
     */
    private function prepareUpdateWithTable($tableName, $data, $identifier = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'update'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で UPDATE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses TableGateway::update()} も参照）
     *
     * @inheritdoc TableGateway::update()
     * @return Statement
     */
    private function prepareUpdateWithoutTable($data, $identifier = [])
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'update'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で DELETE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::delete()} も参照）
     *
     * @inheritdoc Database::delete()
     * @return Statement
     */
    private function prepareDeleteWithTable($tableName, $identifier = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'delete'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で DELETE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses TableGateway::delete()} も参照）
     *
     * @inheritdoc TableGateway::delete()
     * @return Statement
     */
    private function prepareDeleteWithoutTable($identifier = [])
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'delete'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で MODEFIY 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::modify()} も参照）
     *
     * @inheritdoc Database::modify()
     * @return Statement
     */
    private function prepareModifyWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'modify'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で MODEFIY 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses TableGateway::modify()} も参照）
     *
     * @inheritdoc TableGateway::modify()
     * @return Statement
     */
    private function prepareModifyWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'modify'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で REPLACE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::replace()} も参照）
     *
     * @inheritdoc Database::replace()
     * @return Statement
     */
    private function prepareReplaceWithTable($tableName, $data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'replace'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }

    /**
     * クエリビルダ構文で REPLACE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses TableGateway::replace()} も参照）
     *
     * @inheritdoc TableGateway::replace()
     * @return Statement
     */
    private function prepareReplaceWithoutTable($data)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return try_finally([$this, 'replace'], $this->getDatabase()->storeOptions(['preparing' => true]), ...func_get_args());
    }
}
