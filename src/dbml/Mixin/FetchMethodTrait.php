<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\QueryBuilder;

trait FetchMethodTrait
{
    /**
     * レコードの配列を返す
     *
     * ```php
     * $db->fetchArray('SELECT id, name FROM tablename');
     * // results:
     * [
     *     [
     *         'id'   => 1,
     *         'name' => 'name1',
     *     ],
     *     [
     *         'id'   => 2,
     *         'name' => 'name2',
     *     ],
     * ];
     * ```
     *
     * @uses Database::fetch()
     * @inheritdoc Database::fetch()
     */
    private function fetchArrayWithSql($sql, iterable $params = [])
    {
        return $this->fetch(Database::METHOD_ARRAY, $sql, $params);
    }

    /**
     * @uses QueryBuilder::fetch()
     * @inheritdoc FetchMethodTrait::fetchArrayWithSql()
     */
    private function fetchArrayWithoutSql(iterable $params = [])
    {
        return $this->fetch(Database::METHOD_ARRAY, $params);
    }

    /**
     * レコードの連想配列を返す
     *
     * ```php
     * $db->fetchAssoc('SELECT id, name FROM tablename');
     * // results:
     * [
     *     1 => [
     *         'id'   => 1,
     *         'name' => 'name1',
     *     ],
     *     2 => [
     *         'id'   => 2,
     *         'name' => 'name2',
     *     ],
     * ];
     * ```
     *
     * @uses Database::fetch()
     * @inheritdoc Database::fetch()
     */
    private function fetchAssocWithSql($sql, iterable $params = [])
    {
        return $this->fetch(Database::METHOD_ASSOC, $sql, $params);
    }

    /**
     * @uses QueryBuilder::fetch()
     * @inheritdoc FetchMethodTrait::fetchArrayWithSql()
     */
    private function fetchAssocWithoutSql(iterable $params = [])
    {
        return $this->fetch(Database::METHOD_ASSOC, $params);
    }

    /**
     * レコード[1列目]の配列を返す
     *
     * ```php
     * $db->fetchLists('SELECT name FROM tablename');
     * // results:
     * [
     *     'name1',
     *     'name2',
     * ];
     * ```
     *
     * @uses Database::fetch()
     * @inheritdoc Database::fetch()
     */
    private function fetchListsWithSql($sql, iterable $params = [])
    {
        return $this->fetch(Database::METHOD_LISTS, $sql, $params);
    }

    /**
     * @uses QueryBuilder::fetch()
     * @inheritdoc FetchMethodTrait::fetchArrayWithSql()
     */
    private function fetchListsWithoutSql(iterable $params = [])
    {
        return $this->fetch(Database::METHOD_LISTS, $params);
    }

    /**
     * レコード[1列目=>2列目]の連想配列を返す
     *
     * ```php
     * $db->fetchPairs('SELECT id, name FROM tablename');
     * // results:
     * [
     *     1 => 'name1',
     *     2 => 'name2',
     * ];
     * ```
     *
     * @uses Database::fetch()
     * @inheritdoc Database::fetch()
     */
    private function fetchPairsWithSql($sql, iterable $params = [])
    {
        return $this->fetch(Database::METHOD_PAIRS, $sql, $params);
    }

    /**
     * @uses QueryBuilder::fetch()
     * @inheritdoc FetchMethodTrait::fetchArrayWithSql()
     */
    private function fetchPairsWithoutSql(iterable $params = [])
    {
        return $this->fetch(Database::METHOD_PAIRS, $params);
    }

    /**
     * レコードを返す
     *
     * このメソッドはフェッチ結果が2件以上だと**例外を投げる**。
     * これは
     *
     * - 1行を期待しているのに WHERE や LIMIT がなく、無駄なクエリになっている
     * - {@link whereInto()} の仕様により意図せず配列を与えて WHERE IN になっている
     *
     * のを予防的に阻止するため必要な仕様である。
     *
     * ```php
     * $db->fetchTuple('SELECT id, name FROM tablename LIMIT 1');
     * // results:
     * [
     *     'id'   => 1,
     *     'name' => 'name1',
     * ];
     * ```
     *
     * @uses Database::fetch()
     * @inheritdoc Database::fetch()
     */
    private function fetchTupleWithSql($sql, iterable $params = [])
    {
        return $this->fetch(Database::METHOD_TUPLE, $sql, $params);
    }

    /**
     * @uses QueryBuilder::fetch()
     * @inheritdoc FetchMethodTrait::fetchArrayWithSql()
     */
    private function fetchTupleWithoutSql(iterable $params = [])
    {
        return $this->fetch(Database::METHOD_TUPLE, $params);
    }

    /**
     * レコード[1列目]を返す
     *
     * このメソッドはフェッチ結果が2件以上だと**例外を投げる**。
     * これは
     *
     * - 1行を期待しているのに WHERE や LIMIT がなく、無駄なクエリになっている
     * - {@link whereInto()} の仕様により意図せず配列を与えて WHERE IN になっている
     *
     * のを予防的に阻止するために必要な仕様である。
     *
     * ```php
     * $db->fetchValue('SELECT name FROM tablename LIMIT 1');
     * // results:
     * 'name1';
     * ```
     *
     * @uses Database::fetch()
     * @inheritdoc Database::fetch()
     */
    private function fetchValueWithSql($sql, iterable $params = [])
    {
        return $this->fetch(Database::METHOD_VALUE, $sql, $params);
    }

    /**
     * @uses QueryBuilder::fetch()
     * @inheritdoc FetchMethodTrait::fetchArrayWithSql()
     */
    private function fetchValueWithoutSql(iterable $params = [])
    {
        return $this->fetch(Database::METHOD_VALUE, $params);
    }
}
