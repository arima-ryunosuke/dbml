<?php

namespace ryunosuke\Test\dbml\Attribute;

use ryunosuke\dbml\Attribute\VirtualColumn;

class VirtualColumnTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    #[VirtualColumn('string', false, false)]
    function test()
    {
        $attribute = VirtualColumn::of(new \ReflectionMethod(__METHOD__));
        $this->assertInstanceOf(VirtualColumn::class, $attribute->newInstance());
        $this->assertEquals(['string', false, false], $attribute->getArguments());
    }
}
