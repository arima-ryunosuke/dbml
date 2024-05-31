<?php

namespace ryunosuke\Test\dbml\Generator;

use ryunosuke\dbml\Generator\AbstractGenerator;

class AbstractGeneratorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_path()
    {
        $g = new ConcreteGenerator();
        that($g)->generate('never/notfound/dir/:::.txt', [])->wasThrown(new \RuntimeException('failed to open'));
    }

    function test_stdout()
    {
        $g = new ConcreteGenerator();
        $this->expectOutputRegex('#generateHead
generateBody
generateBody
generateTail
#');
        $length = $g->generate(null, [
            ['id' => 1, 'name' => 'name1'],
            ['id' => 2, 'name' => 'name2'],
        ]);
        $this->assertEquals(52, $length);
    }
}

class ConcreteGenerator extends AbstractGenerator
{
    protected function initProvider(iterable $provider)
    {
    }

    protected function generateHead($resource)
    {
        return fwrite($resource, __FUNCTION__ . "\n");
    }

    protected function generateBody($resource, $key, $value, $first_flg)
    {
        return fwrite($resource, __FUNCTION__ . "\n");
    }

    protected function generateTail($resource)
    {
        return fwrite($resource, __FUNCTION__ . "\n");
    }
}
