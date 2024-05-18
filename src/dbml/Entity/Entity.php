<?php

namespace ryunosuke\dbml\Entity;

use JsonSerializable;
use function ryunosuke\dbml\arrayval;

/**
 * 組み込みのデフォルトエンティティクラス
 */
class Entity implements Entityable, \IteratorAggregate, JsonSerializable
{
    private array $fields = [];

    public function __call(string $name, array $arguments): mixed
    {
        return ($this->offsetGet($name))(...$arguments);
    }

    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }

    public function __unset(string $name): void
    {
        $this->offsetUnset($name);
    }

    public function __get(string $name): mixed
    {
        return $this->offsetGet($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->offsetSet($name, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->fields);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->fields[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->fields[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
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

    public function assign(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function arrayize(): array
    {
        return arrayval($this->fields);
    }
}
