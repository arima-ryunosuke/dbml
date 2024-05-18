<?php

namespace ryunosuke\dbml\Query;

/**
 * クエリ文字列（完全性は問わない。部分クエリでも良い）とパラメータを持つインターフェース
 */
interface Queryable
{
    /**
     * クエリ文字列を返す
     */
    public function getQuery(): string;

    /**
     * パラメータを返す
     */
    public function getParams(): array;

    /**
     * パラメータをマージして文字列表現を返す
     *
     * クエリ文字列を返し、引数配列にパラメータが追加される
     */
    public function merge(?array &$params): string;
}
