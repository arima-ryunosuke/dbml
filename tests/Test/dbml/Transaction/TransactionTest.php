<?php

namespace ryunosuke\Test\dbml\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ryunosuke\dbml\Transaction\Logger;
use ryunosuke\dbml\Transaction\Transaction;
use ryunosuke\Test\Database;

class TransactionTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideTransaction()
    {
        return array_map(function ($v) {
            return [
                new Transaction($v[0]),
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider provideConnection
     * @param Connection $connection
     */
    function test_getDefaultOptions($connection)
    {
        $database = new Database($connection);
        $transaction = new Transaction($database);
        $options = $transaction::getDefaultOptions();
        foreach ($options as $key => $dummy) {
            $this->assertSame($transaction, $transaction->{'set' . $key}($key));
        }
        foreach ($options as $key => $dummy) {
            $this->assertSame($key, $transaction->{'get' . $key}());
        }
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test___construct($transaction, $database)
    {
        $transaction = new Transaction($database, [
            'savepointable' => true,
            'retries'       => [1, 2, 3],
        ]);
        $this->assertEquals(true, $transaction->savepointable);
        $this->assertEquals([1, 2, 3], $transaction->retries);
        $this->assertFalse(call_user_func($transaction->retryable, new \Exception()));
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test___getset($transaction, $database)
    {
        $transaction->retries = [1, 2, 3];
        $this->assertEquals([1, 2, 3], $transaction->retries);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_perform($transaction, $database)
    {
        $current = $database->count('test');

        $return = $transaction->main(function () use ($database) {
            $database->insert('test', ['id' => '99', 'name' => 'hoge']);
            return 'return-value';
        })->perform();

        // 1件増えてるはず
        $this->assertEquals($current + 1, $database->count('test'));
        // 返り値は main の返り値のはず
        $this->assertEquals('return-value', $return);

        $return = $transaction->main(function () use ($database) {
            throw new \Exception('hoge');
        }, 0)->perform(false);
        // 返り値は例外のはず
        $this->assertEquals(new \Exception('hoge'), $return);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_isolation($transaction, $database)
    {
        $current = $database->getConnection()->getTransactionIsolation();
        $transaction->setIsolationLevel(Transaction::SERIALIZABLE);

        $transaction->main(function () use ($database) {
            // このコンテキストでは SERIALIZABLE のはず
            $this->assertEquals(Transaction::SERIALIZABLE, $database->getConnection()->getTransactionIsolation());
        })->done(function () use ($database) {
            // このコンテキストでも SERIALIZABLE のはず
            $this->assertEquals(Transaction::SERIALIZABLE, $database->getConnection()->getTransactionIsolation());
        })->perform();

        // このコンテキストでは戻っているはず
        $this->assertEquals($current, $database->getConnection()->getTransactionIsolation());

        try {
            $transaction->main(function () use ($database) {
                throw new \Exception();
            })->fail(function () use ($database) {
                // このコンテキストでも SERIALIZABLE のはず
                $this->assertEquals(Transaction::SERIALIZABLE, $database->getConnection()->getTransactionIsolation());
            })->perform();

            $this->fail('ここまで来てはならない');
        }
        catch (\Exception $e) {
            // このコンテキストでは戻っているはず
            $this->assertEquals($current, $database->getConnection()->getTransactionIsolation());
        }
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_main_chain($transaction, $database)
    {
        // main は返り値がチェーンされてくるはず
        $transaction->main(function ($db, $prev = null) { return $prev + 1; });
        $transaction->main(function ($db, $prev) { return $prev + 1; });
        $transaction->main(function ($db, $prev) { return $prev + 1; });
        $this->assertEquals(3, $transaction->perform());

        // 配列を与えると置換されるはず
        $transaction->main([function ($db, $prev = null) { return $prev + 1; }]);
        $this->assertEquals(1, $transaction->perform());
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_main_order($transaction, $database)
    {
        // main は返り値がチェーンされてくるはず
        $transaction->main(function ($db, $prev = '') { return $prev . '2'; }, 2);
        $transaction->main(function ($db, $prev = '') { return $prev . '3'; }, 3);
        $transaction->main(function ($db, $prev = '') { return $prev . '1'; }, 1);
        $this->assertEquals("123", $transaction->perform());
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_retry_ok($transaction, $database)
    {
        $current = $database->count('test');
        $start = microtime(true);

        $count = 0;
        $transaction->retryable(function () { return true; })->main(function () use ($database, &$count) {
            // 3回目は成功させる
            if (++$count === 3) {
                return $database->insert('test', ['id' => '99', 'name' => 'hoge']);
            }
            throw new \Exception('hoge');
        })->retries([100, 200])->perform();

        // 1件増えてるはず
        $this->assertEquals($current + 1, $database->count('test'));
        // 少なくとも300msは経過してるはず
        usleep(1000); // for windows
        $this->assertGreaterThanOrEqual(0.3, microtime(true) - $start);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_retry_ng($transaction, $database)
    {
        $current = $database->count('test');
        $start = microtime(true);

        $count = 0;
        try {
            $transaction->retryable(function () { return true; })->main(function () use (&$count) {
                // 常に失敗する
                $count++;
                throw new \Exception('fail 3 count');
            })->retries([100, 200])->perform();

            $this->fail('ここまで来てはならない');
        }
        catch (\Exception $e) {
            // 例外が飛ぶはず
            $this->assertEquals('fail 3 count', $e->getMessage());
        }

        // 1件も増えていないはず
        $this->assertEquals($current, $database->count('test'));
        // 3回チャレンジしたはず
        $this->assertEquals(3, $count);
        // 少なくとも300msは経過してるはず
        usleep(1000); // for windows
        $this->assertGreaterThanOrEqual(0.3, microtime(true) - $start);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_retry_no($transaction, $database)
    {
        $current = $database->count('test');
        $start = microtime(true);

        $count = 0;
        try {
            $transaction->retryable(function () { return false; })->main(function () use (&$count) {
                // 常に失敗する
                $count++;
                throw new \Exception('fail 1 count');
            })->retries([1000, 2000])->perform();

            $this->fail('ここまで来てはならない');
        }
        catch (\Exception $e) {
            // 例外が飛ぶはず
            $this->assertEquals('fail 1 count', $e->getMessage());
        }

        // 1件も増えていないはず
        $this->assertEquals($current, $database->count('test'));
        // 1回しかチャレンジしてないはず
        $this->assertEquals(1, $count);
        // リトライされないのでまぁ1秒以内には返ってくるはず
        $this->assertLessThanOrEqual(1000, microtime(true) - $start);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_catch_finish($transaction, $database)
    {
        $receiver = [];
        $transaction->main(function () {
            throw new \Exception('error');
        });
        $transaction->catch(function (\Throwable $ex) use (&$receiver) {
            $receiver['catch'] = $ex->getMessage();
        });
        $transaction->finish(function () use (&$receiver) {
            $receiver['finish'] = true;
        });
        $transaction->perform(false);

        $this->assertEquals([
            'catch'  => 'error',
            'finish' => true,
        ], $receiver);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_savepoint($transaction, $database)
    {
        if (!$database->getPlatform()->supportsSavepoints()) {
            return;
        }

        $count = $transaction->savepointable(true)->main(function (Database $db) {
            // ここで1件追加すれば11件になる
            $db->insert('test', ['name' => 'XXX', 'data' => 'YYY']);

            // セーブポイント内で1件追加する
            $db->begin();
            $db->insert('test', ['name' => 'XXX', 'data' => 'YYY']);

            // セーブポイントの状態を返す$db
            $db->rollback();
            return $db->count('test');
        })->perform();

        // 追加した1件は有効なので11件のはず
        $this->assertEquals(11, $count);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_event($transaction, $database)
    {
        $log = [];

        $transaction->retryable(function () { return true; })->begin(function () use (&$log) {
            $log[] = 'begin';
        })->commit(function () use (&$log) {
            $log[] = 'commit';
        })->rollback(function () use (&$log) {
            $log[] = 'rollback';
        })->done(function () use (&$log) {
            $log[] = 'done';
        })->fail(function () use (&$log) {
            $log[] = 'fail';
        })->retry(function ($rc) use (&$log) {
            $log[] = 'retry' . $rc;
        });

        $log = [];
        $transaction->main(function () use (&$log) {
            $log[] = 'main';
        }, 0)->retries([0])->perform(false);
        $this->assertEquals(['begin', 'main', 'commit', 'done'], $log);

        $log = [];
        $transaction->main(function () use (&$log) {
            $log[] = 'main';
            throw new \Exception('main');
        }, 0)->retries([])->perform(false);
        $this->assertEquals(['begin', 'main', 'rollback', 'fail'], $log);

        $rcount = 0;
        $log = [];
        $transaction->main(function () use (&$log, &$rcount) {
            $log[] = 'main';
            if ($rcount++ === 1) {
                return null;
            }
            throw new \Exception('main');
        }, 0)->retries([0])->perform(false);
        $this->assertEquals(['begin', 'main', 'rollback', 'fail', 'retry1', 'begin', 'main', 'commit', 'done'], $log);

        $log = [];
        $transaction->main(function () use (&$log) {
            $log[] = 'main';
            throw new \Exception('main');
        }, 0)->retries([0])->perform(false);
        $this->assertEquals(['begin', 'main', 'rollback', 'fail', 'retry1', 'begin', 'main', 'rollback', 'fail'], $log);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_event_ex($transaction, $database)
    {
        $transaction->retryable(function () { return true; })->done(function () {
            throw new \Exception('done');
        })->fail(function () {
            throw new \Exception('fail');
        })->retry(function () {
            throw new \Exception('retry');
        });

        // done が例外を投げるとリトライもクソもなくすぐさま処理を返すはず
        $ex = $transaction->main(function () {
            return null;
        })->__invoke(false);
        $this->assertEquals('done', $ex->getMessage());

        // fail (同上)
        $ex = $transaction->main(function () {
            throw new \Exception('main');
        })->__invoke(false);
        $this->assertEquals('fail', $ex->getMessage());

        // retry (同上)
        $ex = $transaction->fail(function () {
            //
        }, 0)->retries([0])->__invoke(false);
        $this->assertEquals('retry', $ex->getMessage());
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_logger($transaction, $database)
    {
        // 無駄なメタクエリが検出されないようにあらかじめ投げておく
        $database->getSchema()->getTable('test');

        $logsT = [];
        $loggerT = new Logger([
            'destination' => function ($sql, $params) use (&$logsT) {
                $logsT[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $logsC = [];
        $loggerC = new Logger([
            'destination' => function ($sql, $params) use (&$logsC) {
                $logsC[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $transaction->logger($loggerT);
        $database->getMasterConnection()->getConfiguration()->setSQLLogger($loggerC);

        $transaction->main(function (Database $db) {
            $db->delete('test', ['id' => 2]);
        });

        // preview では 参照引数に集約されるので一切ログられない
        $transaction->preview($queries);
        $this->assertCount(0, $logsT);
        $this->assertCount(0, $logsC);
        $this->assertCount(3, $queries);

        // perform は共にログられる
        $transaction->perform();
        $this->assertCount(3, $logsT);
        $this->assertCount(3, $logsC);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_preview($transaction, $database)
    {
        $logsT = [];
        $loggerT = new Logger([
            'destination' => function ($sql, $params) use (&$logsT) {
                $logsT[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $logsC = [];
        $loggerC = new Logger([
            'destination' => function ($sql, $params) use (&$logsC) {
                $logsC[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $transaction->logger($loggerT);
        $database->getMasterConnection()->getConfiguration()->setSQLLogger($loggerC);

        $return = $transaction->main(function (Database $db) {
            $db->insert('test', ['name' => 'HOGE']);
            $db->insert('test', ['name' => 'HOGE']);
            $db->delete('test', ['id' => 2]);
            return $db->selectArray('test.name', ['id' => [1, 2]]);
        })->preview($queries);

        // クエリ取得に logger を使用してるのでもとに戻っているか担保する
        $this->assertSame($loggerT, $transaction->logger);
        // かつ元の logger にはログられていない（あくまでプレビューなので本家にログられても困る）
        $this->assertCount(0, $logsT);
        // さらに元 connection のロガーにもログられていない（preview = 内部でトランザクションしているという前提を剥き出しにするのはよくない）
        $this->assertCount(0, $logsC);
        // $return に実行結果が入っているはず（id:2 は消してるので1件だけ）
        $this->assertCount(1, $return);
        $this->assertEquals(['name' => 'a'], $return[0]);
        // $queries に実行ログが入っているはず
        $this->assertEquals([
            "START TRANSACTION",
            "INSERT INTO test (name) VALUES ('HOGE')",
            "INSERT INTO test (name) VALUES ('HOGE')",
            "DELETE FROM test WHERE id = 2",
            "SELECT test.name FROM test WHERE id IN (1,2) ORDER BY test.id ASC",
            "ROLLBACK",
        ], $queries);

        $transaction->main([
            function (Database $db) {
                $db->delete('test', ['id' => 1]);
            },
        ])->preview($queries);
        $this->assertEquals([
            "START TRANSACTION",
            "DELETE FROM test WHERE id = 1",
            "ROLLBACK",
        ], $queries);

        // あくまでプレビューなのでロールバックされてるはず
        $this->assertEquals(10, $database->count('test'));

        $database->getMasterConnection()->getConfiguration()->setSQLLogger(null);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_presview_ex($transaction, $database)
    {
        $logsT = [];
        $loggerT = new Logger([
            'destination' => function ($sql, $params) use (&$logsT) {
                $logsT[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $logsC = [];
        $loggerC = new Logger([
            'destination' => function ($sql, $params) use (&$logsC) {
                $logsC[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $transaction->logger($loggerT);
        $database->getMasterConnection()->getConfiguration()->setSQLLogger($loggerC);

        $transaction->main(function (Database $db) {
            $db->insert('test', ['name' => 'HOGE']);
            $db->insert('test', ['name' => 'HOGE']);
            $db->delete('test', ['id' => 2]);
        })->preview($queries);

        // クエリ取得に logger を使用してるのでもとに戻っているか担保する
        $this->assertSame($loggerT, $transaction->logger);
        // かつ元の logger にはログられていない（あくまでプレビューなので本家にログられても困る）
        $this->assertCount(0, $logsT);
        // さらに元 connection のロガーにもログられていない（preview = 内部でトランザクションしているという前提を剥き出しにするのはよくない）
        $this->assertCount(0, $logsC);
        // $queries に実行ログが入っているはず
        $subset = [
            "START TRANSACTION",
            "INSERT INTO test (name) VALUES ('HOGE')",
            "INSERT INTO test (name) VALUES ('HOGE')",
            "DELETE FROM test WHERE id = 2",
            "ROLLBACK",
        ];
        $this->assertEquals($subset, array_values(array_intersect($subset, $queries)));

        $database->getMasterConnection()->getConfiguration()->setSQLLogger(null);
    }

    function test_masterslave()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $master->executeStatement('CREATE TABLE test(id integer)');
        $slave->executeStatement('CREATE TABLE test(id integer)');
        $master->insert('test', ['id' => 66]);
        $database = new Database([$master, $slave]);

        $transaction = new Transaction($database);
        $transaction->main(function (Database $db) {
            return $db->selectArray('test');
        });

        // master() するとマスターで実行される
        $return = $transaction->master()->perform();
        $this->assertEquals([['id' => 66]], $return);

        // slave() するとスレーブで実行される
        $return = $transaction->slave()->perform();
        $this->assertEquals([], $return);

        // いずれにせよ Database の接続は変わっていないはず
        $this->assertSame($master, $database->getConnection());
    }
}
