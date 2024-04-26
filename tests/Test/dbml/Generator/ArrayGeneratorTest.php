<?php

namespace ryunosuke\Test\dbml\Generator;

use ryunosuke\dbml\Generator\ArrayGenerator;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\Test\Database;

class ArrayGeneratorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_all($database)
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        $y = new Yielder($database->executeSelect('select * from test'), $database->getCompatibleConnection());
        $g = new ArrayGenerator([]);
        $g->generate($path, $y);
        $this->assertEquals([
            [
                'id'   => '1',
                'name' => 'a',
                'data' => '',
            ],
            [
                'id'   => '2',
                'name' => 'b',
                'data' => '',
            ],
            [
                'id'   => '3',
                'name' => 'c',
                'data' => '',
            ],
            [
                'id'   => '4',
                'name' => 'd',
                'data' => '',
            ],
            [
                'id'   => '5',
                'name' => 'e',
                'data' => '',
            ],
            [
                'id'   => '6',
                'name' => 'f',
                'data' => '',
            ],
            [
                'id'   => '7',
                'name' => 'g',
                'data' => '',
            ],
            [
                'id'   => '8',
                'name' => 'h',
                'data' => '',
            ],
            [
                'id'   => '9',
                'name' => 'i',
                'data' => '',
            ],
            [
                'id'   => '10',
                'name' => 'j',
                'data' => '',
            ],
        ], require $path);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_assoc($database)
    {
        $path = tempnam(sys_get_temp_dir(), 'export');

        $y = new Yielder($database->executeSelect('select * from test'), $database->getCompatibleConnection());
        $g = new ArrayGenerator([
            'assoc' => false,
        ]);
        $g->generate($path, $y);
        $rows = $database->fetchAssoc('select * from test');
        $this->assertEquals(array_values($rows), require $path);

        $y = new Yielder($database->executeSelect('select * from test'), $database->getCompatibleConnection());
        $g = new ArrayGenerator([
            'assoc' => true,
        ]);
        $g->generate($path, $y);
        $this->assertEquals($rows, require $path);
    }
}
