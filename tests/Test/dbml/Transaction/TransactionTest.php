<?php

namespace ryunosuke\Test\dbml\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\TransactionIsolationLevel;
use ryunosuke\dbml\Logging\Logger;
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
        ]);
        $ex1 = (new \ReflectionClass(UniqueConstraintViolationException::class))->newInstanceWithoutConstructor();
        $ex2 = (new \ReflectionClass(LockWaitTimeoutException::class))->newInstanceWithoutConstructor();
        $this->assertEquals(null, ($transaction->retryable)(100, $ex1, $database->getConnection()));
        $this->assertEquals(null, ($transaction->retryable)(100, $ex2, $database->getConnection()));
        $this->assertEquals(0.1, ($transaction->retryable)(0, $ex1, $database->getConnection()));
        $this->assertEquals(1.0, ($transaction->retryable)(0, $ex2, $database->getConnection()));
        $this->assertEquals(null, ($transaction->retryable)(0, new \Exception(), $database->getConnection()));
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test___getset($transaction, $database)
    {
        $transaction->isolationLevel = TransactionIsolationLevel::SERIALIZABLE;
        $this->assertEquals(TransactionIsolationLevel::SERIALIZABLE, $transaction->isolationLevel);
    }

    function test___debugInfo()
    {
        $transaction = new Transaction(self::getDummyDatabase());
        $debugString = print_r($transaction, true);
        $this->assertStringNotContainsString('database:', $debugString);
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
        catch (\Exception) {
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
        $transaction->retryable(function (int $retryCount, \Exception $ex) {
            return ($retryCount + 1) * 0.1;
        })->main(function () use ($database, &$count) {
            // 3回目は成功させる
            if (++$count === 3) {
                return $database->insert('test', ['id' => '99', 'name' => 'hoge']);
            }
            throw new \Exception('hoge');
        })->perform();

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
            $transaction->retryable(function (int $retryCount, \Exception $ex) {
                if ($retryCount >= 2) {
                    return null;
                }
                return ($retryCount + 1) * 0.1;
            })->main(function () use (&$count) {
                // 常に失敗する
                $count++;
                throw new \Exception('fail 3 count');
            })->perform();

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
            $transaction->retryable(function (int $retryCount, \Exception $ex) {
                return null;
            })->main(function () use (&$count) {
                // 常に失敗する
                $count++;
                throw new \Exception('fail 1 count');
            })->perform();

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
        $this->assertLessThanOrEqual(1, microtime(true) - $start);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_retry_closure($transaction, $database)
    {
        $database->delete('test', ['id >= ?' => 3]);
        $start = microtime(true);

        $count = 1;
        $transaction->retryable(function (int $retryCount, \Exception $ex) {
            if ($ex instanceof UniqueConstraintViolationException) {
                return 0.1;
            }
        })->main(function () use ($database, &$count) {
            $database->insert('test', [
                'id'   => $count++,
                'name' => 'x',
            ]);
        })->perform();

        // 挿入されているはず
        $this->assertEquals('x', $database->selectValue('test.name', ['id' => 3]));
        // id1, id2 が重複でコケるのでまぁ 0.5 秒くらいで終わるはず
        $this->assertLessThanOrEqual(0.5, microtime(true) - $start);
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

        $transaction->begin(function () use (&$log) {
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
        }, 0)->retryable(function (int $retryCount, \Exception $ex) {
            return $retryCount === 0 ? 0.1 : null;
        })->perform(false);
        $this->assertEquals(['begin', 'main', 'commit', 'done'], $log);

        $log = [];
        $transaction->main(function () use (&$log) {
            $log[] = 'main';
            throw new \Exception('main');
        }, 0)->retryable(function (int $retryCount, \Exception $ex) {
            return null;
        })->perform(false);
        $this->assertEquals(['begin', 'main', 'rollback', 'fail'], $log);

        $rcount = 0;
        $log = [];
        $transaction->main(function () use (&$log, &$rcount) {
            $log[] = 'main';
            if ($rcount++ === 1) {
                return null;
            }
            throw new \Exception('main');
        }, 0)->retryable(function (int $retryCount, \Exception $ex) {
            return $retryCount === 0 ? 0.1 : null;
        })->perform(false);
        $this->assertEquals(['begin', 'main', 'rollback', 'fail', 'retry1', 'begin', 'main', 'commit', 'done'], $log);

        $log = [];
        $transaction->main(function () use (&$log) {
            $log[] = 'main';
            throw new \Exception('main');
        }, 0)->retryable(function (int $retryCount, \Exception $ex) {
            return $retryCount === 0 ? 0.1 : null;
        })->perform(false);
        $this->assertEquals(['begin', 'main', 'rollback', 'fail', 'retry1', 'begin', 'main', 'rollback', 'fail'], $log);
    }

    /**
     * @dataProvider provideTransaction
     * @param Transaction $transaction
     * @param Database $database
     */
    function test_event_ex($transaction, $database)
    {
        $transaction->done(function () {
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
        }, 0)->retryable(function (int $retryCount, \Exception $ex) {
            return $retryCount === 0 ? 0.1 : null;
        })->__invoke(false);
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
        $database->setLogger($loggerC);

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
        $database->setLogger($loggerC);

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
            "BEGIN",
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
            "BEGIN",
            "DELETE FROM test WHERE id = 1",
            "ROLLBACK",
        ], $queries);

        // あくまでプレビューなのでロールバックされてるはず
        $this->assertEquals(10, $database->count('test'));

        $database->setLogger([]);
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
        $database->setLogger($loggerC);

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
            "BEGIN",
            "INSERT INTO test (name) VALUES ('HOGE')",
            "INSERT INTO test (name) VALUES ('HOGE')",
            "DELETE FROM test WHERE id = 2",
            "ROLLBACK",
        ];
        $this->assertEquals($subset, array_values(array_intersect($subset, $queries)));

        $database->setLogger([]);
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
