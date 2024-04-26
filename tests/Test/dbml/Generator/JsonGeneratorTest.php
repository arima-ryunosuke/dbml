<?php

namespace ryunosuke\Test\dbml\Generator;

use ryunosuke\dbml\Generator\JsonGenerator;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\Test\Database;

class JsonGeneratorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_yielder($database)
    {
        $path = tempnam(sys_get_temp_dir(), 'export');
        $id = $database->getCompatiblePlatform()->getConcatExpression("''", 'id');

        $y = new Yielder($database->executeSelect("select ($id) as id, test.name from test"), $database->getCompatibleConnection());
        $g = new JsonGenerator(['buffered' => true, 'assoc' => false]);
        $g->generate($path, $y);
        $this->assertJson(file_get_contents($path));
        $this->assertStringEqualsFile($path, '[{"id":"1","name":"a"},{"id":"2","name":"b"},{"id":"3","name":"c"},{"id":"4","name":"d"},{"id":"5","name":"e"},{"id":"6","name":"f"},{"id":"7","name":"g"},{"id":"8","name":"h"},{"id":"9","name":"i"},{"id":"10","name":"j"}]');

        $y = new Yielder($database->executeSelect("select ($id) as id, test.name from test"), $database->getCompatibleConnection());
        $g = new JsonGenerator(['buffered' => true, 'assoc' => true]);
        $g->generate($path, $y);
        $this->assertJson(file_get_contents($path));
        $this->assertStringEqualsFile($path, '{"1":{"id":"1","name":"a"},"2":{"id":"2","name":"b"},"3":{"id":"3","name":"c"},"4":{"id":"4","name":"d"},"5":{"id":"5","name":"e"},"6":{"id":"6","name":"f"},"7":{"id":"7","name":"g"},"8":{"id":"8","name":"h"},"9":{"id":"9","name":"i"},"10":{"id":"10","name":"j"}}');
    }

    function test_assoc()
    {
        $path = tempnam(sys_get_temp_dir(), 'export');

        $g = new JsonGenerator([
            'assoc' => false,
        ]);
        $length = $g->generate($path, [
            100 => ['id' => '100', 'name' => 'thisisname'],
            999 => ['id' => '999', 'name' => "\t!|"],
        ]);
        $actual = '[{"id":"100","name":"thisisname"},{"id":"999","name":"\t!|"}]';
        $this->assertEquals(strlen($actual), $length);
        $this->assertJson(file_get_contents($path));
        $this->assertStringEqualsFile($path, $actual);

        $g = new JsonGenerator([
            'assoc' => true,
        ]);
        $length = $g->generate($path, [
            100 => ['id' => '100', 'name' => 'thisisname'],
            999 => ['id' => '999', 'name' => "\t!|"],
        ]);
        $actual = '{"100":{"id":"100","name":"thisisname"},"999":{"id":"999","name":"\t!|"}}';
        $this->assertEquals(strlen($actual), $length);
        $this->assertJson(file_get_contents($path));
        $this->assertStringEqualsFile($path, $actual);
    }

    function test_pretty()
    {
        $path = tempnam(sys_get_temp_dir(), 'export');

        $g = new JsonGenerator([
            'option' => JSON_PRETTY_PRINT,
            'assoc'  => true,
        ]);
        $length = $g->generate($path, [
            100 => ['id' => '100', 'name' => 'thisisname'],
            999 => ['id' => '999', 'name' => "\t!|"],
        ]);
        $actual = '{
    "100": {
        "id": "100",
        "name": "thisisname"
    },
    "999": {
        "id": "999",
        "name": "\t!|"
    }
}';
        $this->assertJson(file_get_contents($path));
        $this->assertEquals(strlen($actual), $length);
        $this->assertStringEqualsFile($path, $actual);
    }

    function test_empty()
    {
        $path = tempnam(sys_get_temp_dir(), 'export');

        $g = new JsonGenerator(['assoc' => false]);
        $length = $g->generate($path, []);
        $this->assertEquals(2, $length);
        $this->assertStringEqualsFile($path, "[]");

        $g = new JsonGenerator(['assoc' => true]);
        $length = $g->generate($path, []);
        $this->assertEquals(2, $length);
        $this->assertStringEqualsFile($path, "{}");
    }
}
