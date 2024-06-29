<?php

namespace ryunosuke\dbml\Mixin;

/**
 * 拡張クラスを返せるようにする trait
 *
 * insteadof して new するとそのクラスを返す。
 */
trait FactoryTrait
{
    private static string $__static;

    public static function insteadof(?string $classname = null): void
    {
        static::$__static = $classname ?? static::class;
    }

    public static function new(...$arguments): static
    {
        return new (static::$__static ?? static::class)(...$arguments);
    }
}
