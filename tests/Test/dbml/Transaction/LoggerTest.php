<?php /** @noinspection PhpDeprecationInspection */

namespace ryunosuke\Test\dbml\Transaction;

use ryunosuke\dbml\Transaction\Logger;

class LoggerTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_metadata_default()
    {
        $logs = [];
        $database = self::getDummyDatabase();
        $database->setLogger(new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'level'       => 'debug',
        ]));
        $database->fetchArray('select ?', [1]);

        $this->assertStringContainsString("-- time: 20", $logs[0]);
        $this->assertStringContainsString("-- elapsed: 0.", $logs[0]);
        $this->assertStringContainsString("-- traces[]", $logs[0]);
        $this->assertStringContainsString(__FILE__, $logs[0]);
    }

    function test_metadata_custom()
    {
        $logs = [];
        $database = self::getDummyDatabase();
        $database->setLogger(new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'level'       => 'debug',
            'metadata'    => [
                'hoge' => function () { return 123; },
                'fuga' => function () { return [1, 2, 3]; },
            ],
        ]));
        $database->fetchArray('select ?', [1]);

        $this->assertEquals("-- hoge: 123
-- fuga[]: 1
-- fuga[]: 2
-- fuga[]: 3
select 1", $logs[0]);
    }
}
