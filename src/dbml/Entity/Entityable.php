<?php

namespace ryunosuke\dbml\Entity;

/**
 * エンティティであることを示すインターフェース
 *
 * エンティティとして利用するには必ずこのインターフェースを実装しなければならない。
 */
interface Entityable extends \ArrayAccess
{
    /**
     * 配列からプロパティをセットする
     */
    public function assign(array $fields): static;

    /**
     * 子要素も含めて再帰的に配列化する
     */
    public function arrayize(): array;
}
