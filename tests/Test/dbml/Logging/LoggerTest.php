<?php

namespace ryunosuke\Test\dbml\Logging;

use ryunosuke\dbml\Logging\Logger;

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

    function test___debugInfo()
    {
        $debugString = print_r(new Logger(null), true);
        $this->assertStringContainsString('handle:', $debugString);
        $this->assertStringNotContainsString('arrayBuffer:', $debugString);
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
            EXPECTED,
            $simple(<<<ACTUAL
            select ?, ?
            where ? and ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true, 'abc\0xyz'], [], []));

        $simple10 = Logger::simple(10);
        $this->assertEquals(<<<EXPECTED
            select 'abc...wxyz', 2
            where 'abcdefgh' and 1 and 'binary(616...797A)'
            EXPECTED,
            $simple10(<<<ACTUAL
            select ?, ?
            where ? and ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true, 'abc\0xyz'], [], []));

        $simplemeta = Logger::simple(10);
        $this->assertEquals(<<<EXPECTED
            -- hoge: HOGE
            select 'abc...wxyz', 2
            where 'abcdefgh' and 1 and 'binary(616...797A)'
            EXPECTED,
            $simplemeta(<<<ACTUAL
            select ?, ?
            where ? and ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true, 'abc\0xyz'], [], ['hoge' => 'HOGE']));
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
            EXPECTED,
            $pretty(<<<ACTUAL
            select ?, ?
            where ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', true], [], []));
    }

    function test_oneline()
    {
        $oneline = Logger::oneline(10);
        $this->assertEquals(<<<EXPECTED
            select 'abc...wxyz', 2, '  a  
              b  
              c  ' as white where 'abcdefgh' and 'X\\nY'
            EXPECTED,
            $oneline(<<<ACTUAL
            select ?, ?, '  a  
              b  
              c  ' as white
            where ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', "X\nY"], [], []));
    }

    function test_json()
    {
        $json = Logger::json(false);
        $this->assertEquals('{"sql":"select ?, ?, \'  a  \n  b  \n  c  \' as white\nwhere ? and ?","params":["abcdefghijklmnopqrstuvwxyz",2,"abcdefgh","X\nY"],"types":[],"hoge":"HOGE"}',
            $json(<<<ACTUAL
            select ?, ?, '  a  
              b  
              c  ' as white
            where ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', "X\nY"], [], ['hoge' => 'HOGE']));

        $json_bind = Logger::json(true);
        $this->assertEquals('{"sql":"select \'abcdefghijklmnopqrstuvwxyz\', 2, \'  a  \n  b  \n  c  \' as white\nwhere \'abcdefgh\' and \'X\\\\nY\'","hoge":"HOGE"}',
            $json_bind(<<<ACTUAL
            select ?, ?, '  a  
              b  
              c  ' as white
            where ? and ?
            ACTUAL, ['abcdefghijklmnopqrstuvwxyz', 2, 'abcdefgh', "X\nY"], [], ['hoge' => 'HOGE']));
    }

    function test_callback()
    {
        $logs = [];
        $logger = new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'callback'    => function ($sql, $params) {
                return "[prefix] $sql:" . json_encode($params);
            },
            'metadata'    => [],
        ]);

        $logger->alert('select ?, ?', ['params' => [1, 'x']]);
        $logger->alert('COMMIT', ['params' => []]);

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
            'metadata'    => [],
        ]);

        $logger->alert('select ?', ['params' => [9]]);
        $logger->alert('select ?', ['params' => [9]]);

        $this->assertStringEqualsFile($logs, "select 9\nselect 9\n");
    }

    function test_resource()
    {
        $resource = fopen(sys_get_temp_dir() . '/query.log', 'w+');
        $logger = new Logger([
            'destination' => $resource,
            'buffer'      => false,
            'metadata'    => [],
        ]);

        $logger->alert('select ?', ['params' => [9]]);

        rewind($resource);
        $this->assertEquals("select 9\n", stream_get_contents($resource));
    }

    function test_middle_resource()
    {
        $resource = fopen(sys_get_temp_dir() . '/query.log', 'w+');
        $logger = new Logger([
            'destination' => $resource,
            'buffer'      => [100, 100],
            'metadata'    => [],
        ]);

        $logger->alert('select 1, ?', ['params' => [str_repeat('x', 30)]]);
        $logger->alert('select 2, ?', ['params' => [str_repeat('x', 30)]]);
        $logger->alert('select 3, ?', ['params' => [str_repeat('x', 30)]]);
        $logger->alert('select 4, ?', ['params' => [str_repeat('x', 30)]]);

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
            'metadata'    => [],
        ]);

        $logger->alert('select ?', ['params' => [9]]);

        $this->assertEquals(['select 9'], $logs);

        $logs = [];
        $logger = new Logger([
            'destination' => function ($sql, $params) use (&$logs) { $logs[] = compact('sql', 'params'); },
            'buffer'      => 1024 * 10,
            'metadata'    => [],
        ]);

        $logger->alert('select ?', ['params' => [9]]);

        $this->assertEquals([['sql' => 'select ?', 'params' => [9]]], $logs);
    }

    function test_buffer_resource()
    {
        $resource = fopen(sys_get_temp_dir() . '/query1.log', 'w+');
        $logger = new Logger([
            'destination' => $resource,
            'buffer'      => 1024 * 10,
            'lockmode'    => true,
            'metadata'    => [],
        ]);

        $logger->alert('select ?', ['params' => [9]]);
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
        $logger1 = new Logger($logfile, ['buffer' => true, 'metadata' => []]);
        $logger2 = new Logger($logfile, ['buffer' => true, 'metadata' => []]);

        $logger1->alert('select ?', ['params' => [11]]);
        $logger2->alert('select ?', ['params' => [21]]);
        $logger1->alert('select ?', ['params' => [12]]);
        $logger2->alert('select ?', ['params' => [22]]);
        unset($logger1, $logger2);

        $this->assertEquals([
            "select 11",
            "select 12",
            "select 21",
            "select 22",
        ], file($logfile, FILE_IGNORE_NEW_LINES));
    }

    function test_level()
    {
        $logfile = sys_get_temp_dir() . '/query.log';
        @unlink($logfile);
        $logger = new Logger($logfile, ['level' => 'warning', 'buffer' => false, 'metadata' => []]);

        $logger->debug('debug');
        $logger->info('info');
        $logger->notice('notice');
        $logger->warning('warning');
        $logger->error('error');
        $logger->critical('critical');
        $logger->alert('alert');
        $logger->emergency('emergency');

        $this->assertEquals([
            "warning",
            "error",
            "critical",
            "alert",
            "emergency",
        ], file($logfile, FILE_IGNORE_NEW_LINES));
    }

    function test_transaction()
    {
        $logger = new Logger([
            'destination' => $log = tmpfile(),
            'transaction' => true,
            'metadata'    => [],
        ]);

        $logger->info('ignore');
        $logger->info('begin');
        $logger->info('transaction1');
        $logger->info('savepoint hoge');
        $logger->info('logging1');
        $logger->info('release savepoint hoge');
        $logger->info('transaction2');
        $logger->info('commit');
        $logger->info('ignore');
        $logger->info('begin');
        $logger->info('transaction1');
        $logger->info('savepoint fuga');
        $logger->info('logging2');
        $logger->info('release savepoint fuga');
        $logger->info('transaction2');
        $logger->info('rollback');
        $logger->info('ignore');
        unset($logger);

        rewind($log);
        $this->assertEquals(<<<LOG
        begin
          transaction1
          savepoint hoge
            logging1
          release savepoint hoge
          transaction2
        commit
        begin
          transaction1
          savepoint fuga
            logging2
          release savepoint fuga
          transaction2
        rollback
        
        LOG, stream_get_contents($log));
    }

    function test_metadata_default()
    {
        $logs = [];
        $database = self::getDummyDatabase();
        $database->setLogger(new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
        ]));
        $database->fetchArray('select ?', [1]);

        $this->assertStringContainsString("-- id: ", $logs[0]);
        $this->assertStringContainsString("-- time: 20", $logs[0]);
        $this->assertStringContainsString("-- elapsed: 0.", $logs[0]);
        $this->assertStringContainsString("-- traces[]", $logs[0]);
        $this->assertStringContainsString(__FILE__, $logs[0]);
    }

    function test_metadata_custom()
    {
        $seq1 = $seq2 = 1;
        $logs = [];
        $database = self::getDummyDatabase();
        $database->setLogger(new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'metadata'    => [
                'fixed' => 'direct',
                'hoge'  => function () { return 123; },
                'fuga'  => function () { return [1, 2, 3]; },
                'piyo1' => function () use (&$seq1) { return $seq1++; },
                'piyo2' => static function () use (&$seq2) { return $seq2++; },
            ],
        ]));
        $database->fetchArray('select ?', [1]);
        $database->fetchArray('select ?', [2]);
        $database->fetchArray('select ?', [3]);

        // piyo2 は固定されている
        $this->assertEquals(<<<LOG
            -- fixed: direct
            -- hoge: 123
            -- fuga[]: 1
            -- fuga[]: 2
            -- fuga[]: 3
            -- piyo1: 1
            -- piyo2: 1
            select 1
            LOG, $logs[0]);
        $this->assertEquals(<<<LOG
            -- fixed: direct
            -- hoge: 123
            -- fuga[]: 1
            -- fuga[]: 2
            -- fuga[]: 3
            -- piyo1: 2
            -- piyo2: 1
            select 2
            LOG, $logs[1]);
        $this->assertEquals(<<<LOG
            -- fixed: direct
            -- hoge: 123
            -- fuga[]: 1
            -- fuga[]: 2
            -- fuga[]: 3
            -- piyo1: 3
            -- piyo2: 1
            select 3
            LOG, $logs[2]);
    }
}
