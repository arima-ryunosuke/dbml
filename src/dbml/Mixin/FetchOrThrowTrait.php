<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\QueryBuilder;

trait FetchOrThrowTrait
{
    /**
     * {@uses Database::fetchArray()} の例外送出版
     *
     * @inheritdoc Database::fetchArray()
     */
    private function fetchArrayOrThrowWithSql($sql, iterable $params = [])
    {
        return $this->fetchOrThrow('array', $sql, $params);
    }

    /**
     * {@uses QueryBuilder::array()} の例外送出版
     *
     * @inheritdoc QueryBuilder::array()
     */
    private function fetchArrayOrThrowWithoutSql(iterable $params = [])
    {
        return $this->fetchOrThrow('array', $params);
    }

    /**
     * {@uses Database::fetchAssoc()} の例外送出版
     *
     * @inheritdoc Database::fetchAssoc()
     */
    private function fetchAssocOrThrowWithSql($sql, iterable $params = [])
    {
        return $this->fetchOrThrow('assoc', $sql, $params);
    }

    /**
     * {@uses QueryBuilder::assoc()} の例外送出版
     *
     * @inheritdoc QueryBuilder::assoc()
     */
    private function fetchAssocOrThrowWithoutSql(iterable $params = [])
    {
        return $this->fetchOrThrow('assoc', $params);
    }

    /**
     * {@uses Database::fetchLists()} の例外送出版
     *
     * @inheritdoc Database::fetchLists()
     */
    private function fetchListsOrThrowWithSql($sql, iterable $params = [])
    {
        return $this->fetchOrThrow('lists', $sql, $params);
    }

    /**
     * {@uses QueryBuilder::lists()} の例外送出版
     *
     * @inheritdoc QueryBuilder::lists()
     */
    private function fetchListsOrThrowWithoutSql(iterable $params = [])
    {
        return $this->fetchOrThrow('lists', $params);
    }

    /**
     * {@uses Database::fetchPairs()} の例外送出版
     *
     * @inheritdoc Database::fetchPairs()
     */
    private function fetchPairsOrThrowWithSql($sql, iterable $params = [])
    {
        return $this->fetchOrThrow('pairs', $sql, $params);
    }

    /**
     * {@uses QueryBuilder::pairs()} の例外送出版
     *
     * @inheritdoc QueryBuilder::pairs()
     */
    private function fetchPairsOrThrowWithoutSql(iterable $params = [])
    {
        return $this->fetchOrThrow('pairs', $params);
    }

    /**
     * {@uses Database::fetchTuple()} の例外送出版
     *
     * @inheritdoc Database::fetchTuple()
     */
    private function fetchTupleOrThrowWithSql($sql, iterable $params = [])
    {
        return $this->fetchOrThrow('tuple', $sql, $params);
    }

    /**
     * {@uses QueryBuilder::tuple()} の例外送出版
     *
     * @inheritdoc QueryBuilder::tuple()
     */
    private function fetchTupleOrThrowWithoutSql(iterable $params = [])
    {
        return $this->fetchOrThrow('tuple', $params);
    }

    /**
     * {@uses Database::fetchValue()} の例外送出版
     *
     * @inheritdoc Database::fetchValue()
     */
    private function fetchValueOrThrowWithSql($sql, iterable $params = [])
    {
        return $this->fetchOrThrow('value', $sql, $params);
    }

    /**
     * {@uses QueryBuilder::value()} の例外送出版
     *
     * @inheritdoc QueryBuilder::value()
     */
    private function fetchValueOrThrowWithoutSql(iterable $params = [])
    {
        return $this->fetchOrThrow('value', $params);
    }
}
