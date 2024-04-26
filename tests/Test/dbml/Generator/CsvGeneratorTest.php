<?php

namespace ryunosuke\Test\dbml\Generator;

use ryunosuke\dbml\Generator\CsvGenerator;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\Test\Database;

class CsvGeneratorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_yielder($database)
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        $y = new Yielder($database->executeSelect('select * from test'), $database->getCompatibleConnection());
        $g = new CsvGenerator(['buffered' => true]);
        $g->generate($path, $y);
        $this->assertStringEqualsFile($path, "1,a,\n2,b,\n3,c,\n4,d,\n5,e,\n6,f,\n7,g,\n8,h,\n9,i,\n10,j,\n");
    }

    function test_config()
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        $g = new CsvGenerator([
            'buffered'  => true,
            'bom'       => true,
            'encoding'  => 'SJIS-win',
            'callback'  => function ($row) { return array_map('strtoupper', $row); },
            'headers'   => ['id' => 'ID', 'name' => '名前'],
            'delimiter' => "\t",
            'enclosure' => '|',
            'escape'    => '!',
        ]);
        $length = $g->generate($path, [
            ['id' => '100', 'name' => 'thisisname'],
            ['id' => '999', 'name' => "\t!|"],
        ]);
        $actual = "\xEF\xBB\xBF" . mb_convert_encoding(<<<CSV
            ID\t名前
            100\tTHISISNAME
            999\t|\t!||
            
            CSV, 'SJIS-win');
        $this->assertEquals(strlen($actual), $length);
        $this->assertStringEqualsFile($path, $actual);
    }

    function test_autoheader()
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        $g = new CsvGenerator([
            'headers' => true,
        ]);
        $length = $g->generate($path, [
            ['id' => '100', 'name' => 'thisisname'],
            ['id' => '101', 'name' => 'thatisname'],
        ]);
        $actual = <<<CSV
            id,name
            100,thisisname
            101,thatisname
            
            CSV;
        $this->assertEquals(strlen($actual), $length);
        $this->assertStringEqualsFile($path, $actual);
    }

    function test_empty()
    {
        $path = tempnam(sys_get_temp_dir(), 'export');

        $g = new CsvGenerator([]);
        $length = $g->generate($path, []);
        $this->assertEquals(0, $length);
        $this->assertStringEqualsFile($path, "");

        $g = new CsvGenerator([
            'headers' => ['id' => 'ID', 'name' => '名前'],
        ]);
        $length = $g->generate($path, []);
        $this->assertEquals(10, $length);
        $this->assertStringEqualsFile($path, "ID,名前\n");
    }
}
