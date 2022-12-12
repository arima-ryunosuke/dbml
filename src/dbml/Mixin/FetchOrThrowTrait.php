<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait FetchOrThrowTrait
{
    /**
     * {@uses Database::fetchArray()} の例外送出版
     *
     * @inheritdoc Database::fetchArray()
     */
    public function fetchArrayOrThrow(...$args)
    {
        return $this->fetchOrThrow('array', $args);
    }

    /**
     * {@uses Database::fetchAssoc()} の例外送出版
     *
     * @inheritdoc Database::fetchAssoc()
     */
    public function fetchAssocOrThrow(...$args)
    {
        return $this->fetchOrThrow('assoc', $args);
    }

    /**
     * {@uses Database::fetchLists()} の例外送出版
     *
     * @inheritdoc Database::fetchLists()
     */
    public function fetchListsOrThrow(...$args)
    {
        return $this->fetchOrThrow('lists', $args);
    }

    /**
     * {@uses Database::fetchPairs()} の例外送出版
     *
     * @inheritdoc Database::fetchPairs()
     */
    public function fetchPairsOrThrow(...$args)
    {
        return $this->fetchOrThrow('pairs', $args);
    }

    /**
     * {@uses Database::fetchTuple()} の例外送出版
     *
     * @inheritdoc Database::fetchTuple()
     */
    public function fetchTupleOrThrow(...$args)
    {
        return $this->fetchOrThrow('tuple', $args);
    }

    /**
     * {@uses Database::fetchValue()} の例外送出版
     *
     * @inheritdoc Database::fetchValue()
     */
    public function fetchValueOrThrow(...$args)
    {
        return $this->fetchOrThrow('value', $args);
    }
}
