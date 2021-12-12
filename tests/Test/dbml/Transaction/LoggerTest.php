<?php

namespace ryunosuke\Test\dbml\Transaction;

use ryunosuke\dbml\Transaction\Logger;

class LoggerTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test___construct()
    {
        $logger = new Logger(STDOUT, [
            'callback' => $callback = function () { },
        ]);
        // destination だけは第1引数でダイレクトに設定できる
        $this->assertSame(STDOUT, $logger->getOption('destination'));
        // その場合オプション配列は第2引数になる
        $this->assertSame($callback, $logger->getOption('callback'));
    }

    function test___destruct()
    {
        // for coverage. nothing todo
        new Logger(null);

        // ロックの解放を担保
        $tmp = sys_get_temp_dir() . '/log.txt';
        new Logger(fopen($tmp, 'a'), [
            'lockmode' => true,
        ]);
        $this->assertTrue(flock(fopen($tmp, 'a'), LOCK_EX | LOCK_NB));
    }

    function test_simple()
    {
        $simple = Logger::simple();
        $this->assertEquals(<<<EXPECTED
select 'abcdefghijklmnopqrstuvwxyz', 2
where 'abcdefgh' and 1 and 'binary(6162635C3078797A)'
EXPECTED
            , $simple(<<<ACTUAL
select ?, ?
where ? and ? and ?
ACTUAL
                , ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true, 'abc\0xyz'], []));

        $simple10 = Logger::simple(10);
        $this->assertEquals(<<<EXPECTED
select 'abc...wxyz', 2
where 'abcdefgh' and 1 and 'binary(616...797A)'
EXPECTED
            , $simple10(<<<ACTUAL
select ?, ?
where ? and ? and ?
ACTUAL
                , ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true, 'abc\0xyz'], []));
    }

    function test_pretty()
    {
        $pretty = Logger::pretty(10);
        $this->assertEquals(<<<EXPECTED
select
  'abc...wxyz',
  2 
where
  'abcdefgh' 
  and 1
EXPECTED
            , $pretty(<<<ACTUAL
select ?, ?
where ? and ?
ACTUAL
                , ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true], []));
    }

    function test_oneline()
    {
        $oneline = Logger::oneline(10);
        $this->assertEquals(<<<EXPECTED
select 'abc...wxyz', 2, '  a  
  b  
  c  ' as white where 'abcdefgh' and 'X\\nY'
EXPECTED
            , $oneline(<<<ACTUAL
select ?, ?, '  a  
  b  
  c  ' as white
where ? and ?
ACTUAL
                , ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', "X\nY"], []));
    }

    function test_callback()
    {
        $logs = [];
        $logger = new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'callback'    => function ($sql, $params) {
                return "[prefix] $sql:" . json_encode($params);
            },
        ]);

        $logger->log('select ?, ?', [1, 'x']);
        $logger->log('"COMMIT"', []);

        $this->assertEquals([
            '[prefix] select ?, ?:[1,"x"]',
            '[prefix] COMMIT:[]',
        ], $logs);
    }

    function test_file()
    {
        $logs = sys_get_temp_dir() . '/query.log';
        @unlink($logs);
        $logger = new Logger([
            'destination' => $logs,
            'buffer'      => false,
        ]);

        $logger->log('select ?', [9]);
        $logger->log('select ?', [9]);

        $this->assertStringEqualsFile($logs, "select 9\nselect 9\n");
    }

    function test_resource()
    {
        $resource = fopen(sys_get_temp_dir() . '/query.log', 'w+');
        $logger = new Logger([
            'destination' => $resource,
            'buffer'      => false,
        ]);

        $logger->log('select ?', [9]);

        rewind($resource);
        $this->assertEquals("select 9\n", stream_get_contents($resource));
    }

    function test_middle_resource()
    {
        $resource = fopen(sys_get_temp_dir() . '/query.log', 'w+');
        $logger = new Logger([
            'destination' => $resource,
            'buffer'      => [100, 100],
        ]);

        $logger->log('select 1, ?', [str_repeat('x', 30)]);
        $logger->log('select 2, ?', [str_repeat('x', 30)]);
        $logger->log('select 3, ?', [str_repeat('x', 30)]);
        $logger->log('select 4, ?', [str_repeat('x', 30)]);

        unset($logger);

        rewind($resource);
        $this->assertEquals("select 1, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
select 2, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
select 3, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
select 4, 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
", stream_get_contents($resource));
    }

    function test_closure()
    {
        $logs = [];
        $logger = new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'buffer'      => 1024 * 10,
        ]);

        $logger->log('select ?', [9]);

        $this->assertEquals(['select 9'], $logs);

        $logs = [];
        $logger = new Logger([
            'destination' => function ($sql, $params) use (&$logs) { $logs[] = compact('sql', 'params'); },
            'buffer'      => 1024 * 10,
        ]);

        $logger->log('select ?', [9]);

        $this->assertEquals([['sql' => 'select ?', 'params' => [9]]], $logs);
    }

    function test_buffer_resource()
    {
        $resource = fopen(sys_get_temp_dir() . '/query1.log', 'w+');
        $logger = new Logger([
            'destination' => $resource,
            'buffer'      => 1024 * 10,
            'lockmode'    => true,
        ]);

        $logger->log('select ?', [9]);
        rewind($resource);
        $this->assertEmpty(stream_get_contents($resource));

        unset($logger);

        rewind($resource);
        $this->assertEquals("select 9\n", stream_get_contents($resource));
    }

    function test_pallarel()
    {
        $logfile = sys_get_temp_dir() . '/query2.log';
        @unlink($logfile);
        $logger1 = new Logger($logfile, ['buffer' => true]);
        $logger2 = new Logger($logfile, ['buffer' => true]);

        $logger1->log('select ?', [11]);
        $logger2->log('select ?', [21]);
        $logger1->log('select ?', [12]);
        $logger2->log('select ?', [22]);
        unset($logger1, $logger2);

        $this->assertEquals([
            "select 11",
            "select 12",
            "select 21",
            "select 22",
        ], file($logfile, FILE_IGNORE_NEW_LINES));
    }

    function test_metadata_default()
    {
        $logs = [];
        $database = self::getDummyDatabase();
        $database->setLogger(new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
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
