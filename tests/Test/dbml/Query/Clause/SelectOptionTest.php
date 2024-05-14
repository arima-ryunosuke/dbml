<?php

namespace ryunosuke\Test\dbml\Query\Clause;

use ryunosuke\dbml\Query\Clause\SelectOption;

class SelectOptionTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test___callStatic()
    {
        $actual = SelectOption::DISTINCT();
        $this->assertEquals(new SelectOption('DISTINCT'), $actual);
    }

    function test()
    {
        $so = new SelectOption('hogera');
        $this->assertEquals('hogera', $so);
    }
}
