<?php

namespace ryunosuke\dbml\Entity;

use JsonSerializable;
use function ryunosuke\dbml\arrayval;

/**
 * 組み込みのデフォルトエンティティクラス
 */
class Entity implements Entityable, \IteratorAggregate, JsonSerializable
{
    private $fields = [];

    public function __call($name, $arguments)
    {
        return ($this->offsetGet($name))(...$arguments);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        return $this->offsetUnset($name);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        return $this->offsetSet($name, $value);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->fields);
    }

    public function offsetUnset($offset): void
    {
        unset($this->fields[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->fields[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->fields[$offset] = $value;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->fields;
    }

    public function jsonSerialize(): array
    {
        return $this->fields;
    }

    public function assign(array $fields): Entityable
    {
        $this->fields = $fields;
        return $this;
    }

    public function arrayize(): array
    {
        return arrayval($this->fields);
    }
}
