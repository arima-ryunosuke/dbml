<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;

trait AffectIgnoreTrait
{
    /**
     * IGNORE 付き {@uses Database::insertSelect()}
     *
     * @inheritdoc Database::insertSelect()
     */
    private function insertSelectIgnoreWithTable($tableName, $sql, $columns = [], iterable $params = [], ...$opt)
    {
        assert(parameter_default([$this, 'insertSelect']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertSelect($tableName, $sql, $columns, $params, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::insertSelect()}
     *
     * @inheritdoc TableGateway::insertSelect()
     */
    private function insertSelectIgnoreWithoutTable($sql, $columns = [], iterable $params = [], ...$opt)
    {
        assert(parameter_default([$this, 'insertSelect']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertSelect($sql, $columns, $params, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::insertArray()}
     *
     * @inheritdoc Database::insertArray()
     */
    private function insertArrayIgnoreWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertArray($tableName, $data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::insertArray()}
     *
     * @inheritdoc TableGateway::insertArray()
     */
    private function insertArrayIgnoreWithoutTable($data, ...$opt)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertArray($data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::updateArray()}
     *
     * @inheritdoc Database::updateArray()
     */
    private function updateArrayIgnoreWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateArray($tableName, $data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::updateArray()}
     *
     * @inheritdoc TableGateway::updateArray()
     */
    private function updateArrayIgnoreWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateArray($data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::deleteArray()}
     *
     * @inheritdoc Database::deleteArray()
     */
    private function deleteArrayIgnoreWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteArray($tableName, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::deleteArray()}
     *
     * @inheritdoc TableGateway::deleteArray()
     */
    private function deleteArrayIgnoreWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteArray($where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     */
    private function modifyArrayIgnoreWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->modifyArray($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::modifyArray()}
     *
     * @inheritdoc TableGateway::modifyArray()
     */
    private function modifyArrayIgnoreWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->modifyArray($insertData, $updateData, $uniquekey, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::changeArray()}
     *
     * @inheritdoc Database::changeArray()
     */
    private function changeArrayIgnoreWithTable($tableName, $dataarray, $where, $uniquekey = 'PRIMARY', $returning = [], ...$opt)
    {
        assert(parameter_default([$this, 'changeArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->changeArray($tableName, $dataarray, $where, $uniquekey, $returning, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::changeArray()}
     *
     * @inheritdoc TableGateway::changeArray()
     */
    private function changeArrayIgnoreWithoutTable($dataarray, $where, $uniquekey = 'PRIMARY', $returning = [], ...$opt)
    {
        assert(parameter_default([$this, 'changeArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->changeArray($dataarray, $where, $uniquekey, $returning, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::affectArray()}
     *
     * @inheritdoc Database::affectArray()
     */
    private function affectArrayIgnoreWithTable($tableName, $dataarray, ...$opt)
    {
        assert(parameter_default([$this, 'affectArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->affectArray($tableName, $dataarray, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::affectArray()}
     *
     * @inheritdoc TableGateway::affectArray()
     */
    private function affectArrayIgnoreWithoutTable($dataarray, ...$opt)
    {
        assert(parameter_default([$this, 'affectArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->affectArray($dataarray, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::save()}
     *
     * @inheritdoc Database::save()
     */
    private function saveIgnoreWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'save']) === parameter_default([$this, __FUNCTION__]));
        return $this->save($tableName, $data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::save()}
     *
     * @inheritdoc TableGateway::save()
     */
    private function saveIgnoreWithoutTable($data, ...$opt)
    {
        assert(parameter_default([$this, 'save']) === parameter_default([$this, __FUNCTION__]));
        return $this->save($data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     */
    private function insertIgnoreWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->insert($tableName, $data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     */
    private function insertIgnoreWithoutTable($data, ...$opt)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->insert($data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     */
    private function updateIgnoreWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($tableName, $data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     */
    private function updateIgnoreWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     */
    private function deleteIgnoreWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($tableName, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     */
    private function deleteIgnoreWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     */
    private function invalidIgnoreWithTable($tableName, $where, $invalid_columns = null, ...$opt)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($tableName, $where, $invalid_columns, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     */
    private function invalidIgnoreWithoutTable($where = [], $invalid_columns = null, ...$opt)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($where, $invalid_columns, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::revise()}
     *
     * @inheritdoc Database::revise()
     */
    private function reviseIgnoreWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->revise($tableName, $data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::revise()}
     *
     * @inheritdoc TableGateway::revise()
     */
    private function reviseIgnoreWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'revise']) === parameter_default([$this, __FUNCTION__]));
        return $this->revise($data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::upgrade()}
     *
     * @inheritdoc Database::upgrade()
     */
    private function upgradeIgnoreWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->upgrade($tableName, $data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::upgrade()}
     *
     * @inheritdoc TableGateway::upgrade()
     */
    private function upgradeIgnoreWithoutTable($data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'upgrade']) === parameter_default([$this, __FUNCTION__]));
        return $this->upgrade($data, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::remove()}
     *
     * @inheritdoc Database::remove()
     */
    private function removeIgnoreWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->remove($tableName, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::remove()}
     *
     * @inheritdoc TableGateway::remove()
     */
    private function removeIgnoreWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'remove']) === parameter_default([$this, __FUNCTION__]));
        return $this->remove($where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::destroy()}
     *
     * @inheritdoc Database::destroy()
     */
    private function destroyIgnoreWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->destroy($tableName, $where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::destroy()}
     *
     * @inheritdoc TableGateway::destroy()
     */
    private function destroyIgnoreWithoutTable($where = [], ...$opt)
    {
        assert(parameter_default([$this, 'destroy']) === parameter_default([$this, __FUNCTION__]));
        return $this->destroy($where, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::create()}
     *
     * @inheritdoc Database::create()
     */
    private function createIgnoreWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'create']) === parameter_default([$this, __FUNCTION__]));
        return $this->create($tableName, $data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::create()}
     *
     * @inheritdoc TableGateway::create()
     */
    private function createIgnoreWithoutTable($data, ...$opt)
    {
        assert(parameter_default([$this, 'create']) === parameter_default([$this, __FUNCTION__]));
        return $this->create($data, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     */
    private function modifyIgnoreWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['primary' => 2, 'ignore' => true]));
    }

    /**
     * IGNORE 付き {@uses TableGateway::modify()}
     *
     * @inheritdoc TableGateway::modify()
     */
    private function modifyIgnoreWithoutTable($insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($insertData, $updateData, $uniquekey, ...($opt + ['primary' => 2, 'ignore' => true]));
    }
}
