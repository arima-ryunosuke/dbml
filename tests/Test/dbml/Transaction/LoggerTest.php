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
        new Logger($fp = fopen($tmp, 'a'), [
            'lockmode' => true,
        ]);
        $this->assertTrue(flock(fopen($tmp, 'a'), LOCK_EX | LOCK_NB));
    }

    function test_simple()
    {
        $simple = Logger::simple();
        $this->assertEquals(<<<EXPECTED
select 'abcdefghijklmnopqrstuvwxyz', 2
where 'abcdefgh' and 1
EXPECTED
            , $simple(<<<ACTUAL
select ?, ?
where ? and ?
ACTUAL
                , ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true], []));

        $simple10 = Logger::simple(10);
        $this->assertEquals(<<<EXPECTED
select 'abc...wxyz', 2
where 'abcdefgh' and 1
EXPECTED
            , $simple10(<<<ACTUAL
select ?, ?
where ? and ?
ACTUAL
                , ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true], []));
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
  c  ' as white where 'abcdefgh' and 'X
Y'
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
        ]);

        $logger->log('select ?', [9]);

        rewind($resource);
        $this->assertEquals("select 9\n", stream_get_contents($resource));
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
}
