<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use function ryunosuke\dbml\try_finally;

trait PrepareTrait
{
    /**
     * クエリビルダ構文で SELECT 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::select()} も参照）
     *
     * @inheritdoc Database::select()
     */
    public function prepareSelect(...$args)
    {
        return $this->getDatabase()->prepare($this->select(...$args));
    }

    /**
     * クエリビルダ構文で INSERT 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::insert()} も参照）
     *
     * @inheritdoc Database::insert()
     */
    public function prepareInsert(...$args)
    {
        return try_finally([$this, 'insert'], $this->getDatabase()->storeOptions(['preparing' => true]), ...$args);
    }

    /**
     * クエリビルダ構文で UPDATE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::update()} も参照）
     *
     * @inheritdoc Database::update()
     */
    public function prepareUpdate(...$args)
    {
        return try_finally([$this, 'update'], $this->getDatabase()->storeOptions(['preparing' => true]), ...$args);
    }

    /**
     * クエリビルダ構文で DELETE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::delete()} も参照）
     *
     * @inheritdoc Database::delete()
     */
    public function prepareDelete(...$args)
    {
        return try_finally([$this, 'delete'], $this->getDatabase()->storeOptions(['preparing' => true]), ...$args);
    }

    /**
     * クエリビルダ構文で MODEFIY 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::modify()} も参照）
     *
     * @inheritdoc Database::modify()
     */
    public function prepareModify(...$args)
    {
        return try_finally([$this, 'modify'], $this->getDatabase()->storeOptions(['preparing' => true]), ...$args);
    }

    /**
     * クエリビルダ構文で REPLACE 用プリペアドステートメント取得する（{@uses Database::prepare()}, {@uses Database::replace()} も参照）
     *
     * @inheritdoc Database::replace()
     */
    public function prepareReplace(...$args)
    {
        return try_finally([$this, 'replace'], $this->getDatabase()->storeOptions(['preparing' => true]), ...$args);
    }
}
