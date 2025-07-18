<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use function ryunosuke\dbml\parameter_default;

trait AffectOrThrowTrait
{
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
    private function insertArrayOrThrowWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertArray($tableName, $data, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::insertArray()}
     *
     * @inheritdoc TableGateway::insertArray()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primaries')]
    private function insertArrayOrThrowWithoutTable(
        #[AssumeType('entities', 'shapes')] $data,
        ...$opt
    ) {
        assert(parameter_default([$this, 'insertArray']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertArray($data, ...($opt + ['throw' => true, 'return' => 'primary']));
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
    private function createWithTable($tableName, $data, ...$opt)
    {
        return $this->insertOrThrowWithTable($tableName, $data, ...$opt);
    }

    /**
     * insertOrThrow のエイリアス
     *
     * @inheritdoc TableGateway::insert()
     * @see createWithTable()
     */
    #[AssumeType('primary')]
    private function createWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        ...$opt
    ) {
        return $this->insertOrThrowWithoutTable($data, ...$opt);
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::insert()}
     *
     * @inheritdoc Database::insert()
     * @return array|string
     * @throws NonAffectedException
     */
    private function insertOrThrowWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->insert($tableName, $data, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::insert()}
     *
     * @inheritdoc TableGateway::insert()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function insertOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        ...$opt
    ) {
        assert(parameter_default([$this, 'insert']) === parameter_default([$this, __FUNCTION__]));
        return $this->insert($data, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::update()}
     *
     * @inheritdoc Database::update()
     * @return array|string
     * @throws NonAffectedException
     */
    private function updateOrThrowWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($tableName, $data, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::update()}
     *
     * @inheritdoc TableGateway::update()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function updateOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'update']) === parameter_default([$this, __FUNCTION__]));
        return $this->update($data, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::delete()}
     *
     * @inheritdoc Database::delete()
     * @return array|string
     * @throws NonAffectedException
     */
    private function deleteOrThrowWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($tableName, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::delete()}
     *
     * @inheritdoc TableGateway::delete()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function deleteOrThrowWithoutTable(
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'delete']) === parameter_default([$this, __FUNCTION__]));
        return $this->delete($where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::invalid()}
     *
     * @inheritdoc Database::invalid()
     * @return array|string
     * @throws NonAffectedException
     */
    private function invalidOrThrowWithTable($tableName, $where, $invalid_columns = null, ...$opt)
    {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($tableName, $where, $invalid_columns, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::invalid()}
     *
     * @inheritdoc TableGateway::invalid()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function invalidOrThrowWithoutTable(
        #[AssumeType('shape')] $where = [],
        #[AssumeType('shape')] ?array $invalid_columns = null,
        ...$opt
    ) {
        assert(parameter_default([$this, 'invalid']) === parameter_default([$this, __FUNCTION__]));
        return $this->invalid($where, $invalid_columns, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::updateExcludeRestrict()}
     *
     * @inheritdoc Database::updateExcludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    private function updateExcludeRestrictOrThrowWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateExcludeRestrict($tableName, $data, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::updateExcludeRestrict()}
     *
     * @inheritdoc TableGateway::updateExcludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function updateExcludeRestrictOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'updateExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateExcludeRestrict($data, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::updateIncludeRestrict()}
     *
     * @inheritdoc Database::updateIncludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    private function updateIncludeRestrictOrThrowWithTable($tableName, $data, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'updateIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateIncludeRestrict($tableName, $data, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::updateIncludeRestrict()}
     *
     * @inheritdoc TableGateway::updateIncludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function updateIncludeRestrictOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'updateIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->updateIncludeRestrict($data, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::deleteExcludeRestrict()}
     *
     * @inheritdoc Database::deleteExcludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    private function deleteExcludeRestrictOrThrowWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteExcludeRestrict($tableName, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::deleteExcludeRestrict()}
     *
     * @inheritdoc TableGateway::deleteExcludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function deleteExcludeRestrictOrThrowWithoutTable(
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'deleteExcludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteExcludeRestrict($where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::deleteIncludeRestrict()}
     *
     * @inheritdoc Database::deleteIncludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    private function deleteIncludeRestrictOrThrowWithTable($tableName, $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'deleteIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteIncludeRestrict($tableName, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::deleteIncludeRestrict()}
     *
     * @inheritdoc TableGateway::deleteIncludeRestrict()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function deleteIncludeRestrictOrThrowWithoutTable(
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'deleteIncludeRestrict']) === parameter_default([$this, __FUNCTION__]));
        return $this->deleteIncludeRestrict($where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::reduce()}
     *
     * @inheritdoc Database::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    private function reduceOrThrowWithTable($tableName, $limit = null, $orderBy = [], $groupBy = [], $where = [], ...$opt)
    {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->reduce($tableName, $limit, $orderBy, $groupBy, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::reduce()}
     *
     * @inheritdoc TableGateway::reduce()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function reduceOrThrowWithoutTable(
        $limit = null,
        $orderBy = [],
        $groupBy = [],
        #[AssumeType('shape')] $where = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'reduce']) === parameter_default([$this, __FUNCTION__]));
        return $this->reduce($limit, $orderBy, $groupBy, $where, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::insertOrUpdate()}
     *
     * @inheritdoc Database::insertOrUpdate()
     * @return array|string
     * @throws NonAffectedException
     */
    private function insertOrUpdateOrThrowWithTable($tableName, $insertData, $updateData = [], ...$opt)
    {
        assert(parameter_default([$this, 'insertOrUpdate']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertOrUpdate($tableName, $insertData, $updateData, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::insertOrUpdate()}
     *
     * @inheritdoc TableGateway::insertOrUpdate()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function insertOrUpdateOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $insertData,
        #[AssumeType('entity', 'shape')] $updateData = [],
        ...$opt
    ) {
        assert(parameter_default([$this, 'insertOrUpdate']) === parameter_default([$this, __FUNCTION__]));
        return $this->insertOrUpdate($insertData, $updateData, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::modify()}
     *
     * @inheritdoc Database::modify()
     * @return array|string
     * @throws NonAffectedException
     */
    private function modifyOrThrowWithTable($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', ...$opt)
    {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($tableName, $insertData, $updateData, $uniquekey, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::modify()}
     *
     * @inheritdoc TableGateway::modify()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function modifyOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $insertData,
        #[AssumeType('entity', 'shape')] $updateData = [],
        $uniquekey = 'PRIMARY',
        ...$opt
    ) {
        assert(parameter_default([$this, 'modify']) === parameter_default([$this, __FUNCTION__]));
        return $this->modify($insertData, $updateData, $uniquekey, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses Database::replace()}
     *
     * @inheritdoc Database::replace()
     * @return array|string
     * @throws NonAffectedException
     */
    private function replaceOrThrowWithTable($tableName, $data, ...$opt)
    {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->replace($tableName, $data, ...($opt + ['throw' => true, 'return' => 'primary']));
    }

    /**
     * 作用行が 0 のときに例外を投げる {@uses TableGateway::replace()}
     *
     * @inheritdoc TableGateway::replace()
     * @return array|string
     * @throws NonAffectedException
     */
    #[AssumeType('primary')]
    private function replaceOrThrowWithoutTable(
        #[AssumeType('entity', 'shape')] $data,
        ...$opt
    ) {
        assert(parameter_default([$this, 'replace']) === parameter_default([$this, __FUNCTION__]));
        return $this->replace($data, ...($opt + ['throw' => true, 'return' => 'primary']));
    }
}
