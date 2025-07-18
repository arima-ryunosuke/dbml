<?php

namespace ryunosuke\Test\dbml\Attribute;

use ryunosuke\dbml\Attribute\Scope;

class ScopeTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    #[Scope(true, false)]
    function test()
    {
        $attribute = Scope::of(new \ReflectionMethod(__METHOD__));
        $this->assertInstanceOf(Scope::class, $attribute->newInstance());
        $this->assertEquals([true, false], $attribute->getArguments());
    }
}
