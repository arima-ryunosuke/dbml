<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;

trait AffectAndPrimaryTrait
{
    /**
     * 主キーを返す {@uses Database::insertArray()}
     *
     * @inheritdoc Database::insertArray()
     * @return array|string
     */
    private function insertArrayAndPrimaryWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertArray($tableName, $data, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::insertArray()}
     *
     * @inheritdoc TableGateway::insertArray()
     * @return array|string
     */
    #[AssumeType('primaries')]
    private function insertArrayAndPrimaryWithoutTable(
        #[AssumeType('entities', 'shapes')] $data,
        ...$opt
    ) {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertArray($data, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::modifyArray()}
     *
     * @inheritdoc Database::modifyArray()
     * @return array|string
     */
    private function modifyArrayAndPrimaryWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->modifyArray($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::modifyArray()}
     *
     * @inheritdoc TableGateway::modifyArray()
     * @return array|string
     */
    #[AssumeType('primaries')]
    private function modifyArrayAndPrimaryWithoutTable(
        #[AssumeType('entities', 'shapes')] $insertData,
        #[AssumeType('entity', 'shape')] $updateData = [],
        $uniquekey = 'PRIMARY',
        ...$opt
    ) {
        assert(parameter_default([$this, 'modifyArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->modifyArray($insertData, $updateData, $uniquekey, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     * @return array|string
     */
    private function insertAndPrimaryWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->insert($tableName, $data, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function insertAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        ...$opt
    ) {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->insert($data, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     * @return array|string
     */
    private function updateAndPrimaryWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($tableName, $data, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function updateAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($data, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     * @return array|string
     */
    private function deleteAndPrimaryWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($tableName, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function deleteAndPrimaryWithoutTable(
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     * @return array|string
     */
    private function invalidAndPrimaryWithTable($tableName, $where, $invalid_columns = null, ...$opt)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($tableName, $where, $invalid_columns, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function invalidAndPrimaryWithoutTable(
        #[AssumeType('shape')] $where = [],
        #[AssumeType('shape')] ?array $invalid_columns = null,
        ...$opt
    ) {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($where, $invalid_columns, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::updateExcludeRestrict()}
     *
     * @inheritdoc Database::updateExcludeRestrict()
     * @return array|string
     */
    private function updateExcludeRestrictAndPrimaryWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateExcludeRestrict($tableName, $data, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::updateExcludeRestrict()}
     *
     * @inheritdoc TableGateway::updateExcludeRestrict()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function updateExcludeRestrictAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'updateExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateExcludeRestrict($data, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::updateIncludeRestrict()}
     *
     * @inheritdoc Database::updateIncludeRestrict()
     * @return array|string
     */
    private function updateIncludeRestrictAndPrimaryWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateIncludeRestrict($tableName, $data, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::updateIncludeRestrict()}
     *
     * @inheritdoc TableGateway::updateIncludeRestrict()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function updateIncludeRestrictAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'updateIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateIncludeRestrict($data, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::deleteExcludeRestrict()}
     *
     * @inheritdoc Database::deleteExcludeRestrict()
     * @return array|string
     */
    private function deleteExcludeRestrictAndPrimaryWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteExcludeRestrict($tableName, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::deleteExcludeRestrict()}
     *
     * @inheritdoc TableGateway::deleteExcludeRestrict()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function deleteExcludeRestrictAndPrimaryWithoutTable(
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'deleteExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteExcludeRestrict($where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::deleteIncludeRestrict()}
     *
     * @inheritdoc Database::deleteIncludeRestrict()
     * @return array|string
     */
    private function deleteIncludeRestrictAndPrimaryWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteIncludeRestrict($tableName, $where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::deleteIncludeRestrict()}
     *
     * @inheritdoc TableGateway::deleteIncludeRestrict()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function deleteIncludeRestrictAndPrimaryWithoutTable(
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'deleteIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteIncludeRestrict($where, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::upsert()}
     *
     * @inheritdoc Database::upsert()
     * @return array|string
     */
    private function upsertAndPrimaryWithTable($tableName, $insertData, $updateData = [], ...$opt)
    {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->upsert($tableName, $insertData, $updateData, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::upsert()}
     *
     * @inheritdoc TableGateway::upsert()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function upsertAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $insertData,
        #[AssumeType('entity', 'shape')] $updateData = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'upsert']) === parameter_default([$this, __FUNCTION__]));
        return $this->upsert($insertData, $updateData, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    private function modifyAndPrimaryWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function modifyAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $insertData,
        #[AssumeType('entity', 'shape')] $updateData = [],
        $uniquekey = 'PRIMARY',
        ...$opt
    ) {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($insertData, $updateData, $uniquekey, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     * @return array|string
     */
    private function replaceAndPrimaryWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->replace($tableName, $data, ...($opt + ['return' => 'primary']));
    }

    /**
     * 主キーを返す {@uses TableGateway::replace()}
     *
     * @inheritdoc TableGateway::replace()
     * @return array|string
     */
    #[AssumeType('primary')]
    private function replaceAndPrimaryWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        ...$opt
    ) {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->replace($data, ...($opt + ['return' => 'primary']));
    }
}
