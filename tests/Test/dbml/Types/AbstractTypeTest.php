<?php

namespace ryunosuke\Test\dbml\Types;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Types\AbstractType;

class AbstractTypeTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $platform = new SqlitePlatform();
        $type = new class extends AbstractType { };

        $this->assertStringStartsWith('abstracttype', $type->getName());

        $this->assertSame(123, $type->convertToDatabaseValue(123, $platform));
        $this->assertSame('hoge', $type->convertToDatabaseValue(new Expression('hoge'), $platform));

        $this->assertSame(123, $type->convertToPHPValue(123, $platform));

        that($type)->getSQLDeclaration([], $platform)->wasThrown('is not supported');
    }
}
