<?php

namespace ryunosuke\dbml\Types;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use function ryunosuke\dbml\class_shorten;

abstract class AbstractType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        throw DBALException::notSupported("dbml Type is not supported DDL");
    }

    public function getName()
    {
        return array_search($this, Type::getTypeRegistry()->getMap(), true) ?: strtolower(class_shorten(static::class));
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * アノテーション出力のため、返り値の型は指定するのが望ましい。
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }
}
