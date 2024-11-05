<?php

namespace ryunosuke\Test\dbml\Types;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use ryunosuke\dbml\Types\EnumType;
use ryunosuke\Test\IntEnum;

class EnumTypeTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $platform = new SqlitePlatform();
        $types = EnumType::register($platform, [
            'hogetype' => IntEnum::class,
        ]);
        $this->assertSame($types, EnumType::register($platform, [
            'hogetype' => IntEnum::class,
        ], false));

        $this->assertEquals('hogetype', $types['hogetype']->getName());
        $this->assertEquals(IntEnum::class, $types['hogetype']->getEnum());

        $this->assertSame(1, $types['hogetype']->convertToDatabaseValue(1, $platform));
        $this->assertSame(1, $types['hogetype']->convertToDatabaseValue(IntEnum::Int1(), $platform));
        $this->assertSame(null, $types['hogetype']->convertToDatabaseValue(null, $platform));

        $this->assertSame(IntEnum::Int1(), $types['hogetype']->convertToPHPValue(1, $platform));
        $this->assertSame(IntEnum::Int1(), $types['hogetype']->convertToPHPValue(IntEnum::Int1(), $platform));
        $this->assertSame(null, $types['hogetype']->convertToPHPValue(null, $platform));

        that($types['hogetype'])->convertToDatabaseValue(999, $platform)->wasThrown('is not a valid backing value');
        that($types['hogetype'])->convertToPHPValue(999, $platform)->wasThrown('is not a valid backing value');

        that(EnumType::class)->register($platform, ['hogetype' => IntEnum::class])->wasThrown('already exists');
    }
}
