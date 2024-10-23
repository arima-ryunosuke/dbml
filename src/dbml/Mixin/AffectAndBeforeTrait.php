<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;

trait AffectAndBeforeTrait
{
    /**
     * レコードを返す {@uses Database::updateArray()}
     *
     * @inheritdoc Database::updateArray()
     */
    private function updateArrayAndBeforeWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateArray($tableName, $data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::updateArray()}
     *
     * @inheritdoc TableGateway::updateArray()
     */
    private function updateArrayAndBeforeWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateArray($data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::deleteArray()}
     *
     * @inheritdoc Database::deleteArray()
     */
    private function deleteArrayAndBeforeWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteArray($tableName, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::deleteArray()}
     *
     * @inheritdoc TableGateway::deleteArray()
     */
    private function deleteArrayAndBeforeWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteArray($where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     */
    private function modifyArrayAndBeforeWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->modifyArray($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::modifyArray()}
     *
     * @inheritdoc TableGateway::modifyArray()
     */
    private function modifyArrayAndBeforeWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->modifyArray($insertData, $updateData, $uniquekey, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     * @return array|string
     */
    private function updateAndBeforeWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($tableName, $data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     * @return array|string
     */
    private function updateAndBeforeWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     * @return array|string
     */
    private function deleteAndBeforeWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($tableName, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     * @return array|string
     */
    private function deleteAndBeforeWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     * @return array|string
     */
    private function invalidAndBeforeWithTable($tableName, $where, $invalid_columns = null, ...$opt)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($tableName, $where, $invalid_columns, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     * @return array|string
     */
    private function invalidAndBeforeWithoutTable($where = [], $invalid_columns = null, ...$opt)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($where, $invalid_columns, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::revise()}
     *
     * @inheritdoc Database::revise()
     * @return array|string
     */
    private function reviseAndBeforeWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->revise($tableName, $data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::revise()}
     *
     * @inheritdoc TableGateway::revise()
     * @return array|string
     */
    private function reviseAndBeforeWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->revise($data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::upgrade()}
     *
     * @inheritdoc Database::upgrade()
     * @return array|string
     */
    private function upgradeAndBeforeWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->upgrade($tableName, $data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::upgrade()}
     *
     * @inheritdoc TableGateway::upgrade()
     * @return array|string
     */
    private function upgradeAndBeforeWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->upgrade($data, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     * @return array|string
     */
    private function removeAndBeforeWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->remove($tableName, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::remove()}
     *
     * @inheritdoc TableGateway::remove()
     * @return array|string
     */
    private function removeAndBeforeWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->remove($where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     * @return array|string
     */
    private function destroyAndBeforeWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->destroy($tableName, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::destroy()}
     *
     * @inheritdoc TableGateway::destroy()
     * @return array|string
     */
    private function destroyAndBeforeWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->destroy($where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::reduce()}
     *
     * @inheritdoc Database::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceAndBeforeWithTable($tableName, $limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->reduce($tableName, $limit, $orderBy, $groupBy, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::reduce()}
     *
     * @inheritdoc TableGateway::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceAndBeforeWithoutTable($limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->reduce($limit, $orderBy, $groupBy, $where, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     * @return array|string
     */
    private function upsertAndBeforeWithTable($tableName, $insertData, $updateData = [], ...$opt)
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->upsert($tableName, $insertData, $updateData, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::upsert()}
     *
     * @inheritdoc TableGateway::upsert()
     * @return array|string
     */
    private function upsertAndBeforeWithoutTable($insertData, $updateData = [], ...$opt)
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->upsert($insertData, $updateData, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndBeforeWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndBeforeWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($insertData, $updateData, $uniquekey, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     * @return array|string
     */
    private function replaceAndBeforeWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->replace($tableName, $data, ...($opt + ['return' => 1]));
    }

    /**
     * レコードを返す {@uses TableGateway::replace()}
     *
     * @inheritdoc TableGateway::replace()
     * @return array|string
     */
    private function replaceAndBeforeWithoutTable($data, ...$opt)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->replace($data, ...($opt + ['return' => 1]));
    }
}
