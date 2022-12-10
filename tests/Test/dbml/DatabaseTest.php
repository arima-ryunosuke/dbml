<?php

namespace ryunosuke\Test\dbml;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Psr\SimpleCache\CacheInterface;
use ryunosuke\dbml\Entity\Entity;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Exception\NonSelectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Logger;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\dbml\Transaction\Transaction;
use ryunosuke\Test\Database;
use ryunosuke\Test\Entity\Article;
use ryunosuke\Test\Entity\Comment;
use ryunosuke\Test\Entity\ManagedComment;
use function ryunosuke\dbml\array_order;
use function ryunosuke\dbml\array_remove;
use function ryunosuke\dbml\mkdir_p;
use function ryunosuke\dbml\rm_rf;
use function ryunosuke\dbml\try_null;
use function ryunosuke\dbml\try_return;

class DatabaseTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @dataProvider provideConnection
     * @param Connection $connection
     */
    function test_getDefaultOptions($connection)
    {
        $database = new Database($connection);
        $options = $database::getDefaultOptions();
        foreach ($options as $key => $dummy) {
            if (!in_array($key, ['logger', 'autoCastType', 'compatiblePlatform'])) {
                $this->assertSame($database, $database->{'set' . $key}($key));
            }
        }
        foreach ($options as $key => $dummy) {
            if (!in_array($key, ['logger', 'autoCastType', 'compatiblePlatform'])) {
                $this->assertSame($key, $database->{'get' . $key}());
            }
        }
    }

    function test___construct_ms()
    {
        // 普通の配列はシングル構成になる
        $db = new Database([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'port'   => 1234,
            'dbname' => 'masterslave',
        ]);
        $this->assertSame($db->getMasterConnection(), $db->getSlaveConnection());
        $this->assertEquals([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'port'   => 1234,
            'dbname' => 'masterslave',
        ], $db->getConnection()->getParams());

        // 配列で与えればマスタースレーブ構成になる
        $db = new Database([
            'driver' => 'pdo_sqlite',
            'memory' => [true, false],
            'port'   => [1234, 5678],
            'dbname' => 'masterslave',
        ]);
        $this->assertNotSame($db->getMasterConnection(), $db->getSlaveConnection());
        $this->assertEquals([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'port'   => 1234,
            'dbname' => 'masterslave',
        ], $db->getMasterConnection()->getParams());
        $this->assertEquals([
            'driver' => 'pdo_sqlite',
            'memory' => false,
            'port'   => 5678,
            'dbname' => 'masterslave',
        ], $db->getSlaveConnection()->getParams());

        // logger and initCommand and cacheProvider
        $tmpdir = sys_get_temp_dir() . '/dbml/tmp';
        rm_rf($tmpdir);
        mkdir_p($tmpdir);
        $db = new Database(['url' => 'sqlite:///:memory:'], [
            'logger'      => new Logger([
                'destination' => "$tmpdir/log.txt",
                'metadata'    => [],
            ]),
            'initCommand' => 'PRAGMA cache_size = 1000',
        ]);
        $db->getMasterConnection()->connect();
        $db->getSlaveConnection()->connect();
        unset($db);
        gc_collect_cycles();
        $this->assertStringEqualsFile("$tmpdir/log.txt", <<<LOG
        Connecting
        PRAGMA cache_size = 1000
        Disconnecting
        
        LOG,);

        $this->assertException('$dbconfig must be', function () { new Database(null); });
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___isset($database)
    {
        $this->assertTrue(isset($database->test));
        $this->assertTrue(isset($database->Comment));
        $this->assertFalse(isset($database->hogera));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___unset($database)
    {
        $test = $database->test;
        $this->assertSame($test, $database->test);
        unset($database->test);
        $this->assertNotSame($test, $database->test);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___get($database)
    {
        $this->assertInstanceOf(TableGateway::class, $database->test);
        $this->assertInstanceOf(TableGateway::class, $database->Comment);
        $this->assertNull($database->hogera);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___set($database)
    {
        $this->assertException('is not supported', fn() => $database->test = 123);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___call($database)
    {
        // aggregate 系
        $this->assertIsInt($database->count('test'));
        $this->assertIsFloat($database->avg('test.id'));

        // select は select のはず
        $this->assertInstanceOf(QueryBuilder::class, $database->select('t', []));

        // select 系
        $this->assertEquals($database->selectArray('test'), $database->select('test')->array());
        $this->assertEquals($database->selectValue('test', [], [], 1), $database->select('test', [], [], 1)->value());
        $this->assertEquals($database->selectTuple('test', [], [], 1), $database->select('test', [], [], 1)->tuple());

        // select～ForUpdate|InShare 系(ロックされることを担保・・・は難しいのでエラーにならないことを担保)
        $this->assertEquals($database->selectArray('test'), $database->selectArrayInShare('test'));
        $this->assertEquals($database->selectValue('test', [], [], 1), $database->selectValueInShare('test', [], [], 1));
        $this->assertEquals($database->selectTuple('test', [], [], 1), $database->selectTupleForUpdate('test', [], [], 1));

        // select～OrThrow 系(見つかる場合に同じ結果になることを担保)
        $this->assertEquals($database->selectArray('test'), $database->selectArrayOrThrow('test'));
        $this->assertEquals($database->selectValue('test', [], [], 1), $database->selectValueOrThrow('test', [], [], 1));
        $this->assertEquals($database->selectTuple('test', [], [], 1), $database->selectTupleOrThrow('test', [], [], 1));

        // select～OrThrow 系(見つからなかった場合に例外が投がることを担保)
        $ex = new NonSelectedException('record is not found');
        $this->assertException($ex, L($database)->selectArrayOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectValueOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectTupleOrThrow('test', ['1=0']));

        // select～ForAffect 系(見つからなかった場合に例外が投がることを担保)
        $this->assertEquals($database->selectArray('test'), $database->selectArrayForAffect('test'));
        $this->assertException($ex, L($database)->selectValueForAffect('test', ['1=0']));

        // fetch～OrThrow 系(見つかる場合に同じ結果になることを担保)
        $sql = 'select id from test where id = 3';
        $this->assertEquals($database->fetchArray($sql), $database->fetchArrayOrThrow($sql));
        $this->assertEquals($database->fetchValue($sql), $database->fetchValueOrThrow($sql));
        $this->assertEquals($database->fetchTuple($sql), $database->fetchTupleOrThrow($sql));

        // fetch～OrThrow 系(見つからなかった場合に例外が投がることを担保)
        $sql = 'select id from test where 1=0';
        $ex = new NonSelectedException('record is not found');
        $this->assertException($ex, L($database)->fetchArrayOrThrow($sql));
        $this->assertException($ex, L($database)->fetchValueOrThrow($sql));
        $this->assertException($ex, L($database)->fetchTupleOrThrow($sql));

        // 作用行系(作用した場合に主キーが返ることを担保)
        $this->assertEquals(['id' => 99], $database->insertOrThrow('test', ['id' => 99]));
        $this->assertEquals(['id' => 99], $database->upsertOrThrow('test', ['id' => 99, 'name' => 'hogera']));
        $this->assertEquals(['id' => 99], $database->modifyOrThrow('test', ['id' => 99, 'name' => 'rageho']));
        // ON AUTO_INCREMENT
        $lastid = $database->create('test', ['name' => 'hogera']);
        $this->assertEquals(['id' => $database->getLastInsertId('test', 'id')], $lastid);
        $lastid = $database->upsertOrThrow('test', ['name' => 'hogera']);
        $this->assertEquals(['id' => $database->getLastInsertId('test', 'id')], $lastid);
        $lastid = $database->modifyOrThrow('test', ['name' => 'hogera']);
        $this->assertEquals(['id' => $database->getLastInsertId('test', 'id')], $lastid);
        // NO AUTO_INCREMENT
        $this->assertEquals(['id' => 'a'], $database->insertOrThrow('noauto', ['id' => 'a', 'name' => 'hogera']));
        $this->assertEquals(['id' => 'b'], $database->upsertOrThrow('noauto', ['id' => 'b', 'name' => 'hogera']));
        $this->assertEquals(['id' => 'c'], $database->modifyOrThrow('noauto', ['id' => 'c', 'name' => 'hogera']));

        // update/delete
        $this->assertEquals(['id' => 1], $database->updateOrThrow('test', ['name' => 'hogera'], ['id' => 1]));
        $this->assertEquals(['id' => 1], $database->deleteOrThrow('test', ['id' => 1]));
        $this->assertEquals(['id' => 2], $database->removeOrThrow('test', ['id' => 2]));
        $this->assertEquals(['id' => 3], $database->destroyOrThrow('test', ['id' => 3]));

        // 作用行系(見つからなかった場合に例外が投がることを担保)
        $ex = new NonAffectedException('affected row is nothing');
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $this->assertException($ex, L($database)->insert('test', ['id' => 9, 'name' => 'hoge'], ['throw' => true, 'ignore' => true]));
            if ($database->getPlatform() instanceof SqlitePlatform) {
                $this->assertException($ex, L($database)->modify('test', ['id' => 9, 'name' => null], [], ['throw' => true, 'ignore' => true]));
            }
        }
        $this->assertException($ex, L($database)->updateOrThrow('test', ['name' => 'd'], ['id' => -1]));
        $this->assertException($ex, L($database)->deleteOrThrow('test', ['id' => -1]));
        $this->assertException($ex, L($database)->removeOrThrow('test', ['id' => -1]));
        $this->assertException($ex, L($database)->destroyOrThrow('test', ['id' => -1]));
        if ($database->getCompatiblePlatform()->supportsZeroAffectedUpdate()) {
            $this->assertException($ex, L($database)->upsertOrThrow('test', ['id' => 9, 'name' => 'i', 'data' => '']));
        }

        // affectConditionally
        $database->truncate('noauto');
        $this->assertEquals(['id' => 'a'], $database->insertConditionally('noauto', ['id' => ''], ['id' => 'a', 'name' => 'hoge']));
        $this->assertEquals(['id' => 'b'], $database->upsertConditionally('noauto', ['id' => ''], ['id' => 'b', 'name' => 'fuga']));
        $this->assertEquals(['id' => 'c'], $database->modifyConditionally('noauto', ['id' => ''], ['id' => 'c', 'name' => 'piyo']));
        $this->assertEquals([], $database->insertConditionally('noauto', ['id' => 'a'], ['id' => 'a', 'name' => 'hoge']));
        $this->assertEquals([], $database->upsertConditionally('noauto', ['id' => 'b'], ['id' => 'b', 'name' => 'fuga']));
        $this->assertEquals([], $database->modifyConditionally('noauto', ['id' => 'c'], ['id' => 'c', 'name' => 'piyo']));

        // affectIgnore
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $database->truncate('noauto');
            $database->insert('noauto', ['id' => 'x', 'name' => '']);
            $this->assertEquals(['id' => 'b'], $database->createIgnore('noauto', ['id' => 'b', 'name' => 'hoge']));
            $this->assertEquals(['id' => 'a'], $database->insertIgnore('noauto', ['id' => 'a', 'name' => 'hoge']));
            $this->assertEquals(['id' => 'a'], $database->updateIgnore('noauto', ['name' => 'fuga'], ['id' => 'a']));
            $this->assertEquals(['id' => 'a'], $database->modifyIgnore('noauto', ['id' => 'a', 'name' => 'piyo']));
            $this->assertEquals([], $database->createIgnore('noauto', ['id' => 'x']));
            $this->assertEquals([], $database->insertIgnore('noauto', ['id' => 'x']));
            $this->assertEquals([], $database->updateIgnore('noauto', ['id' => 'x'], ['id' => 'a']));
            // insert しようとしてダメでさらに update しようとしてダメだった場合に無視できるのは mysql のみ（本当は方法があるのかもしれないが詳しくないのでわからない）
            if ($database->getPlatform() instanceof MySQLPlatform) {
                $this->assertEquals([], $database->modifyIgnore('noauto', ['id' => 'x'], ['id' => 'a']));
            }

            // array 系
            $database->truncate('noauto');
            $database->insert('noauto', ['id' => 1, 'name' => '']);
            $database->insert('noauto', ['id' => 2, 'name' => '']);
            $this->assertEquals(0, $database->insertSelectIgnore('noauto', 'select 1 union select 2', ['id']));
            $this->assertEquals(0, $database->insertArrayIgnore('noauto', [
                ['id' => 1, 'name' => ''],
                ['id' => 2, 'name' => ''],
            ]));
            $this->assertEquals(0, $database->updateArrayIgnore('noauto', [
                ['id' => 1, 'name' => null],
                ['id' => 2, 'name' => null],
            ]));
            $this->assertEquals(0, $database->modifyArrayIgnore('noauto', [
                ['id' => 1, 'name' => null],
                ['id' => 2, 'name' => null],
            ]));
            $this->assertEquals([
                [],
                [],
            ], $database->changeArrayIgnore('noauto', [
                ['name' => null],
                ['name' => null],
            ], false));

            $database->import([
                'foreign_p' => [
                    [
                        'id'         => 1,
                        'name'       => 'P1',
                        'foreign_c1' => [],
                    ],
                    [
                        'id'         => 2,
                        'name'       => 'P2',
                        'foreign_c1' => [['seq' => 1, 'name' => 'C']],
                    ],
                ],
            ]);

            // for coverage
            $this->assertEquals([], $database->delete('foreign_c1', ['id' => -1], ['primary' => 2]));
            $this->assertEquals([], $database->remove('foreign_c1', ['id' => -1], ['primary' => 2]));
            $this->assertEquals([], $database->destroy('foreign_c1', ['id' => -1], ['primary' => 2]));

            if ($database->getPlatform() instanceof MySQLPlatform) {
                $this->assertEquals(['id' => 1], $database->deleteIgnore('foreign_p', ['id' => 1]));
            }
        }

        // テーブル記法＋OrThrowもきちんと動くことを担保
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $this->assertEquals(['id' => 199], $database->insertOrThrow('test.id', 199));
            $this->assertEquals(['id' => 199], $database->upsertOrThrow('test.id, name', [199, 'hogera']));
        }
        $lastid = $database->insertOrThrow('test.name', 'foobar');
        $this->assertEquals('foobar', $database->selectValue('test.name', $lastid));

        // Gateway 系
        $this->assertInstanceOf(TableGateway::class, $database->test());
        $this->assertInstanceOf(TableGateway::class, $database->Comment('*'));

        // 基本的には Gateway を返す。ただし数値のときは pk になる
        $this->assertEquals([
            'id'   => '4',
            'name' => 'd',
            'data' => '',
        ], $database->test(4)->tuple());

        // H は存在しないはず
        $this->assertException(new \BadMethodCallException(), [$database, 'selectH'], 'hoge');

        // 引数が足りない
        $this->assertException(new \InvalidArgumentException('too short'), L($database)->insertOrThrow('test'));
        $this->assertException(new \InvalidArgumentException('too short'), L($database)->insertConditionally('test', []));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___debugInfo($database)
    {
        $this->assertArrayNotHasKey('cache', $database->__debugInfo());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test__normalize($database)
    {
        $_normalize = self::forcedCallize($database, '_normalize');
        $row = $database->Article->pk(1)->tuple();
        $this->assertSame($row->arrayize(), $_normalize('t_article', $row));

        $row->article_id = 99;
        $database->insert('t_article', $row);
        $this->assertTrue($database->exists('t_article(99)'));

        $row->title = 'newest';
        $database->update('t_article', $row, ['article_id' => $row->article_id]);
        $this->assertEquals('newest', $database->selectValue('t_article(99).title'));

        $database->delete('t_article', ['article_id' => $row->article_id]);
        $this->assertFalse($database->exists('t_article(99)'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test__normalizes($database)
    {
        $_normalizes = self::forcedCallize($database, '_normalizes');

        // 省略時は主キー
        $this->assertEquals([
            'id'   => [1, 2],
            'name' => $database->raw('CASE id WHEN ? THEN ? WHEN ? THEN ? ELSE name END', [1, 'a', 2, 'b']),
        ], $_normalizes('test', [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
        ]));

        // マルチ主キーでも動く
        $this->assertEquals([
            'mainid' => [1, 1],
            'subid'  => [1, 2],
            'name'   => $database->raw('CASE WHEN mainid = ? AND subid = ? THEN ? WHEN mainid = ? AND subid = ? THEN ? ELSE name END', [1, 1, 'a', 1, 2, 'b']),
        ], $_normalizes('multiprimary', [
            ['mainid' => 1, 'subid' => 1, 'name' => 'a'],
            ['mainid' => 1, 'subid' => 2, 'name' => 'b'],
        ]));

        // カラムを明示的に指定できる
        $this->assertEquals([
            'mainid' => [1, 2],
            'subid'  => $database->raw('CASE mainid WHEN ? THEN ? WHEN ? THEN ? ELSE subid END', [1, 1, 2, 2]),
            'name'   => $database->raw('CASE mainid WHEN ? THEN ? WHEN ? THEN ? ELSE name END', [1, 'a', 2, 'b']),
        ], $_normalizes('multiprimary', [
            ['mainid' => 1, 'subid' => 1, 'name' => 'a'],
            ['mainid' => 2, 'subid' => 2, 'name' => 'b'],
        ], ['mainid']));
    }

    function test_masterslave()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

        $master->executeStatement('CREATE TABLE test(id integer)');
        $slave->executeStatement('CREATE TABLE test(id integer)');

        $database = new Database([$master, $slave]);

        // 1件突っ込むと・・・
        $database->insert('test', ['id' => 1]);

        // マスターには登録されているが・・・
        $this->assertEquals([['id' => 1]], $master->fetchAllAssociative('select * from test'));

        // スレーブでは取得できない
        $this->assertEquals([], $database->selectArray('test'));

        // マスターモードにすると取得できる
        $this->assertEquals([['id' => 1]], $database->context()->setMasterMode(true)->selectArray('test'));

        // RDBMS が異なると例外が飛ぶ
        $this->assertException('must be same platform', function () {
            $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
            $slave = DriverManager::getConnection(['url' => 'mysql://localhost/testdb']);
            new Database([$master, $slave]);
        });
    }

    function test_getConnections()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

        $database = new Database([$master, $slave]);
        $this->assertCount(2, $database->getConnections());

        $database = new Database([$master]);
        $this->assertCount(1, $database->getConnections());

        $database = new Database([$master, $master]);
        $this->assertCount(1, $database->getConnections());
    }

    function test_compatiblePlatform()
    {
        // デフォルト
        $database = self::getDummyDatabase();
        $this->assertInstanceOf(CompatiblePlatform::class, $database->getCompatiblePlatform());

        $cplatform = new class(new SqlitePlatform()) extends CompatiblePlatform {
        };

        // クラス名
        $database = new Database(DriverManager::getConnection(['url' => 'sqlite:///:memory:']), [
            'compatiblePlatform' => get_class($cplatform),
        ]);
        $this->assertInstanceOf(get_class($cplatform), $database->getCompatiblePlatform());

        // インスタンス
        $database = new Database(DriverManager::getConnection(['url' => 'sqlite:///:memory:']), [
            'compatiblePlatform' => $cplatform,
        ]);
        $this->assertSame($cplatform, $database->getCompatiblePlatform());
    }

    function test_getSchema()
    {
        $connection = DriverManager::getConnection([
            'url' => 'sqlite:///:memory:',
        ]);
        $callcount = 0;
        $database = new Database($connection, [
            'onRequireSchema' => function () use (&$callcount) {
                $callcount++;
            },
        ]);
        $this->assertSame($database->getSchema(), $database->getSchema());
        $this->assertSame($database->dryrun()->getSchema(), $database->getSchema());
        $this->assertEquals(1, $callcount);
    }

    function test_getPdo()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

        $database = new Database([$master, $slave]);

        $this->assertSame($master->getNativeConnection(), $database->getPdo());
        $this->assertSame($master->getNativeConnection(), $database->getMasterPdo());
        $this->assertSame($slave->getNativeConnection(), $database->getSlavePdo());
        $this->assertSame($master->getNativeConnection(), $database->setMasterMode(true)->getSlavePdo());
    }

    function test_setPdoAttribute()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);

        /** @var \PDO $mPdo */
        /** @var \PDO $sPdo */
        $mPdo = $master->getNativeConnection();
        $sPdo = $slave->getNativeConnection();

        /// マスターだけモード

        $database = new Database($master);

        // 2属性変更してみる
        $restorer = $database->setPdoAttribute([
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_SILENT,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_NUM,
        ]);
        // master/slave 共に変更されているはず
        $this->assertEquals(\PDO::ERRMODE_SILENT, $mPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertEquals(\PDO::FETCH_NUM, $mPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));

        // 元に戻してみる
        $restorer();
        // 元に戻っているはず（元の値に言及したくないので NotEquals）
        $this->assertNotEquals(\PDO::ERRMODE_SILENT, $mPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertNotEquals(\PDO::FETCH_NUM, $mPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));

        $database = new Database([$master, $slave]);

        /// 複数モード

        // 2属性変更してみる
        $restorer = $database->setPdoAttribute([
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_SILENT,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_NUM,
        ]);
        // master/slave 共に変更されているはず
        $this->assertEquals(\PDO::ERRMODE_SILENT, $mPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertEquals(\PDO::FETCH_NUM, $mPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertEquals(\PDO::ERRMODE_SILENT, $sPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertEquals(\PDO::FETCH_NUM, $sPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));

        // 元に戻してみる
        $restorer();
        // 元に戻っているはず（元の値に言及したくないので NotEquals）
        $this->assertNotEquals(\PDO::ERRMODE_SILENT, $mPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertNotEquals(\PDO::FETCH_NUM, $mPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertNotEquals(\PDO::ERRMODE_SILENT, $sPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertNotEquals(\PDO::FETCH_NUM, $sPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));

        // master だけ変更してみる
        $restorer = $database->setPdoAttribute([
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_SILENT,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_NUM,
        ], 'master');
        // master/slave だけ変更されているはず
        $this->assertEquals(\PDO::ERRMODE_SILENT, $mPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertEquals(\PDO::FETCH_NUM, $mPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertNotEquals(\PDO::ERRMODE_SILENT, $sPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertNotEquals(\PDO::FETCH_NUM, $sPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));

        // 元に戻してみる
        $restorer();
        // 元に戻っているはず（slave には言及しない）
        $this->assertNotEquals(\PDO::ERRMODE_SILENT, $mPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertNotEquals(\PDO::FETCH_NUM, $mPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertNotEquals(\PDO::ERRMODE_SILENT, $sPdo->getAttribute(\PDO::ATTR_ERRMODE));
        $this->assertNotEquals(\PDO::FETCH_NUM, $sPdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_isEmulationMode($database)
    {
        $expected = [
            'sqlite'     => [true, true],
            'mysql'      => [false, true],
            'postgresql' => [false, true],
            'mssql'      => [version_compare(PHP_VERSION, 8.1) < 0, true],
        ];

        try {
            try {
                $database->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            }
            catch (\Exception $e) {
            }
            $this->assertSame($expected[$database->getCompatiblePlatform()->getName()][false], $database->isEmulationMode(true));

            try {
                $database->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            }
            catch (\Exception $e) {
            }
            $this->assertSame($expected[$database->getCompatiblePlatform()->getName()][true], $database->isEmulationMode(true));
        }
        finally {
            try {
                $database->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            }
            catch (\Exception $e) {
            }
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_stackcontext($database)
    {
        // context はチェーンしないと設定が効かない
        $database->context()->setInsertSet(true);
        $this->assertFalse($database->getInsertSet());

        // チェーンすれば設定が効く
        $this->assertTrue($database->context()->setInsertSet(true)->getInsertSet());

        // stack は解除するまで設定が効く
        $database->stack();
        $database->setInsertSet(true);
        $this->assertTrue($database->getInsertSet());

        $database->unstack();
        $this->assertFalse($database->getInsertSet());

        // どっちも例外発生時はもとに戻る
        try {
            $cx = $database->context();
            $cx->setInsertSet(true)->fetchTuple('invalid query.');
        }
        catch (\Exception $ex) {
            $this->assertFalse($database->getInsertSet());
        }
        try {
            $st = $database->stack();
            $st->setInsertSet(true)->fetchTuple('invalid query.');
        }
        catch (\Exception $ex) {
            $this->assertFalse($database->getInsertSet());
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_parseYaml($database)
    {
        // パース可能
        $this->assertEquals(['a', 'b', 'c'], $database->parseYaml('[a, b, c]', false));
        $this->assertEquals((object) ['a' => 'A'], $database->parseYaml('{a: A}', false));
        $this->assertEquals(['a' => 1, 'b' => 2], $database->parseYaml('[a: 1, b: 2]', false));
        // 不正なシンタックス
        $this->assertException('syntax error', L($database)->parseYaml('[a,b,c', false));
    }

    function test_setLogger()
    {
        $configuration = new Configuration();
        $configuration->setMiddlewares([new Middleware(new LoggerChain())]);
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:'], $configuration);
        $configuration = new Configuration();
        $configuration->setMiddlewares([new Middleware(new LoggerChain())]);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:'], $configuration);

        $master->executeStatement('CREATE TABLE test(id integer)');
        $slave->executeStatement('CREATE TABLE test(id integer)');

        $database = new Database([$master, $slave]);
        $database->selectArray('test', ['id' => 1]); // スキーマ漁りの暖機運転

        // master/slave 同時設定
        $logs = [];
        $database->setLogger(new Logger([
            'destination' => function ($log) use (&$logs) { $logs[] = $log; },
            'metadata'    => [],
        ]));

        $database->begin();
        $database->insert('test', ['id' => 1]);
        $database->selectArray('test', ['id' => 1]);
        $database->rollback();

        $this->assertEquals([
            'BEGIN',
            'INSERT INTO test (id) VALUES (1)',
            'SELECT test.* FROM test WHERE id = 1',
            'ROLLBACK',
        ], $logs);

        // master/slave 個別設定
        $masterlogs = [];
        $slavelogs = [];
        $database->setLogger([
            new Logger([
                'destination' => function ($log) use (&$masterlogs) { $masterlogs[] = $log; },
                'metadata'    => [],
            ]),
            new Logger([
                'destination' => function ($log) use (&$slavelogs) { $slavelogs[] = $log; },
                'metadata'    => [],
            ]),
        ]);

        $database->begin();
        $database->insert('test', ['id' => 1]);
        $database->selectArray('test', ['id' => 1]);
        $database->rollback();

        $this->assertEquals([
            'BEGIN',
            'INSERT INTO test (id) VALUES (1)',
            'ROLLBACK',
        ], $masterlogs);

        $this->assertEquals([
            'SELECT test.* FROM test WHERE id = 1',
        ], $slavelogs);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_autoCastType($database)
    {
        $database->setAutoCastType([
            'hoge'    => true,
            'integer' => true,
            'float'   => [
                'select' => true,
                'affect' => false,
            ],
            'striing' => false,
            'text'    => [
                'select' => false,
                'affect' => false,
            ],
        ]);
        $this->assertEquals([
            'integer' => [
                'select' => true,
                'affect' => true,
            ],
            'float'   => [
                'select' => true,
                'affect' => false,
            ],
        ], $database->getAutoCastType());

        $this->assertException('must contain', L($database)->setAutoCastType(['integer' => ['hoge']]));

        $database->setAutoCastType([]);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_declareVirtualTable($database)
    {
        $database->declareVirtualTable('virtual_table', [
            'foreign_p P' => [
                '*',
                '+foreign_c1 C1' => [
                    '*',
                ],
                'count_c2'       => $database->subcount('foreign_c2'),
            ],
        ], [
            'P.id >= ?' => 1,
        ]);

        $count = $database->getPlatform()->quoteIdentifier('*@count');
        $select = $database->select('virtual_table', [
            'C1.seq <> ?' => 2,
        ]);
        $this->assertStringIgnoreBreak("SELECT P.*,
(SELECT COUNT(*) AS $count FROM foreign_c2 WHERE foreign_c2.cid = P.id) AS count_c2,
C1.*
FROM foreign_p P
INNER JOIN foreign_c1 C1 ON C1.id = P.id
WHERE (P.id >= ?) AND (C1.seq <> ?)
", (string) $select);
        $this->assertEquals([1, 2], $select->getParams());

        $this->assertNull($database->getVirtualTable('not-found'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_declareCommonTable($database)
    {
        // 対応していない？（マニュアル的には対応してそうだけど…）
        if ($database->getPlatform() instanceof SQLServerPlatform) {
            return;
        }

        $database->refresh();

        $database->declareCommonTable([
            'fibonacci(n0, n)' => 'select 0, 1 union all select n, n0 + n from fibonacci WHERE n < 50',
            'querybuilder'     => fn() => $database->selectAvg('aggregate.group_id2', [], 'group_id1'),
            'arrays'           => [
                'column'  => ['aggregate' => ['group_id1', 'MAX(group_id2)']],
                'groupBy' => 'group_id1',
            ],
        ]);

        $this->assertEquals([1, 1, 2, 3, 5, 8, 13, 21, 34, 55], $database->selectLists('fibonacci.n'));
        $this->assertEquals([
            1 => 10.0,
            2 => 10.0,
            3 => 15.0,
            4 => 20.0,
            5 => 20.0,
        ], $database->selectPairs('querybuilder'));
        $this->assertEquals([
            1 => 10.0,
            2 => 10.0,
            3 => 20.0,
            4 => 20.0,
            5 => 20.0,
        ], $database->selectPairs('arrays'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_overrideColumns($database)
    {
        $database->overrideColumns([
            'test1'     => [
                'lower_name' => [
                    'type'   => 'string',
                    'select' => 'LOWER(%s.name1)',
                ],
            ],
            'test2'     => [
                'lower_name' => 'LOWER(%s.name2)',
                'is_a'       => [
                    'select' => ['name2' => 'A'],
                ],
            ],
            'foreign_p' => [
                'gw'   => $database->foreign_c1,
                'qb1'  => $database->selectSum('foreign_c1'),
                'qb2'  => $database->subexists('foreign_c1'),
                'qb3'  => $database->subquery('foreign_c1'),
                'expr' => $database->raw('now'),
                'r0'   => function (): void { },
                'r1'   => function (): bool { },
                'r2'   => function (): int { },
                'r3'   => function (): string { },
                'r4'   => function (): \ArrayObject { },
                'r5'   => function (): \DateTime { },
            ],
        ]);
        $this->assertEquals('a', $database->selectValue('test1.lower_name', [], ['id'], 1));
        $this->assertEquals('a', $database->selectValue('test2.lower_name', [], ['id'], 1));
        if (!$database->getPlatform() instanceof SQLServerPlatform) {
            $this->assertTrue(!!$database->selectValue('test2.is_a', [], ['id'], 1));
            $this->assertFalse(!!$database->selectValue('test2.is_a', [], ['id' => false], 1));
        }

        $columns = $database->getSchema()->getTableColumns('foreign_p');
        $this->assertEquals('array', $columns['gw']->getType()->getName());
        $this->assertEquals('integer', $columns['qb1']->getType()->getName());
        $this->assertEquals('boolean', $columns['qb2']->getType()->getName());
        $this->assertEquals('array', $columns['qb3']->getType()->getName());
        $this->assertEquals('string', $columns['expr']->getType()->getName());
        $this->assertEquals('integer', $columns['r0']->getType()->getName());
        $this->assertEquals('boolean', $columns['r1']->getType()->getName());
        $this->assertEquals('integer', $columns['r2']->getType()->getName());
        $this->assertEquals('string', $columns['r3']->getType()->getName());
        $this->assertEquals('object', $columns['r4']->getType()->getName());
        $this->assertEquals('datetime', $columns['r5']->getType()->getName());

        $database->getSchema()->refresh();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_addRelation($database)
    {
        $fkeys = $database->addRelation([
            'test1' => [
                'test' => [
                    'auto_fk1' => [
                        'id',
                    ],
                ],
            ],
            'test2' => [
                'test' => [
                    [
                        'id',
                    ],
                ],
            ],
        ]);
        $this->assertEquals(['auto_fk1', 'test2_test_0'], $fkeys);

        $this->assertEquals('SELECT test.*, test1.* FROM test INNER JOIN test1 ON test1.id = test.id', (string) $database->select('test+test1'));
        $this->assertEquals('SELECT test.*, test2.* FROM test INNER JOIN test2 ON test2.id = test.id', (string) $database->select('test+test2'));

        $fkeys = $database->addRelation([
            't_comment' => [
                't_article' => [
                    [
                        'comment_id' => 'article_id',
                    ],
                ],
            ],
        ]);
        $this->assertEquals(['t_comment_t_article_0'], $fkeys);
        $this->assertEquals([
            'fk_articlecomment',
            't_comment_t_article_0',
        ], array_keys($database->getSchema()->getTableForeignKeys('t_comment')));

        // 後処理
        $database->getSchema()->refresh();

        $fkeys = $database->getSchema()->getTableForeignKeys('t_comment');
        $this->assertEquals(['fk_articlecomment'], array_keys($fkeys));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_foreignKey($database)
    {
        // まず foreign_p<->foreign_c1 のリレーションがあることを担保して・・・
        $this->assertEquals('SELECT P.*, C.* FROM foreign_p P INNER JOIN foreign_c1 C ON C.id = P.id', (string) $database->select('foreign_p P + foreign_c1 C'));
        // 外部キーを削除すると・・・
        $fkey = $database->ignoreForeignKey('foreign_c1', 'foreign_p', 'id');
        // リレーションが消えるはず
        $this->assertStringContainsString('ON 1', (string ) $database->select('foreign_p P + foreign_c1 C'));
        // 戻り値は外部キーオブジェクトのはず
        $this->assertInstanceOf(Schema\ForeignKeyConstraint::class, $fkey);

        // 外部キーがないなら例外が投げられるはず
        $this->assertException('foreign key is not found', L($database)->ignoreForeignKey('foreign_c1', 'foreign_p', 'id'));

        // まず test1<->test2 のリレーションがないことを担保して・・・
        $this->assertStringContainsString('ON 1', (string ) $database->select('foreign_p P + foreign_c1 C'));
        // 仮想キーを追加すると・・・
        $database->addForeignKey('foreign_c1', 'foreign_p', 'id');
        // リレーションが発生するはず
        $this->assertEquals('SELECT P.*, C.* FROM foreign_p P INNER JOIN foreign_c1 C ON C.id = P.id', (string) $database->select('foreign_p P + foreign_c1 C'));

        // 後処理
        $database->getSchema()->refresh();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_view($database)
    {
        if (!$database->getPlatform() instanceof SQLServerPlatform) {
            // まず v_blog<->t_article のリレーションがないことを担保して・・・
            $this->assertEquals('SELECT A.*, B.* FROM t_article A, v_blog B', (string) $database->select('t_article A,v_blog B'));
            // 仮想キーを追加すると・・・
            $database->addForeignKey('v_blog', 't_article', 'article_id');
            // リレーションが発生するはず
            $this->assertEquals('SELECT A.*, B.* FROM t_article A LEFT JOIN v_blog B ON B.article_id = A.article_id', (string) $database->select('t_article A < v_blog B'));

            // 後処理
            $database->getSchema()->refresh();
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getEntityClass($database)
    {
        // 存在するならそれのはず
        $this->assertEquals(Article::class, $database->getEntityClass('t_article'));
        // 存在しないならEntityのはず
        $this->assertEquals(Entity::class, $database->getEntityClass('test'));

        // 複数を投げると先に見つかった方を返す
        $this->assertEquals(Article::class, $database->getEntityClass(['t_article', 't_comment']));
        $this->assertEquals(Comment::class, $database->getEntityClass(['t_comment', 't_article']));
        $this->assertEquals(Entity::class, $database->getEntityClass(['t_not1', 't_not2']));

        // 直クラス名でも引ける(自動エイリアス機能で t_article は Article と読み替えられるのでどちらでも引けるようにしてある)
        $this->assertEquals(Article::class, $database->getEntityClass('Article'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_convertName($database)
    {
        // 存在するならそれのはず
        $this->assertEquals('Article', $database->convertEntityName('t_article'));
        // 存在しないならそのままのはず
        $this->assertEquals('test', $database->convertEntityName('test'));

        // 存在するならそれのはず
        $this->assertEquals('t_article', $database->convertTableName('Article'));
        // 存在しないならそのままのはず
        $this->assertEquals('test', $database->convertEntityName('test'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_entity_mapper($database)
    {
        /** @var CacheInterface $cacher */
        $cacher = $database->getOption('cacheProvider');

        // 前処理
        $cacher->delete('Database-tableMap');
        $backup = $database->getOption('tableMapper');
        $tableMap = self::forcedCallize($database, '_tableMap');

        // 同じエンティティ名を返すような実装だと例外が飛ぶはず
        $database->setOption('tableMapper', function ($tablename) {
            if ($tablename === 'test') {
                return null;
            }
            return 'hoge';
        });
        $this->assertException('is already defined', $tableMap);

        // テーブル名とエンティティが一致しても例外が飛ぶはず
        $database->setOption('tableMapper', function ($tablename) {
            return $tablename . '1';
        });
        $this->assertException('already defined', $tableMap);

        // null を返せば除外される
        $database->setOption('tableMapper', function ($tablename) {
            if ($tablename === 'test') {
                return 'TestClass';
            }
            return null;
        });
        $this->assertEquals([
            'entityClass'  => [
                'TestClass' => null,
            ],
            'gatewayClass' => [
                'test'      => null,
                'TestClass' => null,
            ],
            'EtoT'         => [
                'TestClass' => 'test',
            ],
            'TtoE'         => [
                'test' => ['TestClass'],
            ],
        ], $tableMap());

        // 後処理
        $cacher->delete('Database-tableMap');
        $database->setOption('tableMapper', $backup);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_prepare($database)
    {
        // fetchXXX 系は stmt を受け付けてくれるはず
        $hogefuga = $database->getCompatiblePlatform()->getConcatExpression('?', ':fuga');
        $stmt = $database->prepare("select $hogefuga as hogefuga", ['hoge']);
        $this->assertEquals([
            'hogefuga' => 'hogefuga',
        ], $database->fetchTuple($stmt, ['fuga' => 'fuga']));

        // 様々なメソッドで fetch できるはず
        $select = $database->select('test.name', 'id = :id')->prepare();
        $this->assertEquals('a', $select->value(['id' => 1]));
        $this->assertEquals(['b'], $select->lists(['id' => 2]));
        $this->assertEquals(['name' => 'c'], $select->tuple(['id' => 3]));

        //@todo emulation off な mysql で本当に prepare されているかテストする？
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_preparing($database)
    {
        // select
        $stmt = $database->prepareSelect('test', ['id' => $database->raw(':id')]);
        $this->assertEquals($stmt->executeSelect(['id' => 1])->fetchAllAssociative(), $database->fetchArray($stmt, ['id' => 1]));
        $this->assertEquals($stmt->executeSelect(['id' => 2])->fetchAllAssociative(), $database->fetchArray($stmt, ['id' => 2]));

        // select in subquery
        $stmt = $database->prepareSelect([
            'foreign_p' => [
                $database->submax('foreign_c1.id'),
                $database->subcount('foreign_c2'),
            ],
        ], [
            'id = :id',
            $database->subexists('foreign_c1'),
            $database->notSubexists('foreign_c2'),
        ]);
        $this->assertEquals($stmt->executeSelect(['id' => 1])->fetchAllAssociative(), $database->fetchArray($stmt, ['id' => 1]));
        $this->assertEquals($stmt->executeSelect(['id' => 2])->fetchAllAssociative(), $database->fetchArray($stmt, ['id' => 2]));

        // insert
        $stmt = $database->prepareInsert('test', ['id' => $database->raw(':id'), ':name']);
        if (!$database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $database->getConnection()->executeStatement($database->getCompatiblePlatform()->getIdentityInsertSQL('test', true));
        }
        $stmt->executeAffect(['id' => 101, 'name' => 'XXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'YYY']);
        if (!$database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $database->getConnection()->executeStatement($database->getCompatiblePlatform()->getIdentityInsertSQL('test', false));
        }
        $this->assertEquals(['XXX', 'YYY'], $database->selectLists('test.name', ['id' => [101, 102]]));

        // update
        $stmt = $database->prepareUpdate('test', [':name'], ['id = :id']);
        $stmt->executeAffect(['id' => 101, 'name' => 'updateXXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'updateYYY']);
        $this->assertEquals(['updateXXX', 'updateYYY'], $database->selectLists('test.name', ['id' => [101, 102]]));

        // :hoge, :fuga の簡易記法
        $stmt = $database->prepareUpdate('test', [':name'], [':id']);
        $stmt->executeAffect(['id' => 101, 'name' => 'bindXXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'bindYYY']);
        $this->assertEquals(['bindXXX', 'bindYYY'], $database->selectLists('test.name', ['id' => [101, 102]]));

        // delete
        $stmt = $database->prepareDelete('test', ['id = :id']);
        $stmt->executeAffect(['id' => 101]);
        $stmt->executeAffect(['id' => 102]);
        $this->assertEquals([], $database->selectLists('test.name', ['id' => [101, 102]]));

        if ($database->getCompatiblePlatform()->supportsReplace()) {
            // replace
            $stmt = $database->prepareReplace('test', [':id', ':name', ':data']);
            $stmt->executeAffect(['id' => 101, 'name' => 'replaceXXX', 'data' => '']);
            $stmt->executeAffect(['id' => 102, 'name' => 'replaceXXX', 'data' => '']);
            $this->assertEquals(['replaceXXX', 'replaceXXX'], $database->selectLists('test.name', ['id' => [101, 102]]));
        }

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            // modify
            $stmt = $database->prepareModify('test', [':id', ':name', ':data']);
            $stmt->executeAffect(['id' => 101, 'name' => 'modifyXXX', 'data' => '']);
            $stmt->executeAffect(['id' => 102, 'name' => 'modifyYYY', 'data' => '']);
            $stmt->executeAffect(['id' => 103, 'name' => 'modifyZZZ', 'data' => '']);
            $this->assertEquals(['modifyXXX', 'modifyYYY', 'modifyZZZ'], $database->selectLists('test.name', ['id' => [101, 102, 103]]));
        }

        // 例外発生時は元に戻るはず
        $database->setOption('preparing', 0);
        $this->assertException(Schema\SchemaException::tableDoesNotExist('notfound'), L($database)->prepareInsert('notfound', []));
        $this->assertSame(0, $database->getOption('preparing'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_dryrun($database)
    {
        // クエリ文字列を返す
        $this->assertEquals("DELETE FROM test WHERE id = '1'", $database->dryrun()->delete('test', ['id' => 1]));

        // Context で実装されているのでこの段階では普通に実行される
        $this->assertEquals(1, $database->delete('test', ['id' => 1]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_filterNullAtNotNullColumn($database)
    {
        // false だとまっとうな DBMS なら怒られる
        $database = $database->context(['filterNullAtNotNullColumn' => false]);
        $this->assertException('null', L($database)->insert('notnulls', [
            'name'     => null,
            'cint'     => null,
            'cfloat'   => null,
            'cdecimal' => null,
        ]));

        // true なら not null な列に null を設定しても怒られない
        $database = $database->context(['filterNullAtNotNullColumn' => true]);
        $pk = $database->create('notnulls', [
            'id'       => 1,
            'name'     => null,
            'cint'     => null,
            'cfloat'   => null,
            'cdecimal' => null,
        ]);
        $this->assertEquals([
            'id'       => 1,
            'name'     => '',
            'cint'     => 1,
            'cfloat'   => 2.3,
            'cdecimal' => 4.56,
        ], $database->selectTuple('notnulls', $pk));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_convertEmptyToNull($database)
    {
        $database->setConvertEmptyToNull(true);
        $this->assertEquals(1, $database->insert('auto', [
            'id'   => null,
            'name' => 'hoge',
        ]));
        $this->assertEquals(1, $database->insert('auto', [
            'id'   => '',
            'name' => 'hoge',
        ]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_convertBoolToInt($database)
    {
        // @todo 良くわからないエラーが出る
        if ($database->getCompatiblePlatform()->getWrappedPlatform() instanceof SQLServerPlatform) {
            return;
        }
        $database->setConvertBoolToInt(true);
        $database->insert('misctype', [
            'id'        => 9,
            'pid'       => true,
            'cint'      => false,
            'cfloat'    => true,
            'cdecimal'  => false,
            'cstring'   => true,
            'ctext'     => false,
            'cbinary'   => true,
            'cblob'     => false,
            'carray'    => true,
            'cjson'     => false,
            'cdate'     => '2012-12-12',
            'cdatetime' => '2012-12-12 12:34:56',
        ]);
        $expected = [
            'id'        => 9,
            'pid'       => 1,
            'cint'      => 0,
            'cfloat'    => 1,
            'cdecimal'  => 0,
            'cstring'   => 1,
            'ctext'     => 0,
            'carray'    => 1,
            'cjson'     => 0,
            'cdate'     => '2012-12-12',
            'cdatetime' => '2012-12-12 12:34:56',
        ];
        $this->assertEquals($expected, array_intersect_key($expected, $database->selectTuple('misctype', ['id' => 9])));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_checkSameColumn($database)
    {
        $database->setAutoCastType(['guid' => true]);
        $database->setCheckSameColumn('noallow');
        $this->assertException('cause noallow', L($database)->selectArray('test.*, test.id'));
        $database->setAutoCastType(['guid' => true]);
        $this->assertException('cause noallow', L($database)->fetchArray('select id as "A.id", id as "B.id" from test'));
        $database->setAutoCastType([]);

        $database->setCheckSameColumn('strict');
        $this->assertArrayHasKey('id', $database->selectTuple('test.*, test.id', [], [], 1));
        $this->assertException('cause strict', L($database)->selectAssoc([
            'test',
            '' => [
                'NULL as a',
                "'' as a",
            ],
        ]));
        $database->setAutoCastType(['guid' => true]);
        $this->assertEquals(['id' => '1'], $database->fetchTuple('select 1 as "A.id", 1 as "B.id" from test where id = 1'));
        $this->assertException('cause strict', L($database)->fetchArray('select 1 as "A.id", 2 as "B.id" from test'));
        $database->setAutoCastType([]);

        $database->setCheckSameColumn('loose');
        $this->assertArrayHasKey('id', $database->selectTuple('test.*, test.id', [], [], 1));
        $this->assertArrayHasKey('a', $database->selectTuple([
            'test',
            '' => [
                'NULL as a',
                'NULL as a',
                "'' as a",
            ],
        ], [], [], 1));
        $this->assertException('cause loose', L($database)->selectAssoc([
            'test',
            '' => [
                'NULL as a',
                '0 as a',
                '1 as a',
            ],
        ]));
        $database->setAutoCastType(['guid' => true]);
        $this->assertEquals(['id' => '1'], $database->fetchTuple('select NULL as "A.id", 1 as "B.id", 1 as "C.id" from test where id = 1'));
        $this->assertException('cause loose', L($database)->fetchArray('select NULL as "A.id", 0 as "B.id", 1 as "C.id" from test'));
        $database->setAutoCastType([]);

        // 子供にも効くはず
        $this->assertException('is same column or alias', (L($database)->selectTuple([
            'test1' => [
                '*',
                'test2{id}' => $database->subselectArray([
                    'test2' => [
                        '*',
                    ],
                    ''      => [
                        'NULL as a',
                        '0 as a',
                        '1 as a',
                    ],
                ]),
            ],
        ], ['id' => 1])));

        $database->setCheckSameColumn('hoge');
        $this->assertException(new \DomainException('invalid'), L($database)->selectAssoc('test.*, test.id'));

        $database->setCheckSameColumn(null);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_injectCallStack($database)
    {
        $logs = [];
        $logger = new Logger([
            'destination' => function ($sql, $params) use (&$logs) {
                $logs[] = compact('sql', 'params');
            },
            'metadata'    => [],
        ]);
        $database->setLogger($logger);

        $database->setInjectCallStack('DatabaseTest.php');
        $database->executeSelect('select * from test');
        $this->assertStringContainsString(__FILE__, $logs[0]['sql']);

        $database->setInjectCallStack('!vendor');
        $database->executeSelect('select * from test');
        $this->assertStringContainsString('Database.php#', $logs[1]['sql']);
        $this->assertStringNotContainsString('phpunit', $logs[1]['sql']);

        $database->setInjectCallStack(['DatabaseTest.php', '!phpunit']);
        $database->executeSelect('select * from test');
        $this->assertStringContainsString(__FILE__, $logs[2]['sql']);
        $this->assertStringNotContainsString('phpunit', $logs[2]['sql']);

        $database->setInjectCallStack('DatabaseTest.php');
        $database->executeAffect("update test set name='hoge'");
        $this->assertStringContainsString(__FILE__, $logs[3]['sql']);

        $database->setInjectCallStack(function ($path) { return preg_match('/phpunit$/', $path); });
        $database->executeAffect("update test set name='hoge'");
        $this->assertStringContainsString('phpunit#', $logs[4]['sql']);

        $database->setInjectCallStack(null);
        $database->setLogger(null);
    }

    function test_tx_method()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $database = new Database([$master, $slave]);

        // 初期値はマスターのはず
        $this->assertSame($master, $database->getConnection());

        // トランザクションが走ってないなら切り替えられるはず
        $this->assertSame($slave, $database->setConnection($slave)->getConnection());

        // bool でも切り替えられるはず
        $this->assertSame($master, $database->setConnection(true)->getConnection());

        // begin ～ rollback で値が増減するはず
        $this->assertEquals(1, $database->begin());
        $this->assertEquals(2, $database->begin());
        $this->assertEquals(1, $database->rollback());
        $this->assertEquals(2, $database->begin());
        $this->assertEquals(1, $database->rollback());
        $this->assertEquals(0, $database->rollback());

        // begin ～ comit で値が増減するはず
        $this->assertEquals(1, $database->begin());
        $this->assertEquals(2, $database->begin());
        $this->assertEquals(1, $database->commit());
        $this->assertEquals(2, $database->begin());
        $this->assertEquals(1, $database->commit());
        $this->assertEquals(0, $database->commit());

        // 一度 begin すると・・・
        $database->begin();
        // 変更のない切り替えはOKだが
        $database->setConnection($master);
        // 変更のある切り替えはNGのはず
        $this->assertException("can't switch connection", L($database)->setConnection($slave));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_transaction($database)
    {
        $tx = $database->transaction();
        $this->assertInstanceOf(get_class(new Transaction($database)), $tx);

        $current = $database->count('test');
        $return = $database->transact(function (Database $db) {
            $db->delete('test', ["'1'" => '1']);
            return 'success';
        });
        $this->assertEquals('success', $return);
        $this->assertNotEquals($current, $database->count('test'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_transact_commit($database)
    {
        $current = $database->count('test');

        $database->transact(function (Database $db) {
            $db->delete('test', ["'1'" => '1']);
        });
        // コミットされているはず
        $this->assertNotEquals($current, $database->count('test'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_transact_rollback($database)
    {
        $current = $database->count('test');

        try {
            $database->transact(function (Database $db) {
                $db->getMasterConnection()->delete('test', [1 => 1]);
                throw new \Exception();
            });
        }
        catch (\Exception $ex) {
            // ロールバックされているはず
            $this->assertEquals($current, $database->count('test'));
            return;
        }

        $this->fail();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_transact_catch($database)
    {
        $current = $database->count('test');

        $database->transact(function (Database $db) {
            $db->getMasterConnection()->delete('test', [1 => 1]);
            throw new \Exception();
        }, function () use ($database, $current) {
            // ロールバックされているはず
            $this->assertEquals($current, $database->count('test'));
        }, [], false);
        // ↑の catch イベントが呼ばれていることを担保
        $this->assertEquals(1, \PHPUnit\Framework\Assert::getCount());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_preview($database)
    {
        $logs = $database->preview(function (Database $db) {
            $db->delete('test');
            $db->insert('test', ['id' => '1']);
        });
        $this->assertContains("DELETE FROM test", $logs);
        $this->assertContains("INSERT INTO test (id) VALUES (1)", $logs);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_raw($database)
    {
        $raw = $database->raw('NOW()', [1, 2, 3]);
        $this->assertTrue($raw instanceof Expression);
        $this->assertEquals('NOW()', $raw->merge($params));
        $this->assertEquals([1, 2, 3], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_operator($database)
    {
        $assert = function ($expectedQuery, $expectedParams, Expression $actual, $trim = false) {
            $actualQuery = $actual->getQuery();
            $actualParams = $actual->getParams();
            if ($trim) {
                $expectedQuery = preg_replace('#\s#', '', $expectedQuery);
                $actualQuery = preg_replace('#\s#', '', $actualQuery);
            }
            $this->assertEquals($expectedQuery, $actualQuery);
            $this->assertEquals($expectedParams, $actualParams);
        };

        // 基本的には whereInto と同じだし、実装も真似ている
        // のでここでは代表的な演算子のみに留める

        // = になるはず
        $assert('(column_name = ?)', [1], $database->operator('column_name', 1));
        // IN になるはず
        $assert('(column_name IN (?,?,?))', [1, 2, 3], $database->operator('column_name', [1, 2, 3]));
        // LIKE演算子明示
        $assert('(column_name LIKE ?)', ['%hogera%'], $database->operator('column_name:%LIKE%', ['hogera']));
        // 区間演算子明示
        $assert('(column_name >= ? AND column_name <= ?)', [1, 99], $database->operator('column_name:[~]', [1, 99]));
        // 上記すべての複合
        $assert(
            '((column_nameE = ?) AND (column_nameI IN (?,?,?)) AND (column_name LIKE ?) AND (column_name >= ? AND column_name <= ?))',
            [1, 1, 2, 3, '%hogera%', 1, 99],
            $database->operator([
                'column_nameE'       => 1,
                'column_nameI'       => [1, 2, 3],
                'column_name:%LIKE%' => ['hogera'],
                'column_name:[~]'    => [1, 99],
            ])
        );
        // 上記すべての複合かつ複数引数（OR 結合される）
        $assert(
        // わかりづらすぎるので適宜改行を入れてある
            "
            (
              (
                (column_nameE = ?) AND
                (column_nameI IN (?,?,?)) AND
                (column_name LIKE ?) AND
                (column_name >= ? AND column_name <= ?)
              )
              OR
              (
                (column_name2E = ?) AND
                (column_name2I IN (?,?)) AND
                (
                  (or_column1 = ?) OR (or_column2 = ?)
                )
              )
            )",
            [1, 1, 2, 3, '%hogera%', 1, 99, 101, 102, 103, 'hoge', 'fuga'],
            $database->operator([
                'column_nameE'       => 1,
                'column_nameI'       => [1, 2, 3],
                'column_name:%LIKE%' => ['hogera'],
                'column_name:[~]'    => [1, 99],
            ], [
                'column_name2E' => 101,
                'column_name2I' => [102, 103],
                [
                    'or_column1' => 'hoge',
                    'or_column2' => 'fuga',
                ],
            ])
            , true);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_binder($database)
    {
        $binder = $database->binder();
        $this->assertEquals('select ? WHERE ? and (?, ?, ?)', "select {$binder(1)} WHERE {$binder(2)} and ({$binder([3, 4, 5])})");
        $this->assertEquals([1, 2, 3, 4, 5], (array) $binder);

        $binder = $database->binder();
        $select = $database->select([
            'f' => new Expression('F(?)', 'arg'),
        ], [
            'id'   => [7, 8, 9],
            'name' => 'hoge',
        ]);
        $this->assertEquals(
            'select ? WHERE (SELECT F(?) AS f WHERE (id IN (?,?,?)) AND (name = ?)) = ? and (?, ?, ?)',
            "select {$binder(1)} WHERE {$binder($select)} = {$binder(99)} and ({$binder([3, 4, 5])})");
        $this->assertEquals([1, 'arg', 7, 8, 9, 'hoge', 99, 3, 4, 5], (array) $binder);

        $binder = $database->binder();
        $this->assertEquals(
            'select ?,?',
            "select {$binder(null)},{$binder([])}");
        $this->assertEquals([null, null], (array) $binder);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_quoteIdentifier($database)
    {
        // カバレッジ以上の意味はない
        $this->assertEquals($database->getPlatform()->quoteIdentifier('hogera'), $database->quoteIdentifier('hogera'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_quote($database)
    {
        $this->assertEquals("NULL", $database->quote(null, null));
        $this->assertEquals("0", $database->quote(false, null));
        $this->assertEquals("1", $database->quote(true, null));
        $this->assertEquals("'1'", $database->quote(1, null));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_queryInto($database)
    {
        $this->assertEquals("'1''2'", $database->queryInto('??', [1, 2]));
        $this->assertEquals("'1''2'", $database->queryInto(':hoge:fuga', ['hoge' => 1, 'fuga' => 2]));
        $this->assertEquals("hoge", $database->queryInto('hoge'));
        $this->assertEquals("'1''2'", $database->queryInto('?:hoge', [1, 'hoge' => 2]));
        $this->assertEquals("'2''1''3'", $database->queryInto(':hoge?:fuga', [1, 'fuga' => 3, 'hoge' => 2]));
        $this->assertEquals("'1','2','3'", $database->queryInto(new Expression('?,?,?', [1, 2, 3])));

        // 他方が包含したり同名だったりすると予期せぬ動作になることがあるのでテスト
        $this->assertEquals("'1', '2'", $database->queryInto(':hogehoge, :hoge', ['hoge' => 2, 'hogehoge' => 1]));
        $this->assertEquals("'2', '2'", $database->queryInto(':hoge, :hoge', ['hoge' => 2]));

        // バックスラッシュの脆弱性があったのでテスト
        $injected = "\\\' EvilString -- ";
        $quoted = $database->quote($injected);
        $query1 = $database->queryInto('select ?', [$injected]);
        $query2 = $database->queryInto('select :hoge', ['hoge' => $injected]);
        $this->assertEquals("select $quoted", $query1);
        $this->assertEquals("select $quoted", $query2);
        // 視認しづらいので、実際に投げてエラーにならないことを担保する
        $this->assertStringContainsString('EvilString', $database->fetchValue($query1));
        $this->assertStringContainsString('EvilString', $database->fetchValue($query2));

        // Queryable とパラメータを投げることは出来ない（足りない分を補填する形ならOKだが、大抵の場合は誤り）
        $this->assertException("long", L($database)->queryInto(new Expression('?,?,?', [1, 2, 3]), [1, 2, 3]));

        // プレースホルダを含む脆弱性があったのでテスト
        $this->assertException('short', L($database)->queryInto('select ?', []));
        $this->assertException('long', L($database)->queryInto('select ?', [1, 2]));

        // 不一致だと予期せぬ動作になることがあるのでテスト
        $this->assertException('short', L($database)->queryInto(':hoge', ['fuga' => 1]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_bindInto($database)
    {
        $params = [];
        $bind = $database->bindInto(['colA' => 1, 'colB' => 2], $params);
        $this->assertEquals(['colA' => '?', 'colB' => '?'], $bind);
        $this->assertEquals([1, 2], $params);

        $params = [];
        $bind = $database->bindInto(['colA' => 1, 'colB' => new Expression('FUNC(99)')], $params);
        $this->assertEquals(['colA' => '?', 'colB' => 'FUNC(99)'], $bind);
        $this->assertEquals([1], $params);

        $params = [];
        $bind = $database->bindInto(['colA' => 1, 'colB' => new Expression('FUNC(?)', [99])], $params);
        $this->assertEquals(['colA' => '?', 'colB' => 'FUNC(?)'], $bind);
        $this->assertEquals([1, 99], $params);

        $params = [];
        $subquery = $database->select('test', ['id' => 1]);
        $bind = $database->bindInto(['colA' => new Expression('FUNC(?)', [99]), 'colB' => $subquery], $params);
        $this->assertEquals(['colA' => 'FUNC(?)', 'colB' => "($subquery)"], $bind);
        $this->assertEquals([99, 1], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto($database)
    {
        $params = [];
        $whereInto = function ($conds) use ($database, &$params) {
            $params = [];
            return $database->whereInto(is_array($conds) ? $conds : [$conds], $params);
        };

        $this->assertEquals([], $whereInto([]));
        $this->assertEquals([], $params);

        $this->assertEquals(['hoge IS NULL'], $whereInto(['hoge' => null]));
        $this->assertEquals([], $params);

        $this->assertEquals(['hoge IN (NULL)'], $whereInto(['hoge' => []]));
        $this->assertEquals([], $params);

        $this->assertEquals(['hoge = 1'], $whereInto(['hoge = 1']));
        $this->assertEquals([], $params);

        $this->assertEquals(['hoge = ?'], $whereInto(['hoge' => 1]));
        $this->assertEquals([1], $params);

        $this->assertEquals(['hoge = ?'], $whereInto(['hoge = ?' => 1]));
        $this->assertEquals([1], $params);

        $this->assertEquals(['hoge = ?'], $whereInto(['hoge = ?' => [1]]));
        $this->assertEquals([1], $params);

        $this->assertEquals(['hoge IN (?)'], $whereInto(['hoge' => [1]]));
        $this->assertEquals([1], $params);

        $this->assertEquals(['hoge IN (?,?)'], $whereInto(['hoge' => [1, 2]]));
        $this->assertEquals([1, 2], $params);

        $this->assertEquals(['hoge IN (?,?)'], $whereInto(['hoge IN (?)' => [1, 2]]));
        $this->assertEquals([1, 2], $params);

        $this->assertEquals(['hoge IN(?) OR fuga IN(?,?)'], $whereInto(['hoge IN(?) OR fuga IN(?)' => [[1], [2, 3]]]));
        $this->assertEquals([1, 2, 3], $params);

        $this->assertEquals(['hoge = ? OR fuga = ?'], $whereInto(['hoge = ? OR fuga = ?' => [1, 2]]));
        $this->assertEquals([1, 2], $params);

        $this->assertEquals(['hoge = ? OR fuga = ?'], $whereInto(['hoge = ? OR fuga = ?' => [[1], [2]]]));
        $this->assertEquals([1, 2], $params);

        $this->assertEquals(['hoge = ? OR fuga IN (?,?)'], $whereInto(['hoge = ? OR fuga IN (?)' => [1, [2, 3]]]));
        $this->assertEquals([1, 2, 3], $params);

        $this->assertEquals(['hoge = ? OR fuga IN (?,?)'], $whereInto(['hoge = ? OR fuga IN (?)' => [[1], [2, 3]]]));
        $this->assertEquals([1, 2, 3], $params);

        $this->assertEquals(['cond = ?', 'id = :id', 'condition'], $whereInto(['cond' => 1, ':id', 'condition']));
        $this->assertEquals([1], $params);

        $this->assertEquals(['hoge IN(?) OR fuga IN(?,?)'], $whereInto([
            'hoge IN(?) OR fuga IN(?)' => new \ArrayObject([
                new \ArrayObject([1]),
                new \ArrayObject([2, 3]),
            ]),
        ]));
        $this->assertEquals([1, 2, 3], $params);

        $this->assertEquals([
            'b1 = ?',
            'b2 IN (?)',
        ], $whereInto([
            // 含まれない
            '!a1' => null,
            '!a2' => [],
            // 含まれる
            '!b1' => 1,
            '!b2' => [1],
        ]));
        $this->assertEquals([1, 1], $params);

        $this->assertEquals([
            'id3 IN (?,?)',
            'id4 LIKE ? OR id4 LIKE ?',
            'NOT (id5 IN (?,?))',
            'NOT (id6 IN (?,?))',
        ], $whereInto([
            'id3:IN'       => ['x', 'y'],
            'id4:%LIKEIN%' => ['x', 'y'],
            'id5:!IN'      => ['x1', 'y1'],
            'id6:!'        => ['x2', 'y2'],
        ]));
        $this->assertEquals(['x', 'y', '%x%', '%y%', 'x1', 'y1', 'x2', 'y2'], $params);

        $this->assertEquals(['(scalar) OR (value)'], $whereInto([['scalar', 'value']]));

        $this->assertEquals(['C IN (NULL)'], $whereInto([[], 'C' => []]));
        $this->assertEquals([], $params);

        $this->assertEquals(['FUNC(99)'], $whereInto([new Expression('FUNC(99)')]));
        $this->assertEquals([], $params);

        $this->assertEquals(['FUNC(?)'], $whereInto([new Expression('FUNC(?)', [99])]));
        $this->assertEquals([99], $params);

        $this->assertEquals(['col IN (?,?)'], $whereInto(['col' => Operator::is(1, 2)]));
        $this->assertEquals([1, 2], $params);

        $this->assertEquals(['col IS NULL'], $whereInto(['col' => Operator::is(null)]));
        $this->assertEquals([], $params);

        $this->assertEquals(['(SELECT test.hoge FROM test)'], $whereInto([$database->select('test.hoge')]));
        $this->assertEquals([], $params);

        $this->assertEquals(['id = (SELECT test.id FROM test)'], $whereInto(['id = ?' => $database->select('test.id')]));
        $this->assertEquals([], $params);

        $this->assertEquals(['id IN((SELECT test.id FROM test))'], $whereInto(['id IN(?)' => $database->select('test.id')]));
        $this->assertEquals([], $params);

        $this->assertEquals(['id IN (SELECT test.id FROM test)'], $whereInto(['id' => $database->select('test.id')]));
        $this->assertEquals([], $params);

        $this->assertException(
            new \InvalidArgumentException('notfound search string'),
            L($database)->whereInto(['hoge = ?' => [[1, 2], 3]], $params)
        );

        $this->assertException(
            new \UnexpectedValueException('both specified'),
            L($database)->whereInto(['col:OP' => Operator::is(null)], $params)
        );
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_ignore($database)
    {
        $params = [];
        $filtered = [];
        $where = $database->whereInto([
            '!rid11'   => 11,
            '!rid12'   => 12,
            '!id11'    => null,
            '!id12'    => '',
            '!id13'    => [],
            [
                '!rid21' => 21,
                '!rid22' => 22,
                '!id21'  => null,
                '!id22'  => '',
                '!id23'  => [],
                [
                    '!rid31' => 31,
                    '!rid32' => 32,
                    '!id31'  => null,
                    '!id32'  => '',
                    '!id33'  => [],
                ],
            ],
            '!id9:!IN' => null,
            '!ids:[~]' => [null, null],
            '!str'     => Operator::likeIn(''), // 意図的
            '!query'   => $database->select('test'),
            '!exists'  => $database->select('test', [
                '!piyo:[~]' => [null, null],
            ]),
        ], $params, 'OR', $filtered);
        // '!' 付きで空値はシカトされている
        $this->assertEquals([
            'rid11 = ?',
            'rid12 = ?',
            '(rid21 = ?) OR (rid22 = ?) OR ((rid31 = ?) AND (rid32 = ?))',
            'str LIKE ?',
            'query IN (SELECT test.* FROM test)',
        ], $where);
        // '!' 付きで空値はバインドされない
        $this->assertEquals([11, 12, 21, 22, 31, 32, '%%'], $params);
        // フィルタ結果が格納される
        $this->assertEquals(false, $filtered);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_closure($database)
    {
        $params = [];
        $whereInto = function ($conds, $andor = 'OR') use ($database, &$params) {
            $params = [];
            return $database->whereInto(is_array($conds) ? $conds : [$conds], $params, $andor);
        };

        $wheres = $whereInto([
            // 数値キーで配列を返す
            function () { return ['A', 'B']; },
            // 数値キーで連想配列を返す
            function () { return ['a' => 'A', 'b' => 'B']; },
            // 数値キーで非配列を返す
            function () { return 'this is cond.'; },
            // 数値キーでクエリビルダを返す
            function (Database $db) { return $db->select('test1')->exists(); },
            // 数値キーで空値を返す
            function () { return null; },
            function () { return ''; },
            function () { return []; },
            // 文字キーで配列を返す
            'columnA'  => function () { return ['Y', 'Z']; },
            // 文字キーで連想配列を返す
            'columnH'  => function () { return ['y' => 'Y', 'z' => 'Z']; },
            // 文字キーで非配列を返す
            'columnC'  => function () { return 'this is cond.'; },
            // 文字キーで空値を返す
            'empty1'   => function () { return null; },
            'empty2'   => function () { return ''; },
            'empty3'   => function () { return []; },
            // !文字キーで空値を返す
            '!iempty1' => function () { return null; },
            '!iempty2' => function () { return ''; },
            '!iempty3' => function () { return []; },
            // 文字キーでクエリビルダを返す
            'subquery' => function (Database $db) { return $db->select('test2'); },
        ]);
        $this->assertEquals([
            '(A) OR (B)',
            '(a = ?) OR (b = ?)',
            'this is cond.',
            '(EXISTS (SELECT * FROM test1))',
            'columnA IN (?,?)',
            'columnH IN (?,?)',
            'columnC = ?',
            'empty1 IS NULL',
            'empty2 = ?',
            'empty3 IN (NULL)',
            'subquery IN (SELECT test2.* FROM test2)',
        ], $wheres);
        $this->assertEquals([
            'A',
            'B',
            'Y',
            'Z',
            'Y',
            'Z',
            'this is cond.',
            '',
        ], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_flipflop($database)
    {
        $whereInto = function ($conds, $andor = 'OR') use ($database, &$params) {
            $params = [];
            return $database->whereInto(is_array($conds) ? $conds : [$conds], $params, $andor);
        };

        $nesting = [
            'condA' => 1,
            [
                'condB1' => 21,
                'condB2' => 22,
            ],
            'condC' => 3,
            [
                'condD1' => 41,
                [
                    'condD21' => 421,
                    'condD22' => 422,
                ],
                'condD3' => 42,
            ],
            'AND'   => [
                'condE1' => 51,
                [
                    'condE21' => 521,
                    'condE22' => 522,
                ],
                'condE3' => 52,
            ],
        ];

        $this->assertEquals([
            'condA = ?',
            '(condB1 = ?) OR (condB2 = ?)',
            'condC = ?',
            '(condD1 = ?) OR ((condD21 = ?) AND (condD22 = ?)) OR (condD3 = ?)',
            '(condE1 = ?) AND ((condE21 = ?) OR (condE22 = ?)) AND (condE3 = ?)',
        ], $whereInto($nesting, 'OR'));

        $this->assertEquals([
            'condA = ?',
            '(condB1 = ?) AND (condB2 = ?)',
            'condC = ?',
            '(condD1 = ?) AND ((condD21 = ?) OR (condD22 = ?)) AND (condD3 = ?)',
            '(condE1 = ?) AND ((condE21 = ?) OR (condE22 = ?)) AND (condE3 = ?)',
        ], $whereInto($nesting, 'AND'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_not($database)
    {

        $params = [];
        $where = $database->whereInto([
            'cond1' => 1,
            // NOT はコンテキストを変えないのでこれは AND
            'NOT'   => [
                'cond2' => 2,
                'cond3' => 3,
                // NOT はコンテキストを変えないのでこれは OR
                [
                    'cond4' => 4,
                    'cond5' => 5,
                ],
                // NOT はコンテキストを変えないのでこれは AND
                'NOT'   => [
                    'cond6' => 6,
                    // NOT はコンテキストを変えないのでこれは OR
                    [
                        'cond7' => 7,
                        'cond8' => 8,
                    ],
                ],
            ],
        ], $params);
        $this->assertEquals([
            'cond1 = ?',
            'NOT ((cond2 = ?) AND (cond3 = ?) AND ((cond4 = ?) OR (cond5 = ?)) AND (NOT ((cond6 = ?) AND ((cond7 = ?) OR (cond8 = ?)))))',
        ], $where);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_notallohashwhere($database)
    {
        $params = [];
        $where = $database->whereInto([
            'id' => ['evil1' => 'evil1'],
            [
                'opt1' => ['evil2' => 'evil2'],
                'opt2' => ['evil3', 'evil4'],
            ],
        ], $params);
        $this->assertEquals(['id IN (?)', '(opt1 IN (?)) OR (opt2 IN (?,?))'], $where);
        $this->assertEquals(['evil1', 'evil2', 'evil3', 'evil4'], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_rowconstructor($database)
    {
        $params = [];
        $where = $database->whereInto([
            '(mainid, subid)' => $database->select('multiprimary S.mainid,subid', ['name' => ['a', 'c']]),
        ], $params);
        $this->assertEquals(['(mainid, subid) IN (SELECT S.mainid, S.subid FROM multiprimary S WHERE name IN (?,?))'], $where);
        $this->assertEquals(['a', 'c'], $params);

        $params = [];
        $where = $database->whereInto([
            '(mainid, subid)' => [[1, 2], [3, 4]],
        ], $params);
        $this->assertEquals(['(mainid, subid) IN ((?,?),(?,?))'], $where);
        $this->assertEquals([1, 2, 3, 4], $params);

        // mysql は行値式を解すので実際に投げて確認する
        if ($database->getPlatform() instanceof MySQLPlatform) {
            $this->assertEquals([
                [
                    'mainid' => '1',
                    'subid'  => '1',
                    'name'   => 'a',
                ],
                [
                    'mainid' => '1',
                    'subid'  => '2',
                    'name'   => 'b',
                ],
            ], $database->selectArray('multiprimary M', [
                '(mainid, subid)' => [[1, 1], [1, 2]],
            ]));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_whereInto_queryable_array($database)
    {
        $params = [];
        $where = $database->whereInto([
            '? and ? and ? and ? and ?' => [
                null,
                $database->selectCount('test1', ['id' => 0, 'name1' => 'hoge']),
                $database->selectExists('test2', ['id' => 1, 'name2' => 'fuga']),
                'dummy',
                $database->raw('(select 1)'),
            ],
        ], $params);

        $count = $database->getPlatform()->quoteIdentifier('*@Count');
        $this->assertEquals([
            implode(" and ", [
                "?",
                "(SELECT COUNT(*) AS $count FROM test1 WHERE (id = ?) AND (name1 = ?))",
                "(EXISTS (SELECT * FROM test2 WHERE (id = ?) AND (name2 = ?)))",
                "?",
                "(select 1)",
            ]),
        ], $where);
        $this->assertEquals([null, 0, 'hoge', 1, 'fuga', 'dummy'], $params);
        $this->assertStringIgnoreBreak("NULL and
(SELECT COUNT(*) AS $count FROM test1 WHERE (id = '0') AND (name1 = 'hoge')) and
(EXISTS (SELECT * FROM test2 WHERE (id = '1') AND (name2 = 'fuga'))) and
'dummy' and
(select 1)", $database->queryInto($where[0], $params));

        $params = [];
        $where = $database->whereInto([
            '?' => [$database->selectExists('test1', ['id' => 1]), 0],
        ], $params);

        $this->assertEquals(['(EXISTS (SELECT * FROM test1 WHERE id = ?)) = ?'], $where);
        $this->assertEquals([1, 0], $params);
        $this->assertStringIgnoreBreak("(EXISTS (SELECT * FROM test1 WHERE id = '1')) = '0'", $database->queryInto($where[0], $params));

        $params = [];
        $where = $database->whereInto([
            '?' => [$database->selectExists('test1', ['id' => 1]), 2, 3],
        ], $params);

        $this->assertEquals(['(EXISTS (SELECT * FROM test1 WHERE id = ?)) IN (?,?)'], $where);
        $this->assertEquals([1, 2, 3], $params);
        $this->assertStringIgnoreBreak("(EXISTS (SELECT * FROM test1 WHERE id = '1')) IN ('2','3')", $database->queryInto($where[0], $params));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_syntax($database)
    {
        $cplatform = $database->getCompatiblePlatform();

        // CASE WHEN の条件なしとか ELSE 節とかを実際に投げてみて正当性を担保
        $syntax = $cplatform->getCaseWhenSyntax("'2'", [1 => 10, 2 => 20], 99);
        $this->assertEquals(20, $database->fetchValue("SELECT $syntax", $syntax->getParams()));
        $syntax = $cplatform->getCaseWhenSyntax("'9'", [1 => 10, 2 => 20], 99);
        $this->assertEquals(99, $database->fetchValue("SELECT $syntax", $syntax->getParams()));

        // GROUP_CONCAT は方言がバラバラなので実際に投げてみて正当性を担保
        if (!$cplatform->getWrappedPlatform() instanceof SQLServerPlatform) {
            $syntax = $cplatform->getGroupConcatSyntax('name', '|');
            $this->assertEquals('a|b|c|d|e|f|g|h|i|j', $database->fetchValue("SELECT $syntax FROM test"));
        }

        // SQLServer は LIKE に特殊性があるので実際に投げてみて正当性を担保
        $this->assertSame([], $database->selectArray('test', ['name:LIKE' => 'w%r_o[d',]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_export($database)
    {
        $path = sys_get_temp_dir() . '/export.tmp';

        $database->exportCsv('select * from test', [], [], $path);
        $this->assertStringEqualsFile($path, "1,a,\n2,b,\n3,c,\n4,d,\n5,e,\n6,f,\n7,g,\n8,h,\n9,i,\n10,j,\n");

        $database->exportJson($database->select('test'), [], [], $path);
        $this->assertJson(file_get_contents($path));

        $database->setCheckSameColumn('loose');
        $this->assertException('cause loose', L($database)->exportCsv('select test.id, 0 as id from test'));
        $database->setCheckSameColumn(null);

        $this->assertException(new \BadMethodCallException('undefined'), L($database)->exportHoge($database->select('test'), [], [], $path));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_gather($database)
    {
        $database->import([
            'g_ancestor' => [
                [
                    'ancestor_name' => 'A',
                    'g_parent'      => [
                        [
                            'parent_name' => 'AA',
                            'g_child'     => [
                                ['child_name' => 'AAA'],
                                ['child_name' => 'AAB'],
                            ],
                        ],
                        [
                            'parent_name' => 'AB',
                            'g_child'     => [
                                ['child_name' => 'ABA'],
                                ['child_name' => 'ABB'],
                            ],
                        ],
                    ],
                ],
                [
                    'ancestor_name' => 'B',
                    'g_parent'      => [
                        [
                            'parent_name' => 'BA',
                            'g_child'     => [
                                ['child_name' => 'BAA'],
                                ['child_name' => 'BAB'],
                            ],
                        ],
                        [
                            'parent_name' => 'BB',
                            'g_child'     => [
                                ['child_name' => 'BBA'],
                                ['child_name' => 'BBB'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals([
            'g_ancestor' => [
                1 => ['ancestor_id' => '1',],
            ],
            'g_parent'   => [
                1 => ['parent_id' => '1',],
                2 => ['parent_id' => '2',],
            ],
            'g_child'    => [
                3 => ['child_id' => '3',],
                4 => ['child_id' => '4',],
            ],
        ], $database->gather('g_ancestor', ['' => 1]));

        $this->assertEquals([
            'g_child'    => [
                3 => ['child_id' => '3',],
            ],
            'g_parent'   => [
                2 => ['parent_id' => '2',],
            ],
            'g_ancestor' => [
                1 => ['ancestor_id' => '1',],
            ],
        ], $database->gather('g_child', ['' => 3], [], true));

        $this->assertEquals([
            'g_ancestor' => [
                2 => ['ancestor_id' => '2',],
            ],
            'g_parent'   => [
                3 => ['parent_id' => '3',],
            ],
            'g_child'    => [
                5 => ['child_id' => '5',],
            ],
        ], $database->gather('g_ancestor', ['' => 2], [
            'g_parent' => [
                'parent_id' => 3,
            ],
            'g_child'  => [
                'child_id <= ?' => 5,
            ],
        ]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetch_string($database)
    {
        // 数値プレースホルダや名前付きプレースホルダが壊れていないことを担保
        $rows1 = $database->fetchArray('select * from test where id in(?, ?)', [1, 2]);
        $rows2 = $database->fetchArray('select * from test where id in(:id1, :id2)', ['id1' => 1, 'id2' => 2]);
        $this->assertEquals($rows1, $rows2);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetch_builder($database)
    {
        $select = $database->select('test', ['id' => 1]);
        $this->assertException('both $builder and fetch argument', L($database)->fetchTuple($select, [1]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetch_mode($database)
    {
        $select = $database->select('test')->limit(1)->cast(Entity::class);
        $this->assertInstanceOf(Entity::class, $select->tuple());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetch_entity($database)
    {
        // 明示的に指定されているときは伝播しない
        $row = $database->select([
            't_article.*' => [
                'children' => $database->subselectAssoc('t_comment ManagedComment'),
            ],
        ])->limit(1)->cast()->tuple();
        $this->assertInstanceOf(Article::class, $row);
        $this->assertContainsOnlyInstancesOf(ManagedComment::class, $row['children']);

        // 親子呼び出しと・・・
        $row1 = $database->select('t_article/t_comment')->limit(1)->cast()->tuple();

        // 怠惰呼び出しと・・・
        $row2 = $database->select('t_article.**')->limit(1)->cast()->tuple();

        // カラム呼び出しと・・・
        $row3 = $database->select([
            't_article.*' => [
                't_comment' => ['*'],
            ],
        ])->limit(1)->cast()->tuple();

        // 完全指定呼び出しが・・・
        $row4 = $database->select([
            't_article.*' => [
                'Comment' => $database->subselectAssoc('t_comment'),
            ],
        ])->limit(1)->cast()->tuple();

        // 全て一致するはず
        $this->assertEquals($row1, $row2);
        $this->assertEquals($row2, $row3);
        $this->assertEquals($row3, $row4);
        $this->assertEquals($row4, $row1);

        // エイリアスを指定すればそれが優先されるはず
        $row2 = $database->selectTuple([
            't_article.*' => [
                't_comment AS hogera' => ['*'],
            ],
        ], [], [], 1);
        $this->assertArrayHasKey('hogera', $row2);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetchArray($database)
    {
        $rows = $database->selectArray('test', [], [], [5 => 1]);
        $val = reset($rows);
        $key = key($rows);
        $this->assertEquals(0, $key);
        $this->assertEquals([
            'id'   => 6,
            'name' => 'f',
            'data' => '',
        ], $val);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetchAssoc($database)
    {
        $rows = $database->selectAssoc('test', [], [], [5 => 1]);
        $val = reset($rows);
        $key = key($rows);
        $this->assertEquals(6, $key);
        $this->assertEquals([
            'id'   => 6,
            'name' => 'f',
            'data' => '',
        ], $val);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetchLists($database)
    {
        $cols = $database->selectLists('test.name', [], [], [5 => 3]);
        $this->assertEquals([
            'f',
            'g',
            'h',
        ], $cols);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetchPairs($database)
    {
        $pairs = $database->selectPairs('test.id,name', [], [], [5 => 1]);
        $val = reset($pairs);
        $key = key($pairs);
        $this->assertEquals(6, $key);
        $this->assertEquals('f', $val);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetchRow($database)
    {
        $row = $database->selectTuple('test.id,name', [], [], [5 => 1]);
        $this->assertEquals([
            'id'   => 6,
            'name' => 'f',
        ], $row);

        $one = $database->selectTuple('test.id,name', ['1=0']);
        $this->assertFalse($one);

        $this->assertException('too many', L($database)->selectTuple('test.id,name'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetchValue($database)
    {
        $one = $database->selectValue('test.name', [], [], [5 => 1]);
        $this->assertEquals('f', $one);

        $one = $database->selectValue('test.name', ['1=0']);
        $this->assertFalse($one);

        $this->assertException('too many', L($database)->selectValue('test.id'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetch_autoCastType($database)
    {
        $database->setAutoCastType([
            'integer'      => true,
            'datetime'     => [
                'select' => true,
                'affect' => false,
            ],
            'simple_array' => [
                'select' => function ($value, $platform) {
                    if ($this instanceof Type) {
                        return $this->convertToPHPValue($value, $platform);
                    }
                },
                'affect' => function ($value, $platform) {
                    if ($this instanceof Type) {
                        return $this->convertToDatabaseValue($value, $platform);
                    }
                },
            ],
            'json'         => [
                'select' => true,
                'affect' => true,
            ],
        ]);

        $database->getSchema()->setTableColumn('misctype', 'carray', ['type' => Types::SIMPLE_ARRAY]);
        $database->getSchema()->setTableColumn('misctype', 'cjson', ['type' => Types::JSON]);

        $database->insert('misctype', [
            'cint'      => 1,
            'cfloat'    => 1.1,
            'cdecimal'  => 1.2,
            'cdate'     => '2012-12-12',
            'cdatetime' => '2012-12-12 12:34:56',
            'cstring'   => 'hoge',
            'ctext'     => 'fuga',
            'carray'    => [1, 2, 3, 4, 5, 6, 7, 8, 9],
            'cjson'     => new Expression('?', [json_encode(['a' => 'A'])]),
        ]);
        $row = $database->selectTuple([
            'misctype MT' => [
                'cint',
                'cdatetime',
                'carray',
                'cjson',
            ],
        ], [], [], 1);
        $this->assertSame(1, $row['cint']);
        $this->assertInstanceOf('\DateTime', $row['cdatetime']);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray']);
        $this->assertEquals(['a' => 'A'], $row['cjson']);

        // 子供ネストもOK
        $this->assertSame([
            'article_id' => 1,
            'comments'   => [
                1 => ['comment_id' => 1],
                2 => ['comment_id' => 2],
                3 => ['comment_id' => 3],
            ],
        ], $database->selectTuple([
            't_article' => [
                'article_id',
                't_comment comments' => ['comment_id'],
            ],
        ], [], [], 1));

        if ($database->getCompatiblePlatform()->supportsTableNameAttribute()) {
            // mysql は * だけで型を活かすことができる
            $row = $database->selectTuple('misctype', [], [], 1);
            $this->assertSame(1, $row['cint']);
            $this->assertInstanceOf('\DateTime', $row['cdatetime']);
            $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray']);

            // さらには生クエリでも可能
            $row = $database->fetchTuple('select * from misctype');
            $this->assertSame(1, $row['cint']);
            $this->assertInstanceOf('\DateTime', $row['cdatetime']);
            $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray']);
        }
        else {
            // mysql の 'a.b.c' を模倣
            $row = $database->fetchTuple('select cint as "HOGE.misctype.cint" from misctype');
            $this->assertSame(1, $row['cint']);
        }

        $database->setAutoCastType([]);
        $database->getSchema()->refresh();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_fetch_misc($database)
    {
        $this->assertException('', L($database)->fetchArray('invalid'));
        $this->assertException('', L($database)->fetchAssoc('invalid'));
        $this->assertException('', L($database)->fetchLists('invalid'));
        $this->assertException('', L($database)->fetchPairs('invalid'));
        $this->assertException('', L($database)->fetchTuple('invalid'));
        $this->assertException('', L($database)->fetchValue('invalid'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_yield($database)
    {
        $it = $database->yieldLists($database->select('test.name', ['id' => [2, 3]]));
        $this->assertInstanceOf(Yielder::class, $it);
        $this->assertEquals(['b', 'c'], iterator_to_array($it));

        $it = $database->yieldArray('select * from test where id=1');
        $this->assertInstanceOf(Yielder::class, $it);
        $this->assertEquals([
            [
                'id'   => '1',
                'name' => 'a',
                'data' => '',
            ],
        ], iterator_to_array($it));

        $it = $database->yieldArray($database->select('t_article/t_comment'));
        $this->assertEquals([
            [
                'article_id' => '1',
                'title'      => 'タイトルです',
                'checks'     => '',
                'Comment'    => [
                    1 => [
                        'comment_id' => '1',
                        'article_id' => '1',
                        'comment'    => 'コメント1です',
                    ],
                    2 => [
                        'comment_id' => '2',
                        'article_id' => '1',
                        'comment'    => 'コメント2です',
                    ],
                    3 => [
                        'comment_id' => '3',
                        'article_id' => '1',
                        'comment'    => 'コメント3です',
                    ],
                ],
            ],
            [
                'article_id' => '2',
                'title'      => 'コメントのない記事です',
                'checks'     => '',
                'Comment'    => [],
            ],
        ], iterator_to_array($it));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_perform($database)
    {
        $this->assertEquals(['hoge'], $database->perform([['hoge']], 'lists'));
        $this->assertException("unknown fetch method 'hoge'", L($database)->perform([], 'hoge'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_describe($database)
    {
        $this->assertInstanceOf(Schema\Schema::class, $database->describe());
        $this->assertInstanceOf(Schema\Table::class, $database->describe('t_article'));
        $this->assertInstanceOf(Schema\Table::class, $database->describe('v_blog'));
        $this->assertInstanceOf(Schema\ForeignKeyConstraint::class, $database->describe('fk_articlecomment'));
        $this->assertInstanceOf(Schema\Column::class, $database->describe('t_article.article_id'));
        $this->assertInstanceOf(Schema\Index::class, $database->describe('t_article.secondary'));
        $this->assertException('undefined schema object', L($database)->describe('hogera'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getAnnotation($database)
    {
        $annotation = $database->getAnnotation();
        $this->assertStringContainsString('$t_article', $annotation);
        $this->assertStringContainsString('$Comment', $annotation);
        $this->assertStringContainsString(\ryunosuke\dbml\Gateway\TableGateway::class, $annotation);
        $this->assertStringContainsString(\ryunosuke\Test\Gateway\Article::class, $annotation);
        $this->assertStringContainsString(\ryunosuke\Test\Gateway\Comment::class, $annotation);

        $annotation = $database->getAnnotation(['t_article', 'Comment']);
        $this->assertStringNotContainsString('$t_article', $annotation);
        $this->assertStringNotContainsString('$Comment', $annotation);

        $this->assertNull($database->getAnnotation('*'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_echoAnnotation($database)
    {
        $annotation = $database->echoAnnotation('ryunosuke\\Test\\dbml\\Annotation', __DIR__ . '/../../annotation.php');
        $this->assertStringContainsString('namespace ryunosuke\\Test\\dbml\\Annotation;', $annotation);
        $this->assertStringContainsString('trait TableGatewayProvider', $annotation);
        $this->assertStringContainsString('class Database', $annotation);
        $this->assertStringContainsString('class ArticleTableGateway extends', $annotation);
        $this->assertStringContainsString('class CommentTableGateway extends', $annotation);
        $this->assertStringContainsString('class ManagedCommentTableGateway extends', $annotation);
        $this->assertStringContainsString('class ArticleEntity extends', $annotation);
        $this->assertStringContainsString('class CommentEntity extends', $annotation);
        $this->assertStringContainsString('class ManagedCommentEntity extends', $annotation);
        $this->assertStringContainsString('$tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []', $annotation);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_echoPhpStormMeta($database)
    {
        $database = $database->context();
        $database->setAutoCastType([
            Types::SIMPLE_ARRAY => [
                'select' => function (): \ArrayObject { },
                'affect' => false,
            ],
            Types::INTEGER      => [
                'select' => function () {/* no return type */ },
                'affect' => false,
            ],
        ]);

        $metafile = sys_get_temp_dir() . '/phpstormmeta';
        $phpstorm_meta = $database->echoPhpStormMeta(false, $metafile);
        $this->assertFileExists($metafile);
        $this->assertStringContainsString('namespace PHPSTORM_META', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\dbml\\Entity\\Entityable', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Article', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Comment', $phpstorm_meta);
        $this->assertStringContainsString('=> \DateTime::class', $phpstorm_meta);
        $this->assertStringContainsString('=> \ArrayObject::class', $phpstorm_meta);

        $phpstorm_meta = $database->echoPhpStormMeta(true, null, 'EntityNamespace');
        $this->assertStringNotContainsString('namespace PHPSTORM_META', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Article', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Comment', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Comment', $phpstorm_meta);
        $this->assertStringContainsString('new \\EntityNamespace\\test', $phpstorm_meta);
        $this->assertStringContainsString('=> \DateTime::class', $phpstorm_meta);

        $database->unstackAll();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getEmptyRecord($database)
    {
        // テーブル指定は配列で返るはず
        $record = $database->getEmptyRecord('test');
        $this->assertIsArray($record);
        $this->assertEquals(null, $record['id']);
        $this->assertEquals('', $record['name']);
        $this->assertEquals('', $record['data']);

        // エンティティ指定はオブジェクトで返るはず
        $record = $database->getEmptyRecord('Article');
        $this->assertInstanceOf(Article::class, $record);
        $this->assertEquals(null, $record['article_id']);
        $this->assertEquals(null, $record['title']);

        // デフォルト値が効いてるはず
        $record = $database->getEmptyRecord('test', ['name' => 'hoge']);
        $this->assertEquals(null, $record['id']);
        $this->assertEquals('hoge', $record['name']);
        $this->assertEquals('', $record['data']);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeSelect_and_Update($database)
    {
        $database->insert('noauto', ['id' => false, 'name' => 'hoge']);
        $database->insert('noauto', ['id' => true, 'name' => 'fuga']);

        $this->assertCount(1, $database->selectArray('noauto', ['id' => false]));
        $this->assertCount(1, $database->selectArray('test', ['id' => true]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_migrate($database)
    {
        $records = [
            [
                'id'         => '11',
                'name'       => 'hoge',
                'foreign_c1' => [
                    [
                        'seq'  => 1,
                        'name' => 'c1',
                    ],
                ],
            ],
            [
                'id'         => '12',
                'name'       => 'fuga',
                'foreign_c2' => [
                    [
                        'seq'  => 1,
                        'name' => 'c2',
                    ],
                ],
            ],
            [
                'id'         => '13',
                'name'       => 'piyo',
                'foreign_c1' => [
                    [
                        'seq'  => 2,
                        'name' => 'cc1',
                    ],
                ],
                'foreign_c2' => [
                    [
                        'seq'  => 2,
                        'name' => 'cc2',
                    ],
                ],
            ],
        ];
        $opt = ['dryrun' => false];

        // INSERT
        $result = $database->migrate('foreign_p', 'insert', $records, $opt + ['bulk' => false]);
        $this->assertEquals(3, $result);

        // SELECT
        $result = $database->migrate('foreign_p', 'select', $records, $opt + ['bulk' => false]);
        $this->assertEquals(array_map(fn($v) => array_remove($v, ['foreign_c1', 'foreign_c2']), $records), $result);

        // UPDATE
        array_walk($records, fn(&$r) => $r['name'] = 'x' . $r['name']);
        $result = $database->migrate('foreign_p', 'update', $records, $opt + ['bulk' => false]);
        $this->assertEquals(3, $result);

        // DELETE
        $result = $database->migrate('foreign_p', 'delete', $records, $opt + ['bulk' => false]);
        $this->assertEquals(3, $result);

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            // MODIFY
            array_walk($records, fn(&$r) => $r['name'] = 'y' . $r['name']);
            $result = $database->migrate('foreign_p', 'modify', $records, $opt + ['bulk' => false]);
            $this->assertEquals(3, $result);

            // CHANGE
            array_walk($records, fn(&$r) => $r['name'] = 'z' . $r['name']);
            $result = $database->migrate('foreign_p', 'change', $records, $opt + ['bulk' => false]);
            $this->assertEquals(3, $result);

            // SAVE
            $result = $database->migrate('foreign_p', 'save', $records, $opt + ['bulk' => false]);
            $this->assertEquals(7, $result);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_migrate_bulk($database)
    {
        // SqlServer はいろいろと辛いので除外（ID 列さえ除けば多分動くはず）
        if (!$database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            return;
        }

        $records = [
            [
                'id'         => '11',
                'name'       => 'hoge',
                'foreign_c1' => [
                    [
                        'seq'  => 1,
                        'name' => 'c1',
                    ],
                ],
            ],
            [
                'id'         => '12',
                'name'       => 'fuga',
                'foreign_c2' => [
                    [
                        'seq'  => 1,
                        'name' => 'c2',
                    ],
                ],
            ],
            [
                'id'         => '13',
                'name'       => 'piyo',
                'foreign_c1' => [
                    [
                        'seq'  => 2,
                        'name' => 'cc1',
                    ],
                ],
                'foreign_c2' => [
                    [
                        'seq'  => 2,
                        'name' => 'cc2',
                    ],
                ],
            ],
        ];
        $opt = ['dryrun' => false];

        // INSERT
        $result = $database->migrate('foreign_p', 'insert', $records, $opt + ['bulk' => true]);
        $this->assertEquals(3, $result);

        // SELECT
        $result = $database->migrate('foreign_p', 'select', $records, $opt + ['bulk' => true]);
        $this->assertEquals(array_map(fn($v) => array_remove($v, ['foreign_c1', 'foreign_c2']), $records), $result);

        // UPDATE
        array_walk($records, fn(&$r) => $r['name'] = 'x' . $r['name']);
        $result = $database->migrate('foreign_p', 'update', $records, $opt + ['bulk' => true]);
        $this->assertEquals(3, $result);

        // DELETE
        $result = $database->migrate('foreign_p', 'delete', $records, $opt + ['bulk' => true]);
        $this->assertEquals(3, $result);

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            // MODIFY
            array_walk($records, fn(&$r) => $r['name'] = 'y' . $r['name']);
            $result = $database->migrate('foreign_p', 'modify', $records, $opt + ['bulk' => true]);
            $this->assertEquals(3, $result);

            // CHANGE
            array_walk($records, fn(&$r) => $r['name'] = 'z' . $r['name']);
            $result = $database->migrate('foreign_p', 'change', $records, $opt + ['bulk' => true]);
            $this->assertEquals(3, $result);

            // SAVE
            $result = $database->migrate('foreign_p', 'save', $records, $opt + ['bulk' => true]);
            $this->assertEquals(7, $result);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_migrate_dryrun($database)
    {
        $records = [
            [
                'id'         => '11',
                'name'       => 'hoge',
                'foreign_c1' => [
                    [
                        'seq'  => 1,
                        'name' => 'c1',
                    ],
                ],
            ],
            [
                'id'         => '12',
                'name'       => 'fuga',
                'foreign_c2' => [
                    [
                        'seq'  => 1,
                        'name' => 'c2',
                    ],
                ],
            ],
            [
                'id'         => '13',
                'name'       => 'piyo',
                'foreign_c1' => [
                    [
                        'seq'  => 2,
                        'name' => 'cc1',
                    ],
                ],
                'foreign_c2' => [
                    [
                        'seq'  => 2,
                        'name' => 'cc2',
                    ],
                ],
            ],
        ];
        $opt = ['dryrun' => true];

        // INSERT
        $result = $database->migrate('foreign_p', 'insert', $records, $opt + ['bulk' => false]);
        $this->assertEquals([
            "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge')",
            "INSERT INTO foreign_p (id, name) VALUES ('12', 'fuga')",
            "INSERT INTO foreign_p (id, name) VALUES ('13', 'piyo')",
        ], $result);
        $result = $database->migrate('foreign_p', 'insert', $records, $opt + ['bulk' => true]);
        $this->assertEquals([
            "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo')",
        ], $result);

        // SELECT
        $result = $database->migrate('foreign_p', 'select', $records, $opt + ['bulk' => false]);
        $this->assertEquals([
            "SELECT foreign_p.id, foreign_p.name FROM foreign_p WHERE id = '11'",
            "SELECT foreign_p.id, foreign_p.name FROM foreign_p WHERE id = '12'",
            "SELECT foreign_p.id, foreign_p.name FROM foreign_p WHERE id = '13'",
        ], $result);
        $result = $database->migrate('foreign_p', 'select', $records, $opt + ['bulk' => true]);
        $this->assertEquals([
            "SELECT foreign_p.id, foreign_p.name FROM foreign_p WHERE (id = '11') OR (id = '12') OR (id = '13')",
        ], $result);

        // UPDATE
        $result = $database->migrate('foreign_p', 'update', $records, $opt + ['bulk' => false]);
        $this->assertEquals([
            "UPDATE foreign_p SET name = 'hoge' WHERE id = '11'",
            "UPDATE foreign_p SET name = 'fuga' WHERE id = '12'",
            "UPDATE foreign_p SET name = 'piyo' WHERE id = '13'",
        ], $result);
        $result = $database->migrate('foreign_p', 'update', $records, $opt + ['bulk' => true]);
        $this->assertEquals([
            "UPDATE foreign_p SET name = CASE id WHEN '11' THEN 'hoge' WHEN '12' THEN 'fuga' WHEN '13' THEN 'piyo' ELSE name END WHERE foreign_p.id IN ('11', '12', '13')",
        ], $result);

        // DELETE
        $result = $database->migrate('foreign_p', 'delete', $records, $opt + ['bulk' => false]);
        $this->assertEquals([
            "DELETE FROM foreign_p WHERE id = '11'",
            "DELETE FROM foreign_p WHERE id = '12'",
            "DELETE FROM foreign_p WHERE id = '13'",
        ], $result);
        $result = $database->migrate('foreign_p', 'delete', $records, $opt + ['bulk' => true]);
        $this->assertEquals([
            "DELETE FROM foreign_p WHERE (id = '11') OR (id = '12') OR (id = '13')",
        ], $result);

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            // MODIFY
            $result = $database->migrate('foreign_p', 'modify', $records, $opt + ['bulk' => false]);
            $this->assertCount(3, $result);
            $this->assertArrayStartsWith([
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('12', 'fuga') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('13', 'piyo') ON",
            ], $result);
            $result = $database->migrate('foreign_p', 'modify', $records, $opt + ['bulk' => true]);
            $this->assertCount(1, $result);
            $this->assertArrayStartsWith([
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
            ], $result);

            // CHANGE
            $result = $database->migrate('foreign_p', 'change', $records, $opt + ['bulk' => false]);
            $this->assertCount(4, $result);
            $this->assertArrayStartsWith([
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('12', 'fuga') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('13', 'piyo') ON",
                "DELETE FROM foreign_p WHERE NOT (foreign_p.id IN ('11', '12', '13'))",
            ], $result);
            $result = $database->migrate('foreign_p', 'change', $records, $opt + ['bulk' => true]);
            $this->assertCount(2, $result);
            $this->assertArrayStartsWith([
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
                "DELETE FROM foreign_p WHERE NOT (foreign_p.id IN ('11', '12', '13'))",
            ], $result);

            // SAVE
            $result = $database->migrate('foreign_p', 'save', $records, $opt + ['bulk' => false]);
            $this->assertCount(11, $result);
            $this->assertArrayStartsWith([
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge') ON",
                "INSERT INTO foreign_c1 (seq, name, id) VALUES ('1', 'c1', '11') ON",
                "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('11')) AND (NOT ((foreign_c1.seq = '1' AND foreign_c1.id = '11')))",
                "INSERT INTO foreign_p (id, name) VALUES ('12', 'fuga') ON",
                "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('1', 'c2', '12') ON",
                "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('12')) AND (NOT ((foreign_c2.seq = '1' AND foreign_c2.cid = '12')))",
                "INSERT INTO foreign_p (id, name) VALUES ('13', 'piyo') ON",
                "INSERT INTO foreign_c1 (seq, name, id) VALUES ('2', 'cc1', '13') ON",
                "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('13')) AND (NOT ((foreign_c1.seq = '2' AND foreign_c1.id = '13')))",
                "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('2', 'cc2', '13') ON",
                "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('13')) AND (NOT ((foreign_c2.seq = '2' AND foreign_c2.cid = '13')))",
            ], $result);
            $result = $database->migrate('foreign_p', 'save', $records, $opt + ['bulk' => true]);
            $this->assertCount(5, $result);
            if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
                $this->assertArrayStartsWith([
                    "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
                    "INSERT INTO foreign_c1 (seq, name, id) VALUES ('1', 'c1', '11'), ('2', 'cc1', '13') ON",
                    "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('11','13')) AND (NOT ((foreign_c1.seq, foreign_c1.id) IN (('1', '11'), ('2', '13'))))",
                    "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('1', 'c2', '12'), ('2', 'cc2', '13') ON",
                    "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('12','13')) AND (NOT ((foreign_c2.seq, foreign_c2.cid) IN (('1', '12'), ('2', '13'))))",
                ], $result);
            }
            else {
                $this->assertArrayStartsWith([
                    "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
                    "INSERT INTO foreign_c1 (seq, name, id) VALUES ('1', 'c1', '11'), ('2', 'cc1', '13') ON",
                    "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('11','13')) AND (NOT ((foreign_c1.seq = '1' AND foreign_c1.id = '11') OR (foreign_c1.seq = '2' AND foreign_c1.id = '13')))",
                    "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('1', 'c2', '12'), ('2', 'cc2', '13') ON",
                    "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('12','13')) AND (NOT ((foreign_c2.seq = '1' AND foreign_c2.cid = '12') OR (foreign_c2.seq = '2' AND foreign_c2.cid = '13')))",
                ], $result);
            }
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_migrate_misc($database)
    {
        $this->assertException('undefined primary key at', L($database)->migrate('foreign_p', 'insert', [['name' => 'hoge']]));
        $this->assertException('is not supported', L($database)->migrate('foreign_p', 'undefined', []));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_import($database)
    {
        $affected = $database->import([]);
        $this->assertEquals(0, $affected);

        $affected = $database->import([
            'g_ancestor' => [
                [
                    'ancestor_name' => 'A',
                    'g_parent'      => [
                        [
                            'parent_name' => 'AA',
                            'g_child'     => [
                                [
                                    'child_name' => 'AAA',
                                ],
                                [
                                    'child_name' => 'AAB',
                                ],
                            ],
                        ],
                        [
                            'parent_name' => 'AB',
                            'g_child'     => [
                                [
                                    'child_name' => 'ABA',
                                ],
                                [
                                    'child_name' => 'ABB',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'ancestor_name' => 'B',
                    'g_parent'      => [
                        [
                            'parent_name' => 'BA',
                            'g_child'     => [
                                [
                                    'child_name' => 'BAA',
                                ],
                                [
                                    'child_name' => 'BAB',
                                ],
                            ],
                        ],
                        [
                            'parent_name' => 'BB',
                            'g_child'     => [
                                [
                                    'child_name' => 'BBA',
                                ],
                                [
                                    'child_name' => 'BBB',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertEquals([
            1 => [
                'ancestor_id'   => '1',
                'ancestor_name' => 'A',
                'g_parent'      => [
                    1 => [
                        'parent_id'   => '1',
                        'ancestor_id' => '1',
                        'parent_name' => 'AA',
                        'g_child'     => [
                            1 => [
                                'child_id'   => '1',
                                'parent_id'  => '1',
                                'child_name' => 'AAA',
                            ],
                            2 => [
                                'child_id'   => '2',
                                'parent_id'  => '1',
                                'child_name' => 'AAB',
                            ],
                        ],
                    ],
                    2 => [
                        'parent_id'   => '2',
                        'ancestor_id' => '1',
                        'parent_name' => 'AB',
                        'g_child'     => [
                            3 => [
                                'child_id'   => '3',
                                'parent_id'  => '2',
                                'child_name' => 'ABA',
                            ],
                            4 => [
                                'child_id'   => '4',
                                'parent_id'  => '2',
                                'child_name' => 'ABB',
                            ],
                        ],
                    ],
                ],
            ],
            2 => [
                'ancestor_id'   => '2',
                'ancestor_name' => 'B',
                'g_parent'      => [
                    3 => [
                        'parent_id'   => '3',
                        'ancestor_id' => '2',
                        'parent_name' => 'BA',
                        'g_child'     => [
                            5 => [
                                'child_id'   => '5',
                                'parent_id'  => '3',
                                'child_name' => 'BAA',
                            ],
                            6 => [
                                'child_id'   => '6',
                                'parent_id'  => '3',
                                'child_name' => 'BAB',
                            ],
                        ],
                    ],
                    4 => [
                        'parent_id'   => '4',
                        'ancestor_id' => '2',
                        'parent_name' => 'BB',
                        'g_child'     => [
                            7 => [
                                'child_id'   => '7',
                                'parent_id'  => '4',
                                'child_name' => 'BBA',
                            ],
                            8 => [
                                'child_id'   => '8',
                                'parent_id'  => '4',
                                'child_name' => 'BBB',
                            ],
                        ],
                    ],
                ],
            ],
        ], $database->selectAssoc('g_ancestor/g_parent/g_child'));
        $this->assertEquals(14, $affected);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_save($database)
    {
        $database->delete('g_ancestor');

        $g_ancestor = 0;
        $g_parent = 0;
        $g_child = 0;
        $g_grand1 = 0;
        $g_grand2 = 0;

        $database->save('g_ancestor', [
            [
                'ancestor_id'   => 1,
                'ancestor_name' => 'A',
                'g_parent'      => [
                    [
                        'parent_id'   => 1,
                        'parent_name' => 'AA',
                        'g_child'     => [
                            [
                                'child_id'   => 1,
                                'child_name' => 'AAA',
                            ],
                            [
                                'child_id'   => 2,
                                'child_name' => 'AAB',
                            ],
                        ],
                    ],
                    [
                        'parent_id'   => 2,
                        'parent_name' => 'AB',
                        'g_child'     => [
                            [
                                'child_id'   => 3,
                                'child_name' => 'ABA',
                            ],
                            [
                                'child_id'   => 4,
                                'child_name' => 'ABB',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'ancestor_id'   => 2,
                'ancestor_name' => 'B',
                'g_parent'      => [
                    [
                        'parent_id'   => 3,
                        'parent_name' => 'BA',
                        'g_child'     => [
                            [
                                'child_id'   => 5,
                                'child_name' => 'BAA',
                            ],
                            [
                                'child_id'   => 6,
                                'child_name' => 'BAB',
                            ],
                        ],
                    ],
                    [
                        'parent_id'   => 4,
                        'parent_name' => 'BB',
                        'g_child'     => [
                            [
                                'child_id'   => 7,
                                'child_name' => 'BBA',
                            ],
                            [
                                'child_id'   => 8,
                                'child_name' => 'BBB',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals($g_ancestor += 2, $database->count('g_ancestor')); // 新規作成なので +2行
        $this->assertEquals($g_parent += 4, $database->count('g_parent'));     // 2行に2行ずつなので +4行
        $this->assertEquals($g_child += 8, $database->count('g_child'));       // 4行に2行ずつなので +8行

        // g_ancestor * 1 * g_parent * 2 * g_child * 2 + g_grand1 * 2 を完全新規作成
        $primary = $database->save('g_ancestor', [
            'ancestor_name' => 'C',
            'g_parent'      => [
                [
                    'parent_name' => 'CA',
                    'g_child'     => [
                        [
                            'child_name' => 'CAA',
                        ],
                        [
                            'child_name' => 'CAB',
                        ],
                    ],
                    'g_grand1'    => [
                        [
                            'grand1_name' => 'CAA',
                        ],
                        [
                            'grand1_name' => 'CAB',
                        ],
                    ],
                ],
                [
                    'parent_name' => 'AB',
                    'g_child'     => [
                        [
                            'child_name' => 'CBA',
                        ],
                        [
                            'child_name' => 'CBB',
                        ],
                    ],
                    'g_grand1'    => [
                        [
                            'grand1_name' => 'CBA',
                        ],
                        [
                            'grand1_name' => 'CBB',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals($g_ancestor += 1, $database->count('g_ancestor')); // 新規作成なので +1行
        $this->assertEquals($g_parent += 2, $database->count('g_parent'));     // 1行に2行ずつなので +2行
        $this->assertEquals($g_child += 4, $database->count('g_child'));       // 2行に2行ずつなので +4行
        $this->assertEquals($g_grand1 += 4, $database->count('g_grand1'));     // 2行に2行ずつなので +4行

        $this->assertEquals([
            "ancestor_id" => 3,
            "g_parent"    => [
                [
                    "parent_id" => 5,
                    "g_child"   => [
                        [
                            "child_id" => 9,
                        ],
                        [
                            "child_id" => 10,
                        ],
                    ],
                    "g_grand1"  => [
                        [
                            "grand_id" => 1,
                        ],
                        [
                            "grand_id" => 2,
                        ],
                    ],
                ],
                [
                    "parent_id" => 6,
                    "g_child"   => [
                        [
                            "child_id" => 11,
                        ],
                        [
                            "child_id" => 12,
                        ],
                    ],
                    "g_grand1"  => [
                        [
                            "grand_id" => 3,
                        ],
                        [
                            "grand_id" => 4,
                        ],
                    ],
                ],
            ],
        ], $primary);

        // g_ancestor * 1 * g_parent * 1 * g_child * 0 を変更
        $primary = $database->save('g_ancestor', [
            "ancestor_id"   => 3,
            'ancestor_name' => 'X',
            'g_parent'      => [
                [
                    'parent_name' => 'XX',
                    'g_child'     => [],
                ],
            ],
        ]);

        $this->assertEquals($g_ancestor += 0, $database->count('g_ancestor')); // 作成していないので 0行
        $this->assertEquals($g_parent -= 1, $database->count('g_parent'));     // 1行しか与えていないので -1行
        $this->assertEquals($g_child -= 4, $database->count('g_child'));       // 1行しか与えていないので -4行
        $this->assertEquals($g_grand1 -= 4, $database->count('g_grand1'));     // 1行も与えていないので -4行

        $this->assertEquals([
            "ancestor_id" => 3,
            "g_parent"    => [
                [
                    "parent_id" => 7,
                ],
            ],
        ], $primary);

        if ($database->getSchema()->hasTable('g_grand2')) {
            $primary = $database->save('g_ancestor', [
                "ancestor_id"   => 3,
                'ancestor_name' => 'X2',
                'g_parent'      => [
                    [
                        "parent_id"   => 7,
                        'parent_name' => 'X2X',
                        'g_child'     => [
                            [
                                'child_name' => 'XXX',
                            ],
                        ],
                        'g_grand1'    => [
                            [
                                'grand1_name' => 'X2XX',
                            ],
                        ],
                        'g_grand2'    => [
                            [
                                'grand2_name' => 'X2XY',
                            ],
                        ],
                    ],
                ],
            ]);

            /** @noinspection PhpUnusedLocalVariableInspection */
            {
                $this->assertEquals($g_ancestor += 0, $database->count('g_ancestor'));
                $this->assertEquals($g_parent += 0, $database->count('g_parent'));
                $this->assertEquals($g_child += 1, $database->count('g_child'));
                $this->assertEquals($g_grand1 += 1, $database->count('g_grand1'));
                $this->assertEquals($g_grand2 += 1, $database->count('g_grand2'));
            }

            $this->assertEquals([
                "ancestor_id" => 3,
                "g_parent"    => [
                    [
                        "parent_id" => 7,
                        'g_child'   => [
                            [
                                'child_id' => 13,
                            ],
                        ],
                        "g_grand1"  => [
                            [
                                "grand_id" => 5,
                            ],
                        ],
                        "g_grand2"  => [
                            [
                                "grand_id" => 1,
                            ],
                        ],
                    ],
                ],
            ], $primary);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_save_misc($database)
    {
        $sqls = $database->dryrun()->save('g_ancestor', [
            [
                'ancestor_id'   => 1,
                'ancestor_name' => 'A',
                'g_parent'      => [
                    [
                        'parent_id'   => 1,
                        'parent_name' => 'AA',
                        'g_child'     => [
                            [
                                'child_id'   => 1,
                                'child_name' => 'AAA',
                            ],
                            [
                                'child_id'   => 2,
                                'child_name' => 'AAB',
                            ],
                        ],
                    ],
                    [
                        'parent_id'   => 2,
                        'parent_name' => 'AB',
                        'g_child'     => [
                            [
                                'child_id'   => 3,
                                'child_name' => 'ABA',
                            ],
                            [
                                'child_id'   => 4,
                                'child_name' => 'ABB',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'ancestor_id'   => 2,
                'ancestor_name' => 'B',
                'g_parent'      => [
                    [
                        'parent_id'   => 3,
                        'parent_name' => 'BA',
                        'g_child'     => [
                            [
                                'child_id'   => 5,
                                'child_name' => 'BAA',
                            ],
                            [
                                'child_id'   => 6,
                                'child_name' => 'BAB',
                            ],
                        ],
                    ],
                    [
                        'parent_id'   => 4,
                        'parent_name' => 'BB',
                        'g_child'     => [
                            [
                                'child_id'   => 7,
                                'child_name' => 'BBA',
                            ],
                            [
                                'child_id'   => 8,
                                'child_name' => 'BBB',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        if ($database->getCompatiblePlatform()->supportsBulkMerge()) {
            $this->assertArrayStartsWith([
                'INSERT INTO g_ancestor',
                'INSERT INTO g_parent',
                'DELETE FROM g_parent',
                'INSERT INTO g_child',
                'DELETE FROM g_child',
            ], $sqls);
        }
        else {
            $this->assertArrayStartsWith([
                'INSERT INTO g_ancestor',
                'INSERT INTO g_ancestor',
                'INSERT INTO g_parent',
                'INSERT INTO g_parent',
                'INSERT INTO g_parent',
                'INSERT INTO g_parent',
                'DELETE FROM g_parent',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'DELETE FROM g_child',
            ], $sqls);
        }

        foreach ($sqls as $sql) {
            $database->executeAffect($sql);
        }

        $sqls = $database->dryrun()->save('g_ancestor', [
            'ancestor_name' => 'C',
            'g_parent'      => [
                [
                    'parent_name' => 'CA',
                    'g_child'     => [
                        [
                            'child_name' => 'CAA',
                        ],
                        [
                            'child_name' => 'CAB',
                        ],
                    ],
                    'g_grand1'    => [
                        [
                            'grand1_name' => 'CAA',
                        ],
                        [
                            'grand1_name' => 'CAB',
                        ],
                    ],
                ],
                [
                    'parent_name' => 'AB',
                    'g_child'     => [
                        [
                            'child_name' => 'CBA',
                        ],
                        [
                            'child_name' => 'CBB',
                        ],
                    ],
                    'g_grand1'    => [
                        [
                            'grand1_name' => 'CBA',
                        ],
                        [
                            'grand1_name' => 'CBB',
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertArrayStartsWith([
            'INSERT INTO g_ancestor',
            'INSERT INTO g_parent',
            'INSERT INTO g_parent',
            'DELETE FROM g_parent',
            'INSERT INTO g_child',
            'INSERT INTO g_child',
            'INSERT INTO g_child',
            'INSERT INTO g_child',
            'DELETE FROM g_child',
            'INSERT INTO g_grand1',
            'INSERT INTO g_grand1',
            'INSERT INTO g_grand1',
            'INSERT INTO g_grand1',
            'DELETE FROM g_grand1',
        ], $sqls);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_loadCsv($database)
    {
        // SqlServer はいろいろと辛いので除外（ID 列さえ除けば多分動くはず）
        if (!$database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            return;
        }

        $csvfile = $csvfile_head = sys_get_temp_dir() . '/csvfile_head.csv';

        // skip と chunk
        file_put_contents($csvfile_head, <<<CSV
id,name,cint,cfloat,cdecimal
1,name1,1,1.1,1.11
2,name2,2,2.2,2.22
3,name3,3,3.3,3.33
CSV
        );
        $database->delete('nullable');
        $this->assertEquals(3, $database->loadCsv('nullable', $csvfile_head, [
            'skip'  => 1,
            'chunk' => 2,
        ]));
        $this->assertEquals([
            ['id' => '1', 'name' => 'name1', 'cint' => '1', 'cfloat' => '1.1', 'cdecimal' => '1.11',],
            ['id' => '2', 'name' => 'name2', 'cint' => '2', 'cfloat' => '2.2', 'cdecimal' => '2.22',],
            ['id' => '3', 'name' => 'name3', 'cint' => '3', 'cfloat' => '3.3', 'cdecimal' => '3.33',],
        ], $database->selectArray('nullable'));

        // null と Expression と Closure
        $database->delete('nullable');
        $this->assertEquals(1, $database->loadCsv([
            'nullable' => [
                'id',
                'name' => new Expression('UPPER(?)'), // 大文字で取り込む
                'cint' => function ($v) { return $v * 100; }, // php で100 倍して取り込む
                null, // cfloat 列をスキップ
                'cdecimal',
            ],
        ], $csvfile_head, [
            'skip' => 3,
        ]));
        $this->assertEquals([
            ['id' => '3', 'name' => 'NAME3', 'cint' => '300', 'cfloat' => null, 'cdecimal' => '3.33',],
        ], $database->selectArray('nullable'));

        // 範囲内と範囲外の直指定
        file_put_contents($csvfile, '1,name1,1.11');
        $database->delete('nullable');
        $this->assertEquals(1, $database->loadCsv([
            'nullable' => [
                'id',
                'name'     => 'direct', // 範囲内直指定
                'cdecimal' => null,
                'cfloat'   => 1.23,     // 範囲外直指定
            ],
        ], $csvfile));
        $this->assertEquals([
            ['id' => '1', 'name' => 'direct', 'cint' => null, 'cfloat' => '1.23', 'cdecimal' => null,],
        ], $database->selectArray('nullable'));

        // デリミタとエンコーディング
        file_put_contents($csvfile, mb_convert_encoding("1\tあああ", 'SJIS', 'utf8'));
        $database->delete('nullable');
        $this->assertEquals(1, $database->loadCsv([
            'nullable' => [
                'id',
                'name',
            ],
        ], $csvfile, [
            'delimiter' => "\t",
            'encoding'  => 'SJIS',
        ]));
        $this->assertEquals([
            ['id' => '1', 'name' => 'あああ', 'cint' => null, 'cfloat' => null, 'cdecimal' => null,],
        ], $database->selectArray('nullable'));

        // dryrun
        file_put_contents($csvfile, '1,name1,1.11');
        $this->assertEquals("INSERT INTO nullable (id, name, cdecimal, cfloat) VALUES ('1', 'direct', NULL, '1.23')", $database->dryrun()->loadCsv([
            'nullable' => [
                'id',
                'name'     => 'direct', // 範囲内直指定
                'cdecimal' => null,
                'cfloat'   => 1.23,     // 範囲外直指定
            ],
        ], $csvfile));

        // カバレッジのために SQL 検証はしておく（実際のテストはすぐ↓）
        if ($database->getPlatform() instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            $sql = $database->dryrun()->loadCsv([
                'nullable' => [
                    'id',
                    'name'  => new Expression('UPPER(?)'),
                    null,
                    'dummy' => null,
                    'data'  => 'binary',
                ],
            ], 'hoge.csv', [
                'native' => true,
            ]);
            $this->assertStringIgnoreBreak("
LOAD DATA LOCAL INFILE 'hoge.csv'
INTO TABLE nullable
CHARACTER SET 'utf8'
FIELDS
TERMINATED BY ','
ENCLOSED BY '\"'
ESCAPED BY '\'
LINES TERMINATED BY '\n'
IGNORE 0 LINES
(@id, @name, @__dummy__2, @dummy, @data) SET id = @id, name = UPPER(@name), dummy = NULL, data = 'binary'
", $sql);

            $this->assertException('accept Closure', L($database->dryrun())->loadCsv([
                'nullable' => [
                    'id' => function () { },
                ],
            ], 'hoge.csv', [
                'native' => true,
            ]));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_loadCsv_native($database)
    {
        if (!$database->getPlatform() instanceof MySQLPlatform) {
            return;
        }

        // for mysql 8.0
        $database->executeAffect('SET GLOBAL local_infile= 1');

        $csvfile = sys_get_temp_dir() . '/load.csv';

        // null と Expression と Closure
        file_put_contents($csvfile, <<<CSV
id,name,cint,cfloat,cdecimal
1,name1,1,1.1,1.11
2,name2,2,2.2,2.22
CSV
        );
        $database->delete('nullable');
        $this->assertEquals(2, $database->loadCsv([
            'nullable' => [
                'id',
                'name' => new Expression('UPPER(?)'),
                'cint' => 999,
                null,
                'cdecimal',
            ],
        ], $csvfile, [
            'native' => true,
            'skip'   => 1,
        ]));
        $this->assertEquals([
            ['id' => '1', 'name' => 'NAME1', 'cint' => '999', 'cfloat' => null, 'cdecimal' => '1.11',],
            ['id' => '2', 'name' => 'NAME2', 'cint' => '999', 'cfloat' => null, 'cdecimal' => '2.22',],
        ], $database->selectArray('nullable'));

        // 範囲内と範囲外の直指定
        file_put_contents($csvfile, '1,name1,1.11');
        $database->delete('nullable');
        $this->assertEquals(1, $database->loadCsv([
            'nullable' => [
                'id',
                'name'     => 'direct', // 範囲内直指定
                'cdecimal' => null,
                'cfloat'   => 1.23,     // 範囲外直指定
            ],
        ], $csvfile, [
            'native' => true,
        ]));
        $this->assertEquals([
            ['id' => '1', 'name' => 'direct', 'cint' => null, 'cfloat' => '1.23', 'cdecimal' => null,],
        ], $database->selectArray('nullable'));

        // デリミタとエンコーディング
        file_put_contents($csvfile, mb_convert_encoding("1\tあああ", 'SJIS', 'utf8'));
        $database->delete('nullable');
        $this->assertEquals(1, $database->loadCsv([
            'nullable' => [
                'id',
                'name',
            ],
        ], $csvfile, [
            'native'     => true,
            'delimiter'  => "\t",
            'encoding'   => 'SJIS',
            'var_prefix' => 'hoge',
        ]));
        $this->assertEquals([
            ['id' => '1', 'name' => 'あああ', 'cint' => null, 'cfloat' => null, 'cdecimal' => null,],
        ], $database->selectArray('nullable'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertSelect($database)
    {
        // multiprimary テーブルに test を全部突っ込むと・・・
        $database->insertSelect('multiprimary', 'select id + ?, id, name from test', ['mainid', 'subid', 'name'], [1000]);
        // 件数が一致するはず
        $this->assertCount($database->count('test'), $database->selectArray('multiprimary', 'mainid > 1000'));

        // QueryBuilder でも同じ
        $database->insertSelect('multiprimary', $database->select('test.id - ?, id, name'), ['mainid', 'subid', 'name'], [-2000]);
        $this->assertCount($database->count('test'), $database->selectArray('multiprimary', 'mainid > 2000'));

        // 列が完全一致するなら $columns は省略できる
        $database->insertSelect('multiprimary', 'select id + 3000 as mainid, id subid, name from test');
        $this->assertCount($database->count('test'), $database->selectArray('multiprimary', 'mainid > 3000'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertArray($database)
    {
        $namequery = $database->select('test.name', [], ['id' => 'desc']);

        // 配列
        $affected = $database->insertArray('test', [
            ['name' => 'a'],
            ['name' => new Expression('UPPER(\'b\')')],
            ['name' => new Expression('UPPER(?)', 'c')],
            ['name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
        ]);
        // 4件追加したら 4 が返るはず
        $this->assertEquals(4, $affected);
        // ケツから4件取れば突っ込んだデータのはず(ただし逆順)
        $this->assertEquals(['D', 'C', 'B', 'a'], $database->fetchLists($namequery->limit($affected)));

        // ジェネレータ
        $affected = $database->insertArray('test', function () {
            foreach (['a', 'b', 'c'] as $v) {
                yield ['name' => $v];
            }
        });
        // 3件追加したら 3 が返るはず
        $this->assertEquals(3, $affected);
        // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
        $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertArray_chunk($database)
    {
        // ログを見たいので全体を preview で囲む
        $logs = $database->preview(function (Database $database) {
            $namequery = $database->select('test.name', [], ['id' => 'desc']);

            // チャンク(1)
            $affected = $database->insertArray('test', [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ], 1);
            // 3件追加したら 3 が返るはず
            $this->assertEquals(3, $affected);
            // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));

            // チャンク(2)
            $affected = $database->insertArray('test', [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ], 2);
            // 3件追加したら 3 が返るはず
            $this->assertEquals(3, $affected);
            // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));

            // チャンク(3)
            $affected = $database->insertArray('test', [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ], 3);
            // 3件追加したら 3 が返るはず
            $this->assertEquals(3, $affected);
            // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));
        });
        $this->assertEquals([
            "INSERT INTO test (name) VALUES ('a')",
            "INSERT INTO test (name) VALUES ('b')",
            "INSERT INTO test (name) VALUES ('c')",
            "INSERT INTO test (name) VALUES ('a'), ('b')",
            "INSERT INTO test (name) VALUES ('c')",
            "INSERT INTO test (name) VALUES ('a'), ('b'), ('c')",
        ], array_values(preg_grep('#^INSERT#', $logs)));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertArray_misc($database)
    {
        // Generator を返さない callable は例外を投げるはず
        $this->assertException(
            new \InvalidArgumentException('must return Generator instance'),
            L($database)->insertArray('test', function () { return 'hoge'; })
        );

        // $data は 連想配列の配列でなければならないはず
        $this->assertException(
            new \InvalidArgumentException('element must be array'),
            L($database)->insertArray('test', ['dummy'])
        );

        // カラムは最初の要素のキーで合わせられるはず
        $this->assertException(
            new \UnexpectedValueException('columns are not match'),
            L($database)->insertArray('test', [['name' => 1], ['name' => 2, 'data' => 3]])
        );

        // カラムは最初の要素のキーで合わせられるはず
        $this->assertException(
            new \UnexpectedValueException('columns are not match'),
            L($database)->insertArray('test', [['name' => 1, 'data' => 3], ['name' => 2]])
        );

        $affected = $database->dryrun()->insertArray('test', [
            ['name' => 'a'],
            ['name' => new Expression('UPPER(\'b\')')],
            ['name' => new Expression('UPPER(?)', 'c')],
            ['name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
        ]);
        $this->assertStringIgnoreBreak("
INSERT INTO test (name) VALUES
('a'),
(UPPER('b')),
(UPPER('c')),
((SELECT UPPER(name1) FROM test1 WHERE id = '4'))", $affected);

        $affected = $database->dryrun()->insertArray('test', [
            ['name' => 'a'],
            ['name' => new Expression('UPPER(\'b\')')],
            ['name' => new Expression('UPPER(?)', 'c')],
            ['name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
        ], 3);
        $this->assertStringIgnoreBreak("
INSERT INTO test (name) VALUES
('a'),
(UPPER('b')),
(UPPER('c'))", $affected[0]);
        $this->assertStringIgnoreBreak("
INSERT INTO test (name) VALUES
((SELECT UPPER(name1) FROM test1 WHERE id = '4'))", $affected[1]);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_updateArray($database)
    {
        $data = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => new Expression('UPPER(\'b\')')],
            ['id' => 3, 'name' => new Expression('UPPER(?)', 'c')],
            ['id' => 4, 'name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
            ['id' => 5, 'name' => 'nothing'],
            ['id' => 6, 'name' => 'f'],
        ];

        $affected = $database->updateArray('test', $data, ['id <> ?' => 5]);

        // 6件与えているが、変更されるのは4件のはず(mysql の場合。他DBMSは5件)
        $expected = $database->getPlatform() instanceof MySQLPlatform ? 4 : 5;
        $this->assertEquals($expected, $affected);

        // 実際に取得して変わっている/いないを確認
        $this->assertEquals([
            'A',
            'B',
            'C',
            'D',
            'e',
            'f',
        ], $database->selectLists('test.name', [
            'id' => [1, 2, 3, 4, 5, 6],
        ]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_updateArray_chunk($database)
    {
        // ログを見たいので全体を preview で囲む
        $logs = $database->preview(function (Database $database) {
            $data = [
                ['id' => 1, 'name' => 'A'],
                ['id' => 2, 'name' => new Expression('UPPER(\'b\')')],
                ['id' => 3, 'name' => new Expression('UPPER(?)', 'c')],
                ['id' => 4, 'name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
                ['id' => 5, 'name' => 'nothing'],
                ['id' => 6, 'name' => 'f'],
            ];

            $affected = $database->updateArray('test', $data, ['id <> ?' => 5], 2);

            // 6件与えているが、変更されるのは4件のはず(mysql の場合。他DBMSは5件)
            $expected = $database->getPlatform() instanceof MySQLPlatform ? 4 : 5;
            $this->assertEquals($expected, $affected);

            // 実際に取得して変わっている/いないを確認
            $this->assertEquals([
                'A',
                'B',
                'C',
                'D',
                'e',
                'f',
            ], $database->selectLists('test.name', [
                'id' => [1, 2, 3, 4, 5, 6],
            ]));
        });
        $this->assertEquals([
            "UPDATE test SET name = CASE id WHEN 1 THEN 'A' WHEN 2 THEN UPPER('b') ELSE name END WHERE (id <> 5) AND (test.id IN (1, 2))",
            "UPDATE test SET name = CASE id WHEN 3 THEN UPPER('c') WHEN 4 THEN (SELECT UPPER(name1) FROM test1 WHERE id = 4) ELSE name END WHERE (id <> 5) AND (test.id IN (3, 4))",
            "UPDATE test SET name = CASE id WHEN 5 THEN 'nothing' WHEN 6 THEN 'f' ELSE name END WHERE (id <> 5) AND (test.id IN (5, 6))",
        ], array_values(preg_grep('#^UPDATE#', $logs)));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_updateArray_multiple($database)
    {
        $data = [
            ['mainid' => 1, 'subid' => 1, 'name' => 'A'],
            ['mainid' => 1, 'subid' => 2, 'name' => new Expression('UPPER(\'b\')')],
            ['mainid' => 1, 'subid' => 3, 'name' => new Expression('UPPER(?)', 'c')],
            ['mainid' => 1, 'subid' => 4, 'name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
            ['mainid' => 1, 'subid' => 5, 'name' => 'nothing'],
            ['mainid' => 2, 'subid' => 6, 'name' => 'f'],
        ];

        $affected = $database->updateArray('multiprimary', $data, ['NOT (mainid = ? AND subid = ?)' => [1, 5]]);

        // 6件与えているが、変更されるのは4件のはず(mysql の場合。他DBMSは5件)
        $expected = $database->getPlatform() instanceof MySQLPlatform ? 4 : 5;
        $this->assertEquals($expected, $affected);

        // 実際に取得して変わっている/いないを確認
        $this->assertEquals([
            'A',
            'B',
            'C',
            'D',
            'e',
            'f',
        ], $database->selectLists('multiprimary.name', [
            'OR' => [
                [
                    'mainid' => 1,
                    'subid'  => 1,
                ],
                [
                    'mainid' => 1,
                    'subid'  => 2,
                ],
                [
                    'mainid' => 1,
                    'subid'  => 3,
                ],
                [
                    'mainid' => 1,
                    'subid'  => 4,
                ],
                [
                    'mainid' => 1,
                    'subid'  => 5,
                ],
                [
                    'mainid' => 2,
                    'subid'  => 6,
                ],
            ],
        ], ['mainid', 'subid']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_updateArray_misc($database)
    {
        // カラムが混在しててもOK
        $affected = $database->updateArray('test', [
            [
                'id'   => 1,
                'name' => 'hoge',
            ],
            [
                'id'   => 2,
                'name' => 'fuga',
                'data' => 'xxxx',
            ],
        ]);
        $this->assertEquals(2, $affected);

        $this->assertEquals([
            [
                'id'   => 1,
                'name' => 'hoge',
                'data' => '',
            ],
            [
                'id'   => 2,
                'name' => 'fuga',
                'data' => 'xxxx',
            ],
        ], $database->selectArray('test', ['id' => [1, 2]]));

        $affected = $database->updateArray('test', function () {
            foreach (['X', 'Y', 'Z'] as $n => $v) {
                yield ['id' => $n + 1, 'name' => $v];
            }
        });

        $this->assertEquals(3, $affected);
        $this->assertEquals(['X', 'Y', 'Z'], $database->selectLists('test.name', ['id' => [1, 2, 3]]));

        // Generator を返さない callable は例外を投げるはず
        $this->assertException(
            new \InvalidArgumentException('must return Generator instance'),
            L($database)->updateArray('test', function () { return 'hoge'; })
        );

        // $data は 連想配列の配列でなければならないはず
        $this->assertException(
            new \InvalidArgumentException('element must be array'),
            L($database)->updateArray('test', ['dummy'])
        );

        // カラムは主キーを含まなければならないはず
        $this->assertException(
            new \InvalidArgumentException('must be contain primary key'),
            L($database)->updateArray('test', [['name' => 1]])
        );

        // 主キーはスカラーでなければならないはず
        $this->assertException(
            new \InvalidArgumentException('primary key must be scalar value'),
            L($database)->updateArray('test', [['id' => new Expression('1')]])
        );
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyArray($database)
    {
        if (!$database->getCompatiblePlatform()->supportsBulkMerge()) {
            return;
        }

        $data = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => new Expression('UPPER(\'b\')')],
            ['id' => 3, 'name' => new Expression('UPPER(?)', 'c')],
            ['id' => 4, 'name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
            ['id' => 990, 'name' => 'nothing'],
            ['id' => 991, 'name' => 'zzz'],
        ];

        $affected = $database->modifyArray('test', $data);

        // mysql は 4件変更・2件追加で計10affected, sqlite は単純に 6affected
        $expected = null;
        if ($database->getPlatform() instanceof MySQLPlatform) {
            $expected = 10;
        }
        if ($database->getPlatform() instanceof SqlitePlatform) {
            $expected = 6;
        }
        if ($database->getPlatform() instanceof PostgreSQLPlatform) {
            $expected = 6;
        }
        $this->assertEquals($expected, $affected);

        // 実際に取得して変わっている/いないを確認
        $this->assertEquals([
            'A',
            'B',
            'C',
            'D',
            'nothing',
            'zzz',
        ], $database->selectLists('test.name', [
            'id' => [1, 2, 3, 4, 990, 991],
        ]));

        $database->modifyArray('test', [
            ['id' => 1, 'name' => 'X'],
            ['id' => 999, 'name' => 'ZZZ'],
        ], [
            'name' => 'UUU',
        ]);
        $this->assertEquals([
            'UUU',
            'ZZZ',
        ], $database->selectLists('test.name', [
            'id' => [1, 999],
        ]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyArray_chunk($database)
    {
        if (!$database->getCompatiblePlatform()->supportsBulkMerge()) {
            return;
        }

        // ログを見たいので全体を preview で囲む
        $logs = $database->preview(function (Database $database) {
            // チャンク(1)
            $affected = $database->modifyArray('test', [
                ['id' => 1, 'name' => 'U1'],
                ['id' => 2, 'name' => 'U2'],
                ['id' => 93, 'name' => 'A1'],
            ], [], 1);
            // mysql は 2件変更・1件追加で計5affected, sqlite は単純に 3affected
            $expected = null;
            if ($database->getPlatform() instanceof MySQLPlatform) {
                $expected = 5;
            }
            if ($database->getPlatform() instanceof SqlitePlatform) {
                $expected = 3;
            }
            if ($database->getPlatform() instanceof PostgreSQLPlatform) {
                $expected = 3;
            }
            $this->assertEquals($expected, $affected);
            // 実際に取得して変わっている/いないを確認
            $this->assertEquals(['U1', 'U2', 'A1'], $database->selectLists('test.name', ['id' => [1, 2, 93]]));

            // チャンク(2件updateData)
            $affected = $database->modifyArray('test', [
                ['id' => 3, 'name' => 'U1'],
                ['id' => 4, 'name' => 'U2'],
                ['id' => 95, 'name' => 'A1'],
            ], ['name' => 'U'], 2);
            // mysql は 2件変更・1件追加で計5affected, sqlite は単純に 3affected
            $expected = null;
            if ($database->getPlatform() instanceof MySQLPlatform) {
                $expected = 5;
            }
            if ($database->getPlatform() instanceof SqlitePlatform) {
                $expected = 3;
            }
            if ($database->getPlatform() instanceof PostgreSQLPlatform) {
                $expected = 3;
            }
            $this->assertEquals($expected, $affected);
            // 実際に取得して変わっている/いないを確認
            $this->assertEquals(['U', 'U', 'A1'], $database->selectLists('test.name', ['id' => [3, 4, 95]]));

            // チャンク(3)
            $affected = $database->modifyArray('test', [
                ['id' => 3, 'name' => 'U'],
                ['id' => 4, 'name' => 'U1'],
                ['id' => 5, 'name' => 'U2'],
                ['id' => 96, 'name' => 'A1'],
            ], [], 3);
            // mysql は 2件変更・1件追加で計5affected, sqlite は単純に 4affected
            $expected = null;
            if ($database->getPlatform() instanceof MySQLPlatform) {
                $expected = 5;
            }
            if ($database->getPlatform() instanceof SqlitePlatform) {
                $expected = 4;
            }
            if ($database->getPlatform() instanceof PostgreSQLPlatform) {
                $expected = 4;
            }
            $this->assertEquals($expected, $affected);
            // 実際に取得して変わっている/いないを確認
            $this->assertEquals(['U', 'U1', 'U2', 'A1'], $database->selectLists('test.name', ['id' => [3, 4, 5, 96]]));
        });
        $logs = implode("\n", array_values(preg_grep('#^INSERT#', $logs)));
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (1, 'U1') ON", $logs);
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (2, 'U2') ON", $logs);
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (93, 'A1') ON", $logs);
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (3, 'U1'), (4, 'U2') ON", $logs);
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (95, 'A1') ON", $logs);
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (3, 'U'), (4, 'U1'), (5, 'U2') ON", $logs);
        $this->assertStringContainsString("INSERT INTO test (id, name) VALUES (96, 'A1') ON", $logs);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyArray_misc($database)
    {
        if (!$database->getCompatiblePlatform()->supportsBulkMerge()) {
            return;
        }

        $manager = $this->scopeManager(function () use ($database) {
            $cplatform = $database->getCompatiblePlatform();
            $cache = $this->forcedRead($database, 'cache');
            $cache['compatiblePlatform'] = new class($cplatform->getWrappedPlatform()) extends CompatiblePlatform {
                public function supportsIdentityAutoUpdate() { return false; }
            };
            $this->forcedWrite($database, 'cache', $cache);
            return function () use ($database, $cache, $cplatform) {
                $cache['compatiblePlatform'] = $cplatform;
                $this->forcedWrite($database, 'cache', $cache);
            };
        });

        $pk = $database->modifyArray('test', [
            ['id' => 98, 'name' => 'xx'],
            ['id' => 99, 'name' => 'yy'],
        ]);
        $this->assertEquals(2, $pk);
        unset($manager);

        $manager = $this->scopeManager(function () use ($database) {
            $cplatform = $database->getCompatiblePlatform();
            $cache = $this->forcedRead($database, 'cache');
            $cache['compatiblePlatform'] = new class($cplatform->getWrappedPlatform()) extends CompatiblePlatform {
                public function supportsBulkMerge() { return false; }
            };
            $this->forcedWrite($database, 'cache', $cache);
            return function () use ($database, $cache, $cplatform) {
                $cache['compatiblePlatform'] = $cplatform;
                $this->forcedWrite($database, 'cache', $cache);
            };
        });

        $this->assertException('is not support modifyArray', L($database)->modifyArray('test', []));
        unset($manager);

        $this->assertException('must be array', L($database->dryrun())->modifyArray('test', ['dummy']));
        $this->assertException('columns are not match', L($database->dryrun())->modifyArray('test', [['id' => 1], ['name' => 2]]));

        $data = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => new Expression('UPPER(\'b\')')],
            ['id' => 3, 'name' => new Expression('UPPER(?)', 'c')],
            ['id' => 4, 'name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
            ['id' => 990, 'name' => 'nothing'],
            ['id' => 991, 'name' => 'zzz'],
        ];

        $merge = function ($columns) use ($database) { return $database->getCompatiblePlatform()->getMergeSyntax($columns); };
        $refer = function ($column) use ($database) { return $database->getCompatiblePlatform()->getReferenceSyntax($column); };

        $affected = $database->dryrun()->modifyArray('test', $data);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'A'),
('2', UPPER('b')),
('3', UPPER('c')),
('4', (SELECT UPPER(name1) FROM test1 WHERE id = '4')),
('990', 'nothing'),
('991', 'zzz')
{$merge(['id'])} id = {$refer('id')}, name = {$refer('name')}", $affected);

        $affected = $database->dryrun()->modifyArray('test', $data, ['name' => 'hoge']);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'A'),
('2', UPPER('b')),
('3', UPPER('c')),
('4', (SELECT UPPER(name1) FROM test1 WHERE id = '4')),
('990', 'nothing'),
('991', 'zzz')
{$merge(['id'])} name = 'hoge'", $affected);

        $affected = $database->dryrun()->modifyArray('test', $data, ['name' => 'hoge'], 4);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'A'),
('2', UPPER('b')),
('3', UPPER('c')),
('4', (SELECT UPPER(name1) FROM test1 WHERE id = '4'))
{$merge(['id'])} name = 'hoge'", $affected[0]);

        $affected = $database->dryrun()->modifyArray('test', $data, ['name' => 'hoge'], 4);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('990', 'nothing'),
('991', 'zzz')
{$merge(['id'])} name = 'hoge'", $affected[1]);

        $affected = $database->dryrun()->modifyArray('test', function () {
            foreach (['X', 'Y', 'Z'] as $n => $v) {
                yield ['id' => $n + 1, 'name' => $v];
            }
        });
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'X'),
('2', 'Y'),
('3', 'Z')
{$merge(['id'])} id = {$refer('id')}, name = {$refer('name')}", $affected);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_createConditionally($database)
    {
        $result = $database->createConditionally('test', ['id' => 1], [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals([], $result);

        $result = $database->createConditionally('test', ['id' => 100], [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $sql = $database->dryrun()->createConditionally('test', ['id' => 1], [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertStringContainsString('INSERT INTO test (id, name) SELECT', $sql);
        $this->assertStringContainsString('WHERE (NOT EXISTS (SELECT * FROM test WHERE id =', $sql);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insert($database)
    {
        // simple
        $database->insert('test', [
            'name' => 'xx',
        ]);
        $this->assertEquals('xx', $database->selectValue('test.name', [], ['id' => 'desc'], 1));

        // into
        $database->insert('test', [
            'name' => new Expression('UPPER(?)', 'lower'),
        ]);
        $this->assertEquals('LOWER', $database->selectValue('test.name', [], ['id' => 'desc'], 1));

        // for mysql
        if ($database->getCompatiblePlatform()->supportsInsertSet()) {
            $sql = $database->context(['insertSet' => true])->dryrun()->insert('test', ['name' => 'zz']);
            $this->assertEquals("INSERT INTO test SET name = 'zz'", $sql);

            if ($database->getPlatform() instanceof MySQLPlatform) {
                $database->executeAffect($sql);
                $this->assertEquals('zz', $database->selectValue('test.name', [], ['id' => 'desc'], 1));
            }
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertConditionally($database)
    {
        $result = $database->insertConditionally('test', ['id' => 1], [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals([], $result);

        $result = $database->insertConditionally('test', ['id' => 100], [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $sql = $database->dryrun()->insertConditionally('test', ['id' => 1], [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertStringContainsString('INSERT INTO test (id, name) SELECT', $sql);
        $this->assertStringContainsString('WHERE (NOT EXISTS (SELECT * FROM test WHERE id =', $sql);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insert_column($database)
    {
        // 1カラム
        $this->assertEquals(1, $database->insert('test.name', 'XXX'));
        $this->assertEquals('XXX', $database->selectValue('test.name', [], ['id' => 'desc'], 1));

        // 複数カラム
        $this->assertEquals(1, $database->insert('test.name, data', ['YYY', 'ZZZ']));
        $this->assertEquals(['name' => 'YYY', 'data' => 'ZZZ'], $database->selectTuple('test.!id', [], ['id' => 'desc'], 1));

        // 複数カラム(配列)
        $this->assertEquals(1, $database->insert([
            'test' => ['name', 'data'],
        ], ['EEE', 'RRR']));
        $this->assertEquals(['name' => 'EEE', 'data' => 'RRR'], $database->selectTuple('test.!id', [], ['id' => 'desc'], 1));

        // 複数テーブル
        $this->assertEquals(2, $database->insert([
            'foreign_p P' => [
                'id'   => 99,
                'name' => 'hoge',
            ],
            '+foreign_c1' => [
                'name' => new Expression("UPPER('fuga')"),
            ],
        ], ['seq' => 9]));
        $this->assertEquals([
            [
                'id'    => '99',
                'pname' => 'hoge',
                'name'  => 'FUGA',
                'seq'   => '9',
            ],
        ], $database->selectArray('foreign_p(99).*, name pname + foreign_c1.*'));

        $sqls = $database->dryrun()->insert([
            'foreign_p P' => [
                'id'   => 99,
                'name' => 'hoge',
            ],
            '+foreign_c1' => [
                'name' => new Expression("UPPER('fuga')"),
            ],
        ], ['seq' => 9]);
        $this->assertStringContainsString("INTO foreign_p (id, name) VALUES (99, 'hoge')", $sqls[0]);
        $this->assertStringContainsString("INTO foreign_c1 (seq, name, id) VALUES ('9', UPPER('fuga'), '99')", $sqls[1]);

        $this->assertException(new \InvalidArgumentException('specify multiple table'), L($database)->insert('test1,test2', ['X']));
        $this->assertException(new \InvalidArgumentException('data array are difference'), L($database)->insert('test.name', ['X', 'Y']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insert_join($database)
    {
        $result = $database->insert('horizontal1 < horizontal2', [
            'name'    => 'name1',
            'summary' => 'summary1',
        ]);
        $this->assertEquals(2, $result);
        $this->assertEquals([
            'name'    => 'name1',
            'summary' => 'summary1',
        ], $database->selectTuple('horizontal1.name + horizontal2.summary', [], [], 1));

        $result = $database->insertOrThrow('horizontal1 + horizontal2', [
            'name'    => 'name2',
            'summary' => 'summary2',
        ]);
        $this->assertIsArray($result);
        $result = reset($result);
        $this->assertEquals([
            'name'    => 'name2',
            'summary' => 'summary2',
        ], $database->selectTuple("horizontal1($result).name + horizontal2.summary", [], [], 1));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_update($database)
    {
        // 連想配列
        $affected = $database->update('test', [
            'name' => 'xx',
        ], [
            'id <= ?' => 2,
        ]);
        $this->assertEquals(2, $affected);

        // フラット配列
        $affected = $database->update('test', [
            'name' => 'yy',
        ], [
            'id <= 2',
        ]);
        $this->assertEquals(2, $affected);

        // key = value
        $affected = $database->update('test', [
            'name' => 'YY',
        ], [
            'id' => 2,
        ]);
        $this->assertEquals(1, $affected);

        // 文字列 where
        $affected = $database->update('test', [
            'name' => 'HH',
        ], '1=1');
        $this->assertEquals(10, $affected);

        // 条件なし1 where
        $affected = $database->update('test', [
            'name' => 'zz',
        ], []);
        $this->assertEquals(10, $affected);
        // 条件なし2 where
        $affected = $database->update('test', [
            'name' => 'ZZ',
        ]);
        $this->assertEquals(10, $affected);

        // into
        $affected = $database->update('test', [
            'name' => new Expression('UPPER(?)', 'lower'),
        ], [
            'id = 1',
        ]);
        $this->assertEquals(1, $affected);
        $this->assertEquals('LOWER', $database->fetchValue('select name from test where id = 1'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_update_column($database)
    {
        // 1カラム
        $this->assertEquals(1, $database->update('test.name', 'XXX', ['id' => 1]));
        $this->assertEquals('XXX', $database->selectValue('test.name', ['id' => 1]));

        // 1カラム(Expression)
        $this->assertEquals(1, $database->update('test.name', new Expression('LOWER(name)'), ['id' => 1]));
        $this->assertEquals('xxx', $database->selectValue('test.name', ['id' => 1]));

        // 複数カラム
        $this->assertEquals(1, $database->update('test.name, data', ['YYY', 'ZZZ'], ['id' => 1]));
        $this->assertEquals(['name' => 'YYY', 'data' => 'ZZZ'], $database->selectTuple('test.!id', ['id' => 1]));

        // 複数カラム(配列)
        $this->assertEquals(1, $database->update([
            'test' => ['name', 'data'],
        ], ['EEE', 'RRR'], ['id' => 1]));
        $this->assertEquals(['name' => 'EEE', 'data' => 'RRR'], $database->selectTuple('test.!id', ['id' => 1]));

        // 普通はこういうことはせず、 JOIN 時のみ有用なので mysql だけでテストする
        if ($database->getPlatform() instanceof MySQLPlatform) {
            $database->insert('foreign_p', ['id' => 1, 'name' => 'pname']);
            $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1name1']);
            $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'c1name2']);
            $database->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'c2name1']);
            $database->insert('foreign_c2', ['cid' => 1, 'seq' => 2, 'name' => 'c2name2']);
            $this->assertEquals(3, $database->update([
                'foreign_p(1)'   => [
                    'name' => 'hoge',
                ],
                '+foreign_c1 C1' => [
                    'name' => new Expression('UPPER(C1.name)'),
                ],
            ], []));
            $this->assertEquals([
                [
                    'id'    => 1,
                    'pname' => 'hoge',
                    'name'  => 'C1NAME1',
                    'seq'   => 1,
                ],
                [
                    'id'    => 1,
                    'pname' => 'hoge',
                    'name'  => 'C1NAME2',
                    'seq'   => 2,
                ],
            ], $database->selectArray('foreign_p(1).*, name pname + foreign_c1.*'));
        }

        $this->assertException(new \InvalidArgumentException('specify multiple table'), L($database)->update('test1,test2', ['X']));
        $this->assertException(new \InvalidArgumentException('data array are difference'), L($database)->update('test.name', ['X', 'Y']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_update_join($database)
    {
        if ($database->getPlatform() instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            $this->assertEquals('UPDATE test T LEFT JOIN test1 T1 ON T.id = T1.id SET name = UPPER(T1.name1) WHERE 1=1', $database->dryrun()->update([
                'test T',
                '<test1 T1' => [['T.id = T1.id']],
            ], ["name" => new Expression('UPPER(T1.name1)')], '1=1'));
        }
        if ($database->getCompatiblePlatform()->supportsUpdateJoin()) {
            $affected = $database->update([
                'test T',
                '<test1 T1' => [['T.id = T1.id']],
            ], ["name" => new Expression('UPPER(T1.name1)')], '1=1');
            $this->assertEquals(10, $affected);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_update_descriptor($database)
    {
        $this->assertEquals(1, $database->update('test(2)', ['name' => 'XXX']));
        $this->assertEquals('XXX', $database->selectValue('test(2).name'));

        $this->assertEquals(2, $database->update('test(1, 2, 3)', ['name' => 'YYY'], ['id <> ?' => 2]));
        $this->assertEquals('YYY', $database->selectValue('test(1).name'));
        $this->assertEquals('XXX', $database->selectValue('test(2).name'));
        $this->assertEquals('YYY', $database->selectValue('test(3).name'));

        if (!$database->getPlatform() instanceof SqlitePlatform && !$database->getPlatform() instanceof SQLServerPlatform) {
            $this->assertEquals(1, $database->update('test(2) as T', ['name' => 'ZZZ']));
            $this->assertEquals('ZZZ', $database->selectValue('test(2).name'));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_delete($database)
    {
        // 連想配列
        $affected = $database->delete('test', [
            'id <= ?' => 2,
        ]);
        $this->assertEquals(2, $affected);

        // フラット配列
        $affected = $database->delete('test', [
            'id > 8',
        ]);
        $this->assertEquals(2, $affected);

        // key = value
        $affected = $database->delete('test', [
            'id' => 5,
        ]);
        $this->assertEquals(1, $affected);

        // 文字列指定
        $affected = $database->delete('test', '1=1');
        $this->assertEquals(5, $affected);

        // 条件なし1
        $affected = $database->delete('test1', []);
        $this->assertEquals(10, $affected);
        $affected = $database->delete('test2');
        $this->assertEquals(20, $affected);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_delete_join($database)
    {
        if ($database->getCompatiblePlatform()->supportsDeleteJoin()) {
            $database->delete('test1', 'id = 1');
            $affected = $database->delete('test1.*', 'id = 2');
            $this->assertEquals(1, $affected);
            $affected = $database->delete([
                'test T',
                '+test1 T1' => [['T.id = T1.id']],
            ], '1=1');
            $this->assertEquals(8, $affected);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_delete_descriptor($database)
    {
        $count = $database->count('test');

        $this->assertEquals(1, $database->delete('test(2)'));
        $this->assertEquals($count - 1, $database->count('test'));

        $this->assertEquals(2, $database->delete('test(1, 2, 3)', ['id <> ?' => 2]));
        $this->assertEquals($count - 3, $database->count('test'));

        if (!$database->getPlatform() instanceof SqlitePlatform) {
            $this->assertEquals(1, $database->delete('test(4) AS T'));
            $this->assertEquals($count - 4, $database->count('test'));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_remove($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_p', ['id' => 4, 'name' => 'name4']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 21, 'name' => 'c2name1']);

        $affected = $database->remove('foreign_p', [
            'id' => [1, 2, 3],
        ]);

        // 1, 2 は子供で使われていて 4 は指定していない。結果 3 しか消えない
        $this->assertEquals(1, $affected);

        // 実際に取得してみて担保する
        $this->assertEquals([
            ['id' => 1, 'name' => 'name1'],
            ['id' => 2, 'name' => 'name2'],
            ['id' => 4, 'name' => 'name4'],
        ], $database->selectArray('foreign_p'));

        if ($database->getPlatform() instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            $logs = [];
            $logger = new Logger([
                'destination' => function ($sql, $params) use (&$logs) {
                    $logs[] = compact('sql', 'params');
                },
                'level'       => 'debug',
                'metadata'    => [],
            ]);
            try {
                $database->setLogger($logger);
                $database->remove('foreign_p P < foreign_c1 C', ['C.id' => 99]);
            }
            catch (\Exception $ex) {
                $last = reset($logs);
                $this->assertEquals('DELETE P FROM foreign_p P LEFT JOIN foreign_c1 C ON C.id = P.id WHERE (C.id = ?) AND ((NOT EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = P.id))) AND ((NOT EXISTS (SELECT * FROM foreign_c2 WHERE foreign_c2.cid = P.id)))', $last['sql']);
            }
            $database->setLogger(null);
        }

        // 相互外部キー
        $this->assertEquals('DELETE FROM foreign_d1 WHERE (NOT EXISTS (SELECT * FROM foreign_d2 WHERE foreign_d2.id = foreign_d1.id))', $database->dryrun()->remove('foreign_d1'));
        $this->assertEquals('DELETE FROM foreign_d2 WHERE (NOT EXISTS (SELECT * FROM foreign_d1 WHERE foreign_d1.d2_id = foreign_d2.id))', $database->dryrun()->remove('foreign_d2'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_destroy($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_p', ['id' => 4, 'name' => 'name4']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 21, 'name' => 'c2name1']);
        $database->insert('foreign_c2', ['cid' => 4, 'seq' => 41, 'name' => 'c4name1']);

        $affected = $database->destroy('foreign_p', [
            'id' => [1, 2, 3],
        ]);

        // 1, 2 は子供で使われているが強制削除される。 4 は指定していない。結果 4 が残る
        $this->assertEquals(5, $affected);

        // 実際に取得してみて担保する
        $this->assertEquals([
            ['id' => 4, 'name' => 'name4'],
        ], $database->selectArray('foreign_p'));
        $this->assertEquals([
            ['cid' => 4, 'seq' => 41, 'name' => 'c4name1'],
        ], $database->selectArray('foreign_c2'));

        // dryrun はクエリ配列を返す
        $this->assertEquals([
            "DELETE FROM foreign_c1 WHERE (id) IN (SELECT foreign_p.id FROM foreign_p WHERE name = 'name4')",
            "DELETE FROM foreign_c2 WHERE (cid) IN (SELECT foreign_p.id FROM foreign_p WHERE name = 'name4')",
            "DELETE FROM foreign_p WHERE name = 'name4'",
        ], $database->dryrun()->destroy('foreign_p', ['name' => 'name4']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_destroy_in($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_p', ['id' => 4, 'name' => 'name4']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 21, 'name' => 'c2name1']);
        $database->insert('foreign_c2', ['cid' => 4, 'seq' => 41, 'name' => 'c4name1']);

        $affected = $database->destroy('foreign_p', [
            'id' => [1, 2, 3],
        ], ['in' => true]);

        // 1, 2 は子供で使われているが強制削除される。 4 は指定していない。結果 4 が残る
        $this->assertEquals(5, $affected);

        // 実際に取得してみて担保する
        $this->assertEquals([
            ['id' => 4, 'name' => 'name4'],
        ], $database->selectArray('foreign_p'));
        $this->assertEquals([
            ['cid' => 4, 'seq' => 41, 'name' => 'c4name1'],
        ], $database->selectArray('foreign_c2'));

        // dryrun はクエリ配列を返す
        $this->assertEquals([
            "DELETE FROM foreign_c1 WHERE foreign_c1.id = '4'",
            "DELETE FROM foreign_c2 WHERE foreign_c2.cid = '4'",
            "DELETE FROM foreign_p WHERE name = 'name4'",
        ], $database->dryrun()->destroy('foreign_p', ['name' => 'name4'], ['in' => true]));
        // 親がいない場合に FALSE になるか担保する
        $this->assertEquals([
            "DELETE FROM foreign_c1 WHERE FALSE",
            "DELETE FROM foreign_c2 WHERE FALSE",
            "DELETE FROM foreign_p WHERE name = 'name3'",
        ], $database->dryrun()->destroy('foreign_p', ['name' => 'name3'], ['in' => true]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_reduce($database)
    {
        // テーブル全体で log_date 昇順で 5 件残す
        $database->begin();
        $this->assertEquals(160, $database->reduce('oprlog', 5, 'log_date'));
        $this->assertEquals(5, $database->count('oprlog'));
        $this->assertEquals([
            '2001-01-01',
            '2002-01-01',
            '2002-02-01',
            '2002-02-02',
            '2003-01-01',
        ], $database->selectLists('oprlog.log_date'));
        $database->rollback();

        // テーブル全体で log_date 降順で 5 件残す
        $database->begin();
        $this->assertEquals(160, $database->reduce('oprlog', 5, '-log_date'));
        $this->assertEquals(5, $database->count('oprlog'));
        $this->assertEquals([
            '2009-09-09',
            '2009-09-08',
            '2009-09-07',
            '2009-09-06',
            '2009-09-05',
        ], $database->selectLists('oprlog-log_date.log_date'));
        $database->rollback();

        // category でグルーピングして log_date 昇順で 1 件残す
        $database->begin();
        $this->assertEquals(156, $database->reduce('oprlog', 1, 'log_date', ['category']));
        $this->assertEquals(9, $database->count('oprlog'));
        $this->assertEquals([
            '2001-01-01',
            '2002-01-01',
            '2003-01-01',
            '2004-01-01',
            '2005-01-01',
            '2006-01-01',
            '2007-01-01',
            '2008-01-01',
            '2009-01-01',
        ], $database->selectLists('oprlog.log_date'));
        $database->rollback();

        // category でグルーピングして log_date 降順で 1 件残す
        $database->begin();
        $this->assertEquals(156, $database->reduce('oprlog', 1, '-log_date', ['category']));
        $this->assertEquals(9, $database->count('oprlog'));
        $this->assertEquals([
            '2001-01-01',
            '2002-02-02',
            '2003-03-03',
            '2004-04-04',
            '2005-05-05',
            '2006-06-06',
            '2007-07-07',
            '2008-08-08',
            '2009-09-09',
        ], $database->selectLists('oprlog.log_date'));
        $database->rollback();

        // category, primary_id でグルーピングして log_date 昇順で 5 件残す
        $database->begin();
        $this->assertEquals(20, $database->reduce('oprlog', 5, 'log_date', ['category', 'primary_id']));
        $this->assertEquals(1, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals(2, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals(3, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals(4, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-02", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-03", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-04", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $database->rollback();

        // category, primary_id でグルーピングして log_date 降順で 5 件残す
        $database->begin();
        $this->assertEquals(20, $database->reduce('oprlog', 5, '-log_date', ['category', 'primary_id']));
        $this->assertEquals(1, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals(2, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals(3, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals(4, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-02", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-03", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-04", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-05", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-02", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-03", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-04", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-06", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-07", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-08", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-09", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $database->rollback();

        // category, primary_id でグルーピングして log_date 降順で 5 件残す。ただし、2009-07-04 以降は残す（2009-07-04 以前のみ削除する）
        $database->begin();
        $this->assertEquals(11, $database->reduce('oprlog', 5, '-log_date', ['category', 'primary_id'], ['log_date < ?' => '2009-07-04']));
        $this->assertEquals(1, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals(2, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals(3, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals(4, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals(7, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals(8, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals(9, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-02", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-02", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-03", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-04", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-06", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-07", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-08", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-09", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $database->rollback();

        // ↑のクエリビルダ版
        $database->begin();
        $select = $database->select('oprlog-log_date#-5')->where(['log_date < ?' => '2009-07-04'])->groupBy('category', 'primary_id');
        $this->assertEquals(11, $database->reduce($select));
        $this->assertEquals(1, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals(2, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals(3, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals(4, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals(5, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals(7, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals(8, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals(9, $database->count('oprlog', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-02", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-01", $database->min('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $this->assertEquals("2009-01-01", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 1]));
        $this->assertEquals("2009-02-02", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 2]));
        $this->assertEquals("2009-03-03", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 3]));
        $this->assertEquals("2009-04-04", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 4]));
        $this->assertEquals("2009-05-05", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 5]));
        $this->assertEquals("2009-06-06", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 6]));
        $this->assertEquals("2009-07-07", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 7]));
        $this->assertEquals("2009-08-08", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 8]));
        $this->assertEquals("2009-09-09", $database->max('oprlog.log_date', ['category' => 'category-9', 'primary_id' => 9]));
        $database->rollback();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_reduce_misc($database)
    {
        $database->begin();
        $this->assertEquals(165, $database->reduce('oprlog', 0, 'id'));
        $database->rollback();

        $database->begin();
        $this->assertEquals(135, $database->reduce('oprlog', 0, '-log_date', ['category'], ['log_date < ?' => '2009-06-01']));
        $database->rollback();

        $database->begin();
        $this->assertEquals(40, $database->reduce('oprlog', 5, 'id', [], ['category' => 'category-9']));
        $database->rollback();

        $database->begin();
        $this->assertEquals(130, $database->reduce('oprlog', 5, '-log_date', [], ['log_date < ?' => '2009-06-01']));
        $database->rollback();

        $database->begin();
        $this->assertEquals(96, $database->reduce('oprlog', 5, '-log_date', ['category'], ['log_date < ?' => '2009-06-01']));
        $database->rollback();

        $database->begin();
        $this->assertEquals(10, $database->reduce('oprlog', 5, '-log_date', ['category', 'primary_id'], ['category' => 'category-9']));
        $database->rollback();

        $this->assertException('must be >= 0', L($database)->reduceOrThrow('oprlog', -1));
        $this->assertException('must be === 1', L($database)->reduceOrThrow('oprlog', 1, ['a' => true, 'b' => false]));
        $this->assertException('affected row is nothing', L($database)->reduceOrThrow('oprlog', 5, 'log_date', [], ['1=0']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upsert($database)
    {
        $current = $database->count('test');

        $row = [
            'id'   => 2,
            'name' => 'xx',
            'data' => '',
        ];
        $database->upsert('test', $row);

        // 全く同じのはず
        $this->assertEquals($row, $database->fetchTuple('select * from test where id = 2'));
        // 同じ件数のはず
        $this->assertEquals($current, $database->count('test'));

        $row = [
            'id'   => 999,
            'name' => 'xx',
            'data' => '',
        ];
        $database->upsert('test', $row);

        // 全く同じのはず
        $this->assertEquals($row, $database->fetchTuple('select * from test where id = 999'));
        // 件数が+1されているはず
        $this->assertEquals($current + 1, $database->count('test'));

        $row = [
            'name' => 'zz',
            'data' => '',
        ];
        $database->upsert('test', $row);

        // 件数が+1されているはず
        $this->assertEquals($current + 2, $database->count('test'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upsert2($database)
    {
        $row1 = [
            'id'   => 2,
            'name' => 'xx',
            'data' => '',
        ];
        $row2 = [
            'name' => 'zz',
            'data' => '',
        ];
        $database->upsert('test', $row1, $row2);

        // $row2 で「更新」されているはず
        $this->assertEquals($row2 + ['id' => 2], $database->fetchTuple('select * from test where id = 2'));

        $row1 = [
            'id'   => 999,
            'name' => 'xx',
            'data' => '',
        ];
        $row2 = [
            'id'   => 999,
            'name' => 'zz',
            'data' => '',
        ];
        $database->upsert('test', $row1, $row2);

        // $row1 が「挿入」されているはず
        $this->assertEquals($row1, $database->fetchTuple('select * from test where id = 999'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upsert_column($database)
    {
        // 複数カラム
        $this->assertEquals(2, $database->upsert('test.id, name', [1, 'YYY']));
        $this->assertEquals('YYY', $database->selectValue('test.name', ['id' => 1]));

        // updateData 指定
        // sqlserver はID列を更新できない
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $this->assertEquals(2, $database->upsert('test.id, name', [1, 'YYY'], [1, 'ZZZ']));
            $this->assertEquals('ZZZ', $database->selectValue('test.name', ['id' => 1]));
        }

        $this->assertException(new \InvalidArgumentException('is not supported Q'), L($database)->upsert('test.name', ['X' => 'Y']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upsert_ex($database)
    {
        $this->assertException(new \UnexpectedValueException('no match primary key'), L($database)->upsert('noauto', ['name' => 'xx']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upsertOrThrow($database)
    {
        $row = [
            'id'   => 2,
            'name' => 'qq',
            'data' => '',
        ];

        // 更新された時はそのID値が返るはず
        $this->assertEquals(['id' => 2], $database->upsertOrThrow('test', $row));
        $this->assertEquals($row, $database->fetchTuple('select * from test where id = 2'));

        $row = [
            'name' => 'qq',
            'data' => '',
        ];

        // 挿入された時はそのAUTOINCREMENTの値が返るはず
        $this->assertEquals(['id' => 11], $database->upsertOrThrow('test', $row));
        $this->assertEquals($row + ['id' => 11], $database->fetchTuple('select * from test where id = 11'));

        $row = [
            'id'   => 1,
            'name' => 'qq',
            'data' => '',
        ];
        $row2 = ['id' => 99] + $row;

        // sqlserver はID列を更新できない
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            // ちょっと複雑だが、$row を insert しようとするが、[id=1] は既に存在するので、update の動作となる
            // その場合、その存在する行を $row2 で更新するので [id=1] は消えてなくなり、[id=99] に生まれ変わる
            // したがってその「更新された行のID」は99が正のはず
            $this->assertEquals(['id' => 99], $database->upsertOrThrow('test', $row, $row2));
            $this->assertEquals(false, $database->fetchTuple('select * from test where id = 1'));
            $this->assertEquals($row2, $database->fetchTuple('select * from test where id = 99'));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upsertConditionally($database)
    {
        $result = $database->upsertConditionally('test', ['id' => 1], [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals([], $result);

        $result = $database->upsertConditionally('test', ['id' => 100], [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $result = $database->upsertConditionally('test', ['id' => -1], [
            'id'   => 10,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 10], $result);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modify($database)
    {
        $database->modify('test', ['name' => 'newN', 'data' => 'newD']);
        $id = $database->getLastInsertId('test', 'id');
        $this->assertEquals(11, $id);
        $this->assertEquals(['name' => 'newN', 'data' => 'newD'], $database->selectTuple('test.name,data', ['id' => $id]));

        $database->modify('test', ['id' => $id, 'name' => 'repN', 'data' => 'repD']);
        $this->assertEquals(['name' => 'repN', 'data' => 'repD'], $database->selectTuple('test.name,data', ['id' => $id]));

        $database->modify('test', ['id' => $id, 'name' => 'repN', 'data' => 'repD'], ['name' => 'upN', 'data' => 'upD']);
        $this->assertEquals(['name' => 'upN', 'data' => 'upD'], $database->selectTuple('test.name,data', ['id' => $id]));

        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $database->modify(['test' => ['id', 'name']], [$id, 'repN2'], [$id, 'repN3']);
            $this->assertEquals(['name' => 'repN3', 'data' => 'upD'], $database->selectTuple('test.name,data', ['id' => $id]));
        }

        $this->assertException(new \InvalidArgumentException('is not supported Q'), L($database)->modify('test.name', ['X' => 'Y']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyOrThrow($database)
    {
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            // 普通にやれば次の連番が返るはず
            $primary = $database->modifyOrThrow('test', ['name' => 'modify1']);
            $this->assertEquals(['id' => 11], $primary);

            // null も数値で返るはず(mysqlのみ)
            if ($database->getPlatform() instanceof MySQLPlatform) {
                $primary = $database->modifyOrThrow('test', ['id' => null, 'name' => 'modify2']);
                $this->assertEquals(['id' => 12], $primary);
            }

            // Expression も数値で返るはず
            $primary = $database->modifyOrThrow('test', ['id' => new Expression('?', 13), 'name' => 'modify3_1']);
            $this->assertEquals(['id' => 13], $primary);
            $primary = $database->modifyOrThrow('test', ['id' => new Expression('?', 13), 'name' => 'modify3_2']);
            $this->assertEquals(['id' => 13], $primary);

            // QueryBuilder も数値で返るはず
            $primary = $database->modifyOrThrow('test', ['id' => $database->select(['test T' => 'id+100'], ['id' => 1]), 'name' => 'modify4_1']);
            $this->assertEquals(['id' => 101], $primary);
            $primary = $database->modifyOrThrow('test', ['id' => $database->select(['test T' => 'id'], ['id' => 1]), 'name' => 'modify4_2']);
            $this->assertEquals(['id' => 1], $primary);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyConditionally($database)
    {
        $result = $database->modifyConditionally('test', ['id' => 1], [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals([], $result);

        $result = $database->modifyConditionally('test', ['id' => 100], [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $result = $database->modifyConditionally('test', ['id' => -1], [
            'id'   => 10,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 10], $result);

        if ($database->getPlatform() instanceof MySQLPlatform) {
            $sql = $database->dryrun()->modifyConditionally('test', ['id' => -1], [
                'id'   => 10,
                'name' => 'zzz',
            ]);
            $this->assertStringContainsString('INSERT INTO test (id, name) SELECT', $sql);
            $this->assertStringContainsString('WHERE (NOT EXISTS (SELECT * FROM test WHERE id =', $sql);
            $this->assertStringContainsString('ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name)', $sql);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modify_misc($database)
    {
        $manager = $this->scopeManager(function () use ($database) {
            return function () use ($database) {
                $database->setConvertEmptyToNull(true);
                $database->setInsertSet(false);
            };
        });

        $database->setConvertEmptyToNull(true);

        $database->delete('nullable');

        // 空文字は空文字でも文字列型はそのまま空文字、数値型は null になるはず
        $pk = $database->insertOrThrow('nullable', ['name' => '', 'cint' => '', 'cfloat' => '', 'cdecimal' => '']);
        $row = $database->selectTuple('nullable.!id', $pk);
        $this->assertSame(['name' => '', 'cint' => null, 'cfloat' => null, 'cdecimal' => null], $row);

        $database->setConvertEmptyToNull(false);

        // DBMS によって挙動が違うし、そもそもエラーになるものもあるので mysql のみ
        if ($database->getConnection()->getDatabasePlatform() instanceof MySQLPlatform) {
            $mode = $database->fetchValue('SELECT @@SESSION.sql_mode');
            $database->executeAffect("SET @@SESSION.sql_mode := ''");
            $pk = $database->insertOrThrow('nullable', ['name' => '', 'cint' => '', 'cfloat' => '', 'cdecimal' => '']);
            $row = $database->selectTuple('nullable.!id', $pk);
            $this->assertNotNull($row['cint']);
            $this->assertNotNull($row['cfloat']);
            $this->assertNotNull($row['cdecimal']);
            $database->executeAffect("SET @@SESSION.sql_mode := '$mode'");
        }

        $database->setInsertSet(true);
        if ($database->getCompatiblePlatform()->supportsInsertSet()) {
            $sql = $database->dryrun()->modifyOrThrow('test', ['name' => 'zz']);
            $this->assertStringContainsString("INSERT INTO test SET name = 'zz' ", $sql);
        }
        unset($manager);

        $manager = $this->scopeManager(function () use ($database) {
            $cplatform = $database->getCompatiblePlatform();
            $cache = $this->forcedRead($database, 'cache');
            $cache['compatiblePlatform'] = new class($cplatform->getWrappedPlatform()) extends CompatiblePlatform {
                public function supportsIdentityAutoUpdate() { return false; }
            };
            $this->forcedWrite($database, 'cache', $cache);
            return function () use ($database, $cache, $cplatform) {
                $cache['compatiblePlatform'] = $cplatform;
                $this->forcedWrite($database, 'cache', $cache);
            };
        });

        $pk = $database->modifyOrThrow('test', ['id' => 99, 'name' => 'xx']);
        $this->assertEquals(['id' => 99], $pk);
        unset($manager);

        $manager = $this->scopeManager(function () use ($database) {
            $cplatform = $database->getCompatiblePlatform();
            $cache = $this->forcedRead($database, 'cache');
            $cache['compatiblePlatform'] = new class($cplatform->getWrappedPlatform()) extends CompatiblePlatform {
                public function supportsMerge() { return false; }
            };
            $this->forcedWrite($database, 'cache', $cache);
            return function () use ($database, $cache, $cplatform) {
                $cache['compatiblePlatform'] = $cplatform;
                $this->forcedWrite($database, 'cache', $cache);
            };
        });

        $this->assertEquals(1, $database->modify('test', ['id' => 100, 'name' => 'z']));
        unset($manager);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_changeArray($database)
    {
        // 空のテスト1
        $database->changeArray('multiprimary', [], ['mainid' => 1]);
        $this->assertEmpty($database->selectArray('multiprimary', ['mainid' => 1]));
        $this->assertCount(5, $database->selectArray('multiprimary', ['mainid' => 2]));

        // 空のテスト2
        $database->changeArray('multiprimary', [], ['mainid' => 2, 'subid = 7']);
        $this->assertCount(4, $database->selectArray('multiprimary', ['mainid' => 2]));

        // バルク兼プリペアのテスト
        $max = $database->max('test.id');

        $primaries = $database->changeArray('test', [
            // bulk
            ['id' => 1, 'name' => 'changeArray:bulk1'],
            ['id' => 2, 'name' => 'changeArray:bulk2'],
            // prepare
            ['id' => null, 'name' => 'changeArray:prepare1'],
            ['id' => null, 'name' => 'changeArray:prepare2'],
            // perrow
            ['id' => null, 'name' => 'changeArray:perrow1'],
            ['id' => null, 'name' => 'changeArray:perrow2', 'data' => 'misc'],
        ], ['name' => 'X']);
        // 与えた配列のとおりになっている（自動採番もされている）
        $this->assertEquals([
            ['id' => 1, 'name' => 'changeArray:bulk1'],
            ['id' => 2, 'name' => 'changeArray:bulk2'],
            ['id' => $max + 1, 'name' => 'changeArray:prepare1'],
            ['id' => $max + 2, 'name' => 'changeArray:prepare2'],
            ['id' => $max + 3, 'name' => 'changeArray:perrow1'],
            ['id' => $max + 4, 'name' => 'changeArray:perrow2'],
        ], $database->selectArray('test.id,name', ['name LIKE ?' => 'changeArray:%']));
        // 主キーを返している（自動採番もされている）
        $this->assertEquals([
            ['id' => 1],
            ['id' => 2],
            ['id' => $max + 1],
            ['id' => $max + 2],
            ['id' => $max + 3],
            ['id' => $max + 4],
        ], $primaries);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_changeArray_auto($database)
    {
        $max = $database->max('test.id');

        $primaries = $database->changeArray('test', [
            ['id' => 1, 'name' => 'X'],
            ['name' => 'X'],
        ], ['name' => 'X']);
        // 与えた配列のとおりになっている（自動採番もされている）
        $this->assertEquals([
            ['id' => 1, 'name' => 'X'],
            ['id' => $max + 1, 'name' => 'X'],
        ], $database->selectArray('test.id,name', ['name' => 'X']));
        // 主キーを返している（自動採番もされている）
        $this->assertEquals([
            ['id' => 1],
            ['id' => $max + 1],
        ], $primaries);

        $primaries = $database->changeArray('test', [
            ['id' => 1, 'name' => 'X'],
        ], ['name' => 'X', "id <> $max + 1"]);
        // 与えた配列のとおりになっている（id:$max + 1 は生き残っている）
        $this->assertEquals([
            ['id' => 1, 'name' => 'X'],
            ['id' => $max + 1, 'name' => 'X'],
        ], $database->selectArray('test.id,name', ['name' => 'X']));
        // 主キーを返している（自動採番もされている）
        $this->assertEquals([
            ['id' => 1],
        ], $primaries);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_changeArray_pk($database)
    {
        $mainid2 = $database->selectArray('multiprimary', ['mainid' => 2]);

        $primaries = $database->changeArray('multiprimary', [
            ['mainid' => 1, 'subid' => 1, 'name' => 'X'],
            ['mainid' => 1, 'subid' => 2, 'name' => 'Y'],
            ['mainid' => 1, 'subid' => 3, 'name' => 'Z'],
        ], ['mainid' => 1]);
        // 与えた配列のとおりになっている
        $this->assertEquals([
            ['mainid' => 1, 'subid' => 1, 'name' => 'X'],
            ['mainid' => 1, 'subid' => 2, 'name' => 'Y'],
            ['mainid' => 1, 'subid' => 3, 'name' => 'Z'],
        ], $database->selectArray('multiprimary', ['mainid' => 1]));
        // 主キーを返している
        $this->assertEquals([
            ['mainid' => 1, 'subid' => 1],
            ['mainid' => 1, 'subid' => 2],
            ['mainid' => 1, 'subid' => 3],
        ], $primaries);

        $primaries = $database->changeArray('multiprimary', [
            ['mainid' => 1, 'subid' => 3, 'name' => 'XX'],
            ['mainid' => 1, 'subid' => 4, 'name' => 'YY'],
            ['mainid' => 1, 'subid' => 5, 'name' => 'ZZ'],
        ], ['mainid' => 1]);
        // 与えた配列のとおりになっている
        $this->assertEquals([
            ['mainid' => 1, 'subid' => 3, 'name' => 'XX'],
            ['mainid' => 1, 'subid' => 4, 'name' => 'YY'],
            ['mainid' => 1, 'subid' => 5, 'name' => 'ZZ'],
        ], $database->selectArray('multiprimary', ['mainid' => 1]));
        // 主キーを返している
        $this->assertEquals([
            ['mainid' => 1, 'subid' => 3],
            ['mainid' => 1, 'subid' => 4],
            ['mainid' => 1, 'subid' => 5],
        ], $primaries);

        // 一連の流れで mainid=2 に波及していないことを担保
        $this->assertEquals($mainid2, $database->selectArray('multiprimary', ['mainid' => 2]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_changeArray_misc($database)
    {
        $changed = $database->dryrun()->changeArray('test', [], true);
        $this->assertEquals([], $changed[0]);
        $this->assertArrayStartsWith(['DELETE FROM'], $changed[1]);

        $changed = $database->dryrun()->changeArray('test', [
            ['id' => 1, 'name' => 'X'],
            ['id' => 2, 'name' => 'Y'],
            ['id' => null, 'name' => 'Z'],
        ], true);

        $this->assertEquals([
            ['id' => 1],
            ['id' => 2],
            ['id' => 11],
        ], $changed[0]);

        if ($database->getCompatiblePlatform()->supportsBulkMerge()) {
            $this->assertArrayStartsWith([
                'INSERT INTO test',
                'INSERT INTO test',
                'DELETE FROM test',
            ], $changed[1]);
        }
        else {
            $this->assertArrayStartsWith([
                'UPDATE test',
                'UPDATE test',
                'INSERT INTO test',
                'DELETE FROM test',
            ], $changed[1]);
        }

        $changed = $database->dryrun()->changeArray('multiprimary', [
            ['mainid' => 1, 'subid' => 1, 'name' => 'X'],
            ['mainid' => 1, 'subid' => 2, 'name' => 'Y'],
            ['mainid' => 1, 'subid' => 3, 'name' => 'Z'],
        ], ['mainid' => 1], ['bulk' => false]);

        $this->assertEquals([
            ['mainid' => 1, 'subid' => 1],
            ['mainid' => 1, 'subid' => 2],
            ['mainid' => 1, 'subid' => 3],
        ], $changed[0]);

        if ($database->getCompatiblePlatform()->supportsBulkMerge()) {
            $this->assertArrayStartsWith([
                'INSERT INTO multiprimary',
                'INSERT INTO multiprimary',
                'INSERT INTO multiprimary',
                'DELETE FROM multiprimary',
            ], $changed[1]);
        }
        else {
            $this->assertArrayStartsWith([
                'UPDATE multiprimary',
                'UPDATE multiprimary',
                'UPDATE multiprimary',
                'DELETE FROM multiprimary',
            ], $changed[1]);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_replace($database)
    {
        if ($database->getCompatiblePlatform()->supportsReplace()) {
            $affected = $database->replace('test', ['name' => 'newN', 'data' => 'newD']);
            $this->assertEquals(1, $affected);
            $id = $database->getLastInsertId('test', 'id');
            $this->assertEquals(11, $id);
            $this->assertEquals(['name' => 'newN', 'data' => 'newD'], $database->selectTuple('test.name,data', ['id' => $id]));

            $database->replace('test', ['id' => $id, 'name' => 'repN', 'data' => 'repD']);
            $this->assertEquals(['name' => 'repN', 'data' => 'repD'], $database->selectTuple('test.name,data', ['id' => $id]));

            $this->assertEquals(['id' => $id + 1], $database->replaceOrThrow('test', ['id' => $id + 1, 'name' => '', 'data' => '']));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_duplicate($database)
    {
        $duplicatest = $database->getSchema()->getTable('test');
        $duplicatest->addColumn('name2', 'string', ['length' => 32, 'default' => '']);
        self::forcedWrite($duplicatest, '_name', 'duplicatest');
        $smanager = $database->getConnection()->createSchemaManager();
        try_return([$smanager, 'dropTable'], $duplicatest);
        $smanager->createTable($duplicatest);
        $database->getSchema()->refresh();

        // 全コピーしたら件数・データ共に等しいはず
        $database->duplicate('duplicatest', [], [], 'test');
        $this->assertEquals($database->count('test'), $database->count('duplicatest'));
        $this->assertEquals($database->selectArray('test.id,test.name'), $database->selectArray('duplicatest.id,duplicatest.name'));

        // test.name をduplicatest.name2 へコピー
        $database->duplicate('duplicatest', ['id' => 999, 'name2' => new Expression('name')], ['id' => 1], 'test');
        $this->assertEquals($database->selectValue('test.name', 'id=1'), $database->selectValue('duplicatest.name2', 'id=999'));

        // 同じテーブルの主キーコピーで件数が +1 になるはず
        $count = $database->count('test');
        $database->duplicate('test', [], ['id' => 1]);
        $this->assertEquals($count + 1, $database->count('test'));

        // 同じテーブルで全コピーで件数が *2 になるはず
        $count = $database->count('test');
        $database->duplicate('test', []);
        $this->assertEquals($count * 2, $database->count('test'));

        // メインID2を3,サブIDを*10してコピー
        $database->duplicate('multiprimary', ['mainid' => 3, 'subid' => new Expression('subid * 10')], ['mainid' => 2]);
        $this->assertEquals([
            ['mainid' => 3, 'subid' => 60, 'name' => 'f'],
            ['mainid' => 3, 'subid' => 70, 'name' => 'g'],
            ['mainid' => 3, 'subid' => 80, 'name' => 'h'],
            ['mainid' => 3, 'subid' => 90, 'name' => 'i'],
            ['mainid' => 3, 'subid' => 100, 'name' => 'j'],
        ], $database->selectArray('multiprimary', ['mainid' => 3]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_truncate($database)
    {
        $database->truncate('test');
        $this->assertEquals(0, $database->count('test'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_affect_ignore($database)
    {
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $database->insert('test', ['id' => 1], ['ignore' => true]);
            $database->update('test', ['id' => 1], ['id' => 2], ['ignore' => true]);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_affect_vcolumn($database)
    {
        $database->overrideColumns([
            'test' => [
                'vname' => [
                    'select' => function (Database $database) {
                        return 'LOWER(name)';
                    },
                    'affect' => function ($value, $row) {
                        return [
                            'name' => strtoupper($value),
                        ];
                    },
                ],
            ],
        ]);

        $pk = $database->insertOrThrow('test', ['vname' => 'hoge']);
        $this->assertEquals('HOGE', $database->selectValue('test.name', $pk));
        $this->assertEquals('hoge', $database->selectValue('test.vname', $pk));

        $database->update('test', ['vname' => 'fuga'], $pk);
        $this->assertEquals('FUGA', $database->selectValue('test.name', $pk));
        $this->assertEquals('fuga', $database->selectValue('test.vname', $pk));

        $database->overrideColumns([
            'test' => [
                'vname' => null,
            ],
        ]);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_prewhere($database)
    {
        $prewhere = self::forcedCallize($database, '_prewhere');
        $params = [];
        $actual = $database->whereInto($prewhere(['A' => 'foreign_p'], $database->subexists('foreign_c1')), $params);
        $this->assertEquals(['(EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = A.id))'], $actual);

        $cx = $database->dryrun();

        $query = $cx->update('foreign_p', ['name' => 'HOGE'], $cx->subexists('foreign_c1', ['seq > ?' => 0]));
        $this->assertStringIgnoreBreak("
UPDATE foreign_p
SET name = 'HOGE'
WHERE (EXISTS (SELECT * FROM foreign_c1 WHERE (seq > '0') AND (foreign_c1.id = foreign_p.id)))", $query);

        $query = $cx->updateArray('foreign_p', [
            ['id' => 1, 'name' => 'HOGE'],
            ['id' => 2, 'name' => 'FUGA'],
        ], $cx->subexists('foreign_c1', ['seq > ?' => 0]));
        $this->assertStringIgnoreBreak("
UPDATE foreign_p
SET name = CASE id WHEN '1' THEN 'HOGE' WHEN '2' THEN 'FUGA' ELSE name END
WHERE ((EXISTS (SELECT * FROM foreign_c1 WHERE (seq > '0') AND (foreign_c1.id = foreign_p.id)))) AND (foreign_p.id IN ('1', '2'))
", $query);

        $query = $cx->delete('foreign_p', $cx->subexists('foreign_c1', ['seq > ?' => 0]));
        $this->assertStringIgnoreBreak("
DELETE FROM foreign_p
WHERE (EXISTS (SELECT * FROM foreign_c1 WHERE (seq > '0') AND (foreign_c1.id = foreign_p.id)))
", $query);

        $query = $cx->remove('foreign_p', $cx->subexists('foreign_c1', ['seq > ?' => 0]));
        $this->assertStringIgnoreBreak("
DELETE FROM foreign_p
WHERE
((EXISTS (SELECT * FROM foreign_c1 WHERE (seq > '0') AND (foreign_c1.id = foreign_p.id))))
AND ((NOT EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = foreign_p.id)))
AND ((NOT EXISTS (SELECT * FROM foreign_c2 WHERE foreign_c2.cid = foreign_p.id)))
", $query);

        // ネストしててもOKのはず
        $query = $cx->delete('g_ancestor', $cx->subexists('g_parent', $cx->subexists('g_child')));
        $this->assertStringIgnoreBreak("DELETE FROM g_ancestor WHERE
(EXISTS (SELECT * FROM g_parent WHERE
((EXISTS (SELECT * FROM g_child WHERE g_child.parent_id = g_parent.parent_id)))
AND (g_parent.ancestor_id = g_ancestor.ancestor_id)))
", $query);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subquery($database)
    {
        $cplatform = $database->getCompatiblePlatform();
        if ($cplatform->getWrappedPlatform() instanceof SQLServerPlatform) {
            return;
        }

        $rows = $database->selectArray([
            't_article' => [
                'comment_ids' => $database->subquery('t_comment.' . $cplatform->getGroupConcatSyntax('comment_id', ',')),
            ],
        ], ['article_id' => [1, 2]]);
        $this->assertEquals([
            [
                'comment_ids' => '1,2,3',
            ],
            [
                'comment_ids' => null,
            ],
        ], $rows);

        $rows = $database->selectArray([
            't_article' => [
                'article_id',
            ],
        ], [
            'article_id' => $database->subquery('t_comment'),
        ]);
        $this->assertEquals([
            [
                'article_id' => '1',
            ],
        ], $rows);

        $row = $database->entityTuple([
            'Article' => [
                'comment_ids' => $database->subquery('t_comment.' . $cplatform->getGroupConcatSyntax('comment_id', ',')),
            ],
        ], ['article_id' => 1]);
        $this->assertEquals('1,2,3', $row->{"comment_ids"});
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subexists($database)
    {
        $rows = $database->selectArray([
            't_article' => [
                'has_comment'    => $database->subexists('t_comment'),
                'nothas_comment' => $database->notSubexists('t_comment'),
            ],
        ], ['article_id' => [1, 2]]);
        $this->assertTrue(!!$rows[0]['has_comment']);
        $this->assertFalse(!!$rows[0]['nothas_comment']);
        $this->assertFalse(!!$rows[1]['has_comment']);
        $this->assertTrue(!!$rows[1]['nothas_comment']);

        $row = $database->entityTuple([
            'Article' => [
                'has_comment'    => $database->subexists('Comment'),
                'nothas_comment' => $database->notSubexists('Comment'),
            ],
        ], ['article_id' => 1]);
        $this->assertTrue(!!$row['has_comment']);
        $this->assertFalse(!!$row['nothas_comment']);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subexists_descripter($database)
    {
        $select = $database->select('t_article A', [
            $database->subexists('t_comment[delete_flg: 0] C'),
        ]);
        $this->assertStringIgnoreBreak("SELECT A.* FROM t_article A WHERE
(EXISTS (SELECT * FROM t_comment C WHERE (delete_flg = '0') AND (C.article_id = A.article_id)))", $select->queryInto());

        $select = $database->select('t_article A', [
            $database->subexists('t_comment:[delete_flg: 0] C'),
        ]);
        $this->assertStringIgnoreBreak("SELECT A.* FROM t_article A WHERE
(EXISTS (SELECT * FROM t_comment C WHERE delete_flg = '0'))", $select->queryInto());

        $select = $database->select('test1 T1', [
            $database->subexists('test2[delete_flg: 0] T2'),
        ]);
        $this->assertStringIgnoreBreak("SELECT T1.* FROM test1 T1 WHERE
(EXISTS (SELECT * FROM test2 T2 WHERE delete_flg = '0'))", $select->queryInto());

        $select = $database->select('foreign_p P', [
            $database->subexists('foreign_c1:{id1: id2} C1'),
            $database->subexists('foreign_c2{cid1: id2} C2'),
        ]);
        $this->assertStringIgnoreBreak("SELECT P.* FROM foreign_p P WHERE
((EXISTS (SELECT * FROM foreign_c1 C1 WHERE C1.id1 = P.id2)))
AND
((EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid1 = P.id2) AND (C2.cid = P.id))))", $select->queryInto());

        $select = $database->select('t_article A', [
            $database->subexists('t_comment@scope2(9) C'),
        ]);
        $this->assertStringIgnoreBreak("SELECT A.* FROM t_article A WHERE
(EXISTS (SELECT * FROM t_comment C WHERE (C.comment_id = '9') AND (C.article_id = A.article_id)))", $select->queryInto());

        $select = $database->select('t_article A', [
            $database->subexists('t_comment:@scope2(9){article_id: id}[delete_flg: 0] C'),
        ]);
        $this->assertStringIgnoreBreak("SELECT A.* FROM t_article A WHERE
(EXISTS (SELECT * FROM t_comment C WHERE (delete_flg = '0') AND (C.comment_id = '9') AND (C.article_id = A.id)))", $select->queryInto());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subexists_ignore($database)
    {
        // 問題なく含まれる
        $select = $database->select('foreign_p P', $database->subexists('foreign_c1 C'));
        $this->assertEquals('SELECT P.* FROM foreign_p P WHERE (EXISTS (SELECT * FROM foreign_c1 C WHERE C.id = P.id))', (string) $select);

        // '!' 付きだが値が有効なので含まれる
        $select = $database->select('foreign_p P', $database->subexists('foreign_c1 C', ['!id' => 1]));
        $this->assertEquals('SELECT P.* FROM foreign_p P WHERE (EXISTS (SELECT * FROM foreign_c1 C WHERE (id = ?) AND (C.id = P.id)))', (string) $select);

        // '!' 付きで値が無効なので含まれない
        $select = $database->select('foreign_p P', $database->subexists('foreign_c1 C', ['!id' => null]));
        $this->assertEquals('SELECT P.* FROM foreign_p P', (string) $select);

        // 親指定版
        $select = $database->select('foreign_p P', [
            'P' => $database->subexists('foreign_c1 C', ['!id' => 1]),
        ]);
        $this->assertEquals('SELECT P.* FROM foreign_p P WHERE (EXISTS (SELECT * FROM foreign_c1 C WHERE (id = ?) AND (C.id = P.id)))', (string) $select);

        $select = $database->select('foreign_p P', [
            'P' => $database->subexists('foreign_c1 C', ['!id' => null]),
        ]);
        $this->assertEquals('SELECT P.* FROM foreign_p P', (string) $select);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_sub_foreign($database)
    {
        $cplatform = $database->getCompatiblePlatform();

        // 相互外部キー1
        $select = $database->select([
            'foreign_d1' => [
                'has_d2' => $database->subexists('foreign_d2:fk_dd12'),
            ],
        ]);
        $exsits = $cplatform->convertSelectExistsQuery('EXISTS (SELECT * FROM foreign_d2 WHERE foreign_d2.id = foreign_d1.d2_id)');
        $this->assertStringContainsString("$exsits", "$select");

        // 相互外部キー2
        $select = $database->select([
            'foreign_d2' => [
                'has_d1' => $database->subexists('foreign_d1:fk_dd21'),
            ],
        ]);
        $exsits = $cplatform->convertSelectExistsQuery('EXISTS (SELECT * FROM foreign_d1 WHERE foreign_d1.id = foreign_d2.id)');
        $this->assertStringContainsString("$exsits", "$select");

        // ダブル外部キー
        $select = $database->select([
            'foreign_s' => [
                'has_sc1' => $database->subexists('foreign_sc:fk_sc1'),
                'has_sc2' => $database->subexists('foreign_sc:fk_sc2'),
            ],
        ]);
        $exsits1 = $cplatform->convertSelectExistsQuery('EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id1 = foreign_s.id)');
        $exsits2 = $cplatform->convertSelectExistsQuery('EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id2 = foreign_s.id)');
        $this->assertStringContainsString("$exsits1", "$select");
        $this->assertStringContainsString("$exsits2", "$select");

        // 指定しないと例外
        $this->assertException('ambiguous', function () use ($database) {
            $database->select([
                'foreign_d1' => [
                    'has_d2' => $database->subexists('foreign_d2'),
                ],
            ]);
        });
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subaggregate($database)
    {
        $row = $database->selectTuple([
            't_article' => [
                'cmin' => $database->submin('t_comment.comment_id'),
                'cmax' => $database->submax('t_comment.comment_id'),
            ],
        ], [], [], 1);
        $this->assertEquals('1', $row['cmin']);
        $this->assertEquals('3', $row['cmax']);

        $this->assertException("aggregate column's length is over 1", function () use ($database) {
            $database->selectTuple([
                't_article' => [
                    'cmin' => $database->submin('t_comment.comment_id, comment'),
                    'cmax' => $database->submax('t_comment.comment_id, comment'),
                ],
            ], [], [], 1);
        });
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_select($database)
    {
        $table = [
            'test',
            [
                new Expression('(select \'value\') as value'),
                'builder' => $database->select(
                    [
                        'test' => 'name',
                    ], [
                        'id = ?' => 2,
                    ]
                ),
            ],
        ];
        $where = [
            'id >= ?' => 5,
        ];
        $order = [
            'id' => 'desc',
        ];
        $limit = [
            2 => 3,
        ];
        $rows = $database->selectArray($table, $where, $order, $limit);

        // LIMIT 効果で3件のはず
        $this->assertCount(3, $rows);

        $row0 = $rows[0];
        $row1 = $rows[1];
        $row2 = $rows[2];

        // value は 'value' 固定値のはず
        $this->assertEquals('value', $row0['value']);
        // builder は id=2 なので 'b' 固定値のはず
        $this->assertEquals('b', $row0['builder']);
        // id >= 5 の 降順 OFFSET 2 なので id は 8 のはず
        $this->assertEquals(8, $row0['id']);

        // value は 'value' 固定値のはず
        $this->assertEquals('value', $row1['value']);
        // builder は id=2 なので 'b' 固定値のはず
        $this->assertEquals('b', $row1['builder']);
        // id >= 5 の 降順 OFFSET 2 なので id は 8 のはず
        $this->assertEquals(7, $row1['id']);

        // value は 'value' 固定値のはず
        $this->assertEquals('value', $row2['value']);
        // builder は id=2 なので 'b' 固定値のはず
        $this->assertEquals('b', $row2['builder']);
        // id >= 5 の 降順 OFFSET 2 なので id は 8 のはず
        $this->assertEquals(6, $row2['id']);

        // groupBy は構造自体が変わってしまうので別に行う
        $this->assertCount(1, $database->selectArray('test.data', [], [], [], 'data', 'min(id) > 0'));

        // TableDescriptor と select 引数の複合呼び出し
        $this->assertStringIgnoreBreak("
SELECT T.id FROM test T
WHERE (T.id = '1') AND (name LIKE 'hoge')
ORDER BY T.id DESC, name ASC
", $database->select([
            'test T(1)-id' => ['id'],
        ], [
            'name:LIKE' => 'hoge',
        ], [
            'name' => 'ASC',
        ])->queryInto());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_union($database)
    {
        $sub = $database->select('test.id', ['id' => 3]);
        $this->assertEquals([1, 2, 3], $database->union(['select 1', 'select 2', $sub])->lists());
        $this->assertEquals([3], $database->union(['select 3', 'select 3', $sub])->lists());
        $this->assertEquals([3, 3, 3], $database->unionAll(['select 3', 'select 3', $sub])->lists());
        $this->assertEquals(['hoge' => 1], $database->union(["select 1 id", "select 2 id", $sub], ['' => 'id hoge'], [], [], 1)->tuple());
        $this->assertEquals([2], $database->union(["select 1 id", "select 2 id", $sub], [], ['id=2'])->lists());
        $this->assertEquals([3, 2, 1], $database->union(["select 1 id", "select 2 id", $sub], [], [], ['id' => 'desc'])->lists());
        $this->assertEquals([3, 2], $database->union(["select 1 id", "select 2 id", $sub], [], [], ['id' => 'desc'], 2)->lists());

        // qb
        $test1 = $database->select('test1(1,2,3).id, id as ord, name1 name');
        $test2 = $database->select([
            'test2' => [
                'id',
                'ord'  => 'id + 10',
                'name' => 'name2',
            ],
        ], ['id' => [3, 4, 5]]);
        $this->assertEquals([
            ['id' => '3', 'name' => 'c', 'a' => 'A'],
            ['id' => '3', 'name' => 'C', 'a' => 'A'],
        ], $database->unionAll([$test1, $test2], ['id', 'name', 'a' => new Expression('UPPER(?)', 'a')], ['id' => 3], 'ord')->array());

        // gw
        $test1 = $database->test1['(1,2,3).id, id as ord, name1 name'];
        $test2 = $database->test2([
            'id',
            'ord'  => 'id + 10',
            'name' => 'name2',
        ], ['id' => [3, 4, 5]]);
        $this->assertEquals([
            ['id' => '3', 'name' => 'c', 'a' => 'A'],
            ['id' => '3', 'name' => 'C', 'a' => 'A'],
        ], $database->unionAll([$test1, $test2], ['id', 'name', 'a' => new Expression('UPPER(?)', 'a')], ['id' => 3], 'ord')->array());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_selectExists($database)
    {
        $builder = $database->selectExists('test', ['id' => 1]);
        $this->assertEquals('EXISTS (SELECT * FROM test WHERE id = ?)', "$builder");
        $this->assertEquals([1], $builder->getParams());

        $builder = $database->selectNotExists('test', ['id' => 1]);
        $this->assertEquals('NOT EXISTS (SELECT * FROM test WHERE id = ?)', "$builder");
        $this->assertEquals([1], $builder->getParams());

        $forWrite = $database->getPlatform()->getWriteLockSQL();
        if (trim($forWrite)) {
            $builder = $database->selectExists('test', ['id' => 1], true);
            $this->assertEquals("EXISTS (SELECT * FROM test WHERE id = ? $forWrite)", "$builder");
            $this->assertEquals([1], $builder->getParams());

            $builder = $database->selectNotExists('test', ['id' => 1], true);
            $this->assertEquals("NOT EXISTS (SELECT * FROM test WHERE id = ? $forWrite)", "$builder");
            $this->assertEquals([1], $builder->getParams());
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_selectAggregate($database)
    {
        $qi = function ($str) use ($database) {
            return $database->getPlatform()->quoteSingleIdentifier($str);
        };

        $builder = $database->selectCount('aggregate.id');
        $this->assertEquals("SELECT COUNT(aggregate.id) AS {$qi('aggregate.id@Count')} FROM aggregate", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals(10, $builder->value());

        $builder = $database->selectMax('aggregate.id');
        $this->assertEquals("SELECT MAX(aggregate.id) AS {$qi('aggregate.id@Max')} FROM aggregate", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals(10, $builder->value());

        $builder = $database->selectCount('aggregate.id', [], ['group_id2']);
        $this->assertEquals("SELECT group_id2, COUNT(aggregate.id) AS {$qi('aggregate.id@Count')} FROM aggregate GROUP BY group_id2", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals([
            10 => 5,
            20 => 5,
        ], $builder->pairs());

        $builder = $database->selectMin('aggregate.id', [], ['group_id2']);
        $this->assertEquals("SELECT group_id2, MIN(aggregate.id) AS {$qi('aggregate.id@Min')} FROM aggregate GROUP BY group_id2", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals([
            10 => 1,
            20 => 6,
        ], $builder->pairs());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_neighbor($database)
    {
        // プロキシメソッドなのでデフォルト引数のみテスト
        $this->assertEquals([
            -1 => ['id' => '4', 'name' => 'd'],
            1  => ['id' => '6', 'name' => 'f'],
        ], $database->neighbor('test.id, name', ['id' => 5]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_exists($database)
    {
        $this->assertTrue($database->exists('test', ['id' => 1]));
        $this->assertFalse($database->exists('test', ['id' => -1]));
        $this->assertTrue($database->exists('test', ['id' => 1], true));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_aggregate($database)
    {
        // シンプルなavg
        $this->assertEquals(5.5, $database->aggregate('avg', 'aggregate.id'));

        // 単一のグルーピングsum
        $this->assertEquals([
            3 => 6,
            4 => 15,
            5 => 19,
        ], $database->aggregate('sum', 'aggregate.id', ['id > 5'], ['group_id1']));

        // 複数のグルーピングcount
        $this->assertEquals([
            4 => [
                'aggregate.id@count'   => 2,
                'aggregate.name@count' => 2,
            ],
            5 => [
                'aggregate.id@count'   => 2,
                'aggregate.name@count' => 2,
            ],
        ], $database->aggregate('count', 'aggregate.id, name', ['id > 5'], ['group_id1'], ['count(aggregate.id) > 1']));

        // 自由モード
        $this->assertEquals([
            'a' => 1,
            'b' => 2,
        ], $database->aggregate([
            'a' => ['? + 1' => 0],
            'b' => ['? + 1' => 1],
        ], 'test'));

        $this->assertEquals([
            ['id' => 1, 'name' => 'a', 'a' => 1],
            ['id' => 2, 'name' => 'b', 'a' => 1],
            ['id' => 3, 'name' => 'c', 'a' => 1],
            ['id' => 4, 'name' => 'd', 'a' => 1],
        ], array_order((array) $database->aggregate([
            '? + 1' => ['a' => 0],
        ], 'test', ['id < 5'], ['id', 'name']), ['id' => true]));

        // 数値以外
        $this->assertEquals('a', $database->aggregate('min', 'test.name'));
        $this->assertEquals('j', $database->aggregate('max', 'test.name'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_anywhere($database)
    {
        //$database->getConnection()->getSchemaManager()->createForeignKey(new ForeignKeyConstraint(['cid'], 'misctype', ['pid'], 'fk_misctype_child1'), 'misctype_child');
        // SQLServer がよく分からんエラーを吐くのでアプリ的に追加する
        $database->getSchema()->refresh();
        $database->addForeignKey('misctype_child', 'misctype', ['cid' => 'pid']);

        $database->getSchema()->getTable('misctype')->addOption('anywhere', [
            'enable'  => 0,
            'comment' => '',
        ]);
        $database->getSchema()->getTable('misctype_child')->addOption('anywhere', [
            'enable'  => 0,
            'comment' => '',
        ]);
        foreach (['id', 'pid', 'cint', 'cfloat', 'cdecimal', 'cdate', 'cdatetime', 'cstring', 'ctext'] as $target) {
            $database->getSchema()->setTableColumn('misctype', $target, [
                'anywhere' => [
                    'enable' => 1,
                ],
            ]);
        }
        foreach (['id', 'cid'] as $target) {
            $database->getSchema()->setTableColumn('misctype_child', $target, [
                'anywhere' => [
                    'enable' => 1,
                ],
            ]);
        }

        $database = $database->context();

        // 空
        $this->assertEmpty($database->anywhere('misctype', ''));
        $this->assertEmpty($database->anywhere('misctype', null));

        // 素
        $this->assertEquals([
            'MT.id = ?'                    => '2000',
            'MT.pid = ?'                   => '2000',
            'MT.cint = ?'                  => '2000',
            'MT.cfloat = ?'                => '2000',
            'MT.cdecimal = ?'              => '2000',
            'MT.cdate BETWEEN ? AND ?'     => ['2000-01-01', '2000-12-31',],
            'MT.cdatetime BETWEEN ? AND ?' => ['2000-01-01 00:00:00', '2000-12-31 23:59:59',],
            'MT.cstring LIKE ?'            => '%2000%',
            'MT.ctext LIKE ?'              => '%2000%',
        ], $database->anywhere('misctype MT', '2000'));

        // クオーテッド
        $this->assertEquals([
            'MT.cstring LIKE ?' => '%2000%',
            'MT.ctext LIKE ?'   => '%2000%',
        ], $database->anywhere('misctype MT', '"2000"'));
        $this->assertEquals([
            'MT.cstring LIKE ?' => '%2000 14%',
            'MT.ctext LIKE ?'   => '%2000 14%',
        ], $database->anywhere('misctype MT', '"2000 14"'));

        // 強欲でない数値マッチ
        $database->mergeOption('anywhereOption', [
            'greedy' => false,
        ]);
        $this->assertEquals([
            'MT.id = ?'       => '2000',
            'MT.pid = ?'      => '2000',
            'MT.cint = ?'     => '2000',
            'MT.cfloat = ?'   => '2000',
            'MT.cdecimal = ?' => '2000',
        ], $database->anywhere('misctype MT', '2000'));

        // 強欲でない日時マッチ
        $database->mergeOption('anywhereOption', [
            'greedy' => false,
        ]);
        $this->assertEquals([
            'MT.cdate BETWEEN ? AND ?'     => ['2011-12-01', '2011-12-31'],
            'MT.cdatetime BETWEEN ? AND ?' => ['2011-12-01 00:00:00', '2011-12-31 23:59:59'],
        ], $database->anywhere('misctype MT', '2011/12'));

        // 強欲でない文字列マッチ
        $database->mergeOption('anywhereOption', [
            'greedy' => false,
        ]);
        $this->assertEquals([
            'MT.cstring LIKE ?' => '%hogera%',
            'MT.ctext LIKE ?'   => '%hogera%',
        ], $database->anywhere('misctype MT', 'hogera'));

        // キー系のみの数値マッチ
        $database->mergeOption('anywhereOption', [
            'keyonly' => true,
            'greedy'  => false,
        ]);
        $this->assertEquals([
            'MT.id = ?'  => '2000',
            'MT.pid = ?' => '2000',
        ], $database->anywhere('misctype MT', '2000'));
        $this->assertEquals([
            'MTC.id = ?'  => '2000',
            'MTC.cid = ?' => '2000',
        ], $database->anywhere('misctype_child MTC', '2000'));

        // ただの数値
        $database->mergeOption('anywhereOption', [
            'keyonly' => false,
            'greedy'  => true,
        ]);
        $this->assertEquals([
            'misctype.id = ?'         => '123',
            'misctype.cint = ?'       => '123',
            'misctype.pid = ?'        => '123',
            'misctype.cfloat = ?'     => '123',
            'misctype.cdecimal = ?'   => '123',
            'misctype.cstring LIKE ?' => '%123%',
            'misctype.ctext LIKE ?'   => '%123%',
        ], $database->anywhere('misctype', '123'));

        // ただの日時
        $this->assertEquals([
            'MT.cdate BETWEEN ? AND ?'     => ['2011-12-01', '2011-12-31'],
            'MT.cdatetime BETWEEN ? AND ?' => ['2011-12-01 00:00:00', '2011-12-31 23:59:59'],
            'MT.cstring LIKE ?'            => '%2011/12%',
            'MT.ctext LIKE ?'              => '%2011/12%',
        ], $database->anywhere('misctype MT', '2011/12'));

        // type 指定
        $database->mergeOption('anywhereOption', [
            'misctype' => [
                'cdate' => ['type' => 'string'],
            ],
        ]);
        $this->assertEquals([
            'MT.cdate LIKE ?'              => '%2011/12%',
            'MT.cdatetime BETWEEN ? AND ?' => ['2011-12-01 00:00:00', '2011-12-31 23:59:59'],
            'MT.cstring LIKE ?'            => '%2011/12%',
            'MT.ctext LIKE ?'              => '%2011/12%',
        ], $database->anywhere('misctype MT', '2011/12'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_anywhere_priotity($database)
    {
        /// 通常用途でよくありそうな「グローバルで無効、テーブル・カラムで指定されている分には有効」を担保
        $database = $database->context();

        // グローバルで無効なら空になる
        $database->getSchema()->refresh();
        $database = $database->mergeOption('anywhereOption', [
            'enable' => false,
        ]);
        $this->assertEmpty($database->anywhere('misctype MT', '2000'));

        // テーブル単位で有効ならいっぱい出てくる
        $database->getSchema()->refresh();
        $database->getSchema()->getTable('misctype')->addOption('anywhere', [
            'enable' => 1,
        ]);
        $this->assertNotEmpty($database->anywhere('misctype MT', '2000'));

        // 「このテーブルだけは greedy: false, keyonly: true にしたい」とか
        $database->getSchema()->refresh();
        $database->getSchema()->getTable('misctype')->addOption('anywhere', [
            'enable'  => 1,
            'greedy'  => 0,
            'keyonly' => 1,
        ]);
        $this->assertEquals([
            '/* anywhere */ MT.id = ?' => '2000',
        ], $database->anywhere('misctype MT', '2000'));

        // カラム単位で有効ならそいつのみ
        $database->getSchema()->refresh();
        $database->getSchema()->setTableColumn('misctype', 'cstring', [
            'anywhere' => [
                'enable' => 1,
            ],
        ]);
        $this->assertEquals([
            '/* anywhere */ MT.cstring LIKE ?' => '%2000%',
        ], $database->anywhere('misctype MT', '2000'));

        // 「id は文字列的にやりたい / cstring は collate 指定なし / ctext は utf8_unicode_ci で検索」とか
        $database->getSchema()->refresh();
        $database->getSchema()->setTableColumn('misctype', 'id', [
            'anywhere' => [
                'enable' => 1,
                'type'   => 'text',
            ],
        ]);
        $database->getSchema()->setTableColumn('misctype', 'cstring', [
            'anywhere' => [
                'enable' => 1,
            ],
        ]);
        $database->getSchema()->setTableColumn('misctype', 'ctext', [
            'anywhere' => [
                'enable'  => 1,
                'collate' => 'utf8_unicode_ci',
            ],
        ]);
        $this->assertEquals([
            '/* anywhere */ MT.id LIKE ?'                            => '%2000%',
            '/* anywhere */ MT.cstring LIKE ?'                       => '%2000%',
            '/* anywhere */ MT.ctext collate utf8_unicode_ci LIKE ?' => '%2000%',
        ], $database->anywhere('misctype MT', '2000'));

        // グローバル、テーブル・カラムコメントでゴリゴリに指定されているが「いまだけはこの設定で検索したい」とか
        $database->getSchema()->refresh();
        $database->getSchema()->getTable('misctype')->addOption('anywhere', [
            'enable'  => 1,
            'greedy'  => 0,
            'keyonly' => 1,
        ]);
        $database->getSchema()->setTableColumn('misctype', 'id', [
            'anywhere' => [
                'enable' => 1,
                'type'   => 'text',
            ],
        ]);
        $database->getSchema()->setTableColumn('misctype', 'cstring', [
            'anywhere' => [
                'enable' => 0,
            ],
        ]);
        $database = $database->mergeOption('anywhereOption', [
            'misctype' => [
                'enable'  => false,
                'id'      => [
                    'enable'  => true,
                    'greedy'  => true,
                    'type'    => 'integer',
                    'comment' => '',
                ],
                'cstring' => [
                    'enable'  => true,
                    'greedy'  => true,
                    'keyonly' => false,
                    'type'    => 'integer',
                ],
            ],
        ]);
        $this->assertEquals([
            'MT.id = ?'                     => '2000',
            '/* anywhere */ MT.cstring = ?' => '2000',
        ], $database->anywhere('misctype MT', '2000'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_warmup($database)
    {
        $actual = $database->warmup('t_article');
        $this->assertIsArray($actual['t_article']);
        $this->assertEquals(4, array_sum($actual['t_article']));

        $actual = $database->warmup(['t_article', 't_comment']);
        $this->assertIsArray($actual['t_comment']);
        $this->assertEquals(6, array_sum($actual['t_comment']));

        $actual = $database->warmup('t_*');
        $this->assertEquals(['t_article', 't_comment'], array_keys($actual));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subselect_method($database)
    {
        $select = $database->select([
            'test1',
            [
                'hoge{id}' => $database->subselectArray('test2'),
            ],
        ]);

        $this->assertIsArray($database->fetchArray($select));
        $this->assertIsArray($database->fetchAssoc($select));
        $this->assertIsArray($database->fetchTuple($select->limit(1)));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subselect($database)
    {
        $normalize = function ($something) {
            return json_decode(json_encode($something), true);
        };

        $rows = $database->selectArray(
            [
                'test1' => 'id',
                [
                    // subselect
                    'arrayS{id}'    => $database->subselectArray('test2.name2, id'),
                    'assocS{id}'    => $database->subselectAssoc('test2.name2, id'),
                    'colS{id}'      => $database->subselectLists('test2.name2, id'),
                    'pairsS{id}'    => $database->subselectPairs('test2.name2, id'),
                    'tupleS{id}'    => $database->subselectTuple('test2.name2, id', [], [], 1),
                    'valueS{id}'    => $database->subselectValue('test2.name2'),
                    'prefixS{id}'   => $database->subselectArray('test2.name2, id'),
                    // subcast
                    'arrayT{id}'    => $database->subselect('test2.name2, id')->cast(Entity::class)->array(),
                    'assocT{id}'    => $database->subselect('test2.name2, id')->cast(Entity::class)->assoc(),
                    'tupleT{id}'    => $database->subselect('test2.name2, id')->cast(Entity::class)->tuple(),
                    'callbackT{id}' => $database->subselect('test2.name2, id')->cast(function ($row) { return $row; })->array(),
                ],
            ]
        );

        // 各 fetch メソッドに応じた形で返っているはず
        $this->assertEquals(['0' => ['name2' => 'A', 'id' => '1']], $normalize($rows[0]['arrayS']));
        $this->assertEquals(['A' => ['name2' => 'A', 'id' => '1']], $normalize($rows[0]['assocS']));
        $this->assertEquals(['A'], $normalize($rows[0]['colS']));
        $this->assertEquals(['A' => '1'], $normalize($rows[0]['pairsS']));
        $this->assertEquals(['name2' => 'A', 'id' => '1'], $normalize($rows[0]['tupleS']));
        $this->assertEquals('A', $normalize($rows[0]['valueS']));

        // prefix 付きも問題なく取得できるはず
        $this->assertEquals($rows[0]['arrayS'], $rows[0]['prefixS']);

        // 各 cast メソッドに応じた形で返っているはず
        $this->assertEquals(['0' => ['name2' => 'A', 'id' => '1']], $normalize($rows[0]['arrayT']));
        $this->assertEquals(['A' => ['name2' => 'A', 'id' => '1']], $normalize($rows[0]['assocT']));
        $this->assertEquals(['name2' => 'A', 'id' => '1'], $normalize($rows[0]['tupleT']));

        // prefix 付きも問題なく取得できるはず
        $this->assertEquals($normalize($rows[0]['arrayT']), $rows[0]['callbackT']);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subparent($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'cname11']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'cname12']);
        $database->insert('foreign_c1', ['id' => 2, 'seq' => 1, 'name' => 'cname21']);

        $rows = $database->selectArray([
            'foreign_c1' => [
                '*',
                'P' => $database->subselectTuple('foreign_p'),
            ],
        ]);

        // 子から親を引っ張れば同じものが含まれるものがあるはず
        $expected1 = [
            'id'   => "1",
            'name' => "name1",
        ];
        $expected2 = [
            'id'   => "2",
            'name' => "name2",
        ];
        $this->assertEquals($expected1, $rows[0]['P']);
        $this->assertEquals($expected1, $rows[1]['P']);
        $this->assertEquals($expected2, $rows[2]['P']);

        // 子供を基点として subtable すると・・・
        $row = $database->selectTuple([
            'foreign_c1.*' => [
                'foreign_p.*' => [],
            ],
        ], [], [], 1);
        // 親は assoc されず単一 row で返ってくるはず
        $this->assertEquals([
            'id'        => '1',
            'seq'       => '1',
            'name'      => 'cname11',
            'foreign_p' => [
                'id'   => '1',
                'name' => 'name1',
            ],
        ], $row);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subgateway($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 21, 'name' => 'c2name1']);

        $row = $database->selectTuple([
            'foreign_p P' => [
                'C1' => $database->foreign_c1()->column('*'),
                'C2' => $database->foreign_c2()->column('name'),
            ],
        ], [], [], 1);
        $this->assertEquals(
            [
                'id'   => '1',
                'name' => 'name1',
                'C1'   => [
                    11 => [
                        'id'   => '1',
                        'seq'  => '11',
                        'name' => 'c1name1',
                    ],
                ],
                'C2'   => [
                    21 => [
                        'name' => 'c2name1',
                    ],
                ],
            ], $row);

        // 外部キーがなければ例外が飛ぶはず
        $this->assertException('has not foreign key', L($database)->selectTuple([
            'test1' => [
                'test2' => $database->foreign_c1(),
            ],
        ], [], [], 1));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_subselect_nest($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 21, 'name' => 'c2name1']);

        $rows = $database->selectArray([
            'foreign_p P' => [
                'pie'              => new Expression('3.14'),
                'foreign_c1 as C1' => ['name'],
                'foreign_c2 AS C2' => ['name'],
            ],
        ]);
        $this->assertEquals([
            [
                'pie' => 3.14,
                'C1'  => [
                    11 => ['name' => 'c1name1'],
                ],
                'C2'  => [
                    21 => ['name' => 'c2name1'],
                ],
            ],
        ], $rows);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_refparent($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'cname11']);
        $database->insert('foreign_c1', ['id' => 2, 'seq' => 1, 'name' => 'cname21']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'cname11']);

        $rows = $database->selectArray([
            'foreign_p P' => [
                '*',
                'pid'           => 'id',
                'foreign_c1 C1' => [
                    '*',
                    '..pid',
                    'ppname' => '..name',
                ],
                'C2'            => $database->subselectTuple([
                    'foreign_c2' => [
                        '*',
                        '..pid',
                        'ppname' => '..name',
                    ],
                ]),
            ],
        ]);
        // pname, pid で親カラムが参照できているはず
        $this->assertEquals([
            [
                'id'   => '1',
                'name' => 'name1',
                'pid'  => '1',
                'C1'   => [
                    1 => [
                        'seq'    => '1',
                        'id'     => '1',
                        'name'   => 'cname11',
                        'pid'    => '1',
                        'ppname' => 'name1',
                    ],
                ],
                'C2'   => [
                    'cid'    => '1',
                    'seq'    => '1',
                    'name'   => 'cname11',
                    'pid'    => '1',
                    'ppname' => 'name1',
                ],
            ],
            [
                'id'   => '2',
                'name' => 'name2',
                'pid'  => '2',
                'C1'   => [
                    1 => [
                        'seq'    => '1',
                        'id'     => '2',
                        'name'   => 'cname21',
                        'pid'    => '2',
                        'ppname' => 'name2',
                    ],
                ],
                'C2'   => false,
            ],
        ], $rows);

        // 親にないカラムを参照しようとすると例外が飛ぶ
        $this->assertException('reference undefined parent', L($database)->selectArray([
            'foreign_p P' => [
                '*',
                'pid'           => 'id',
                'foreign_c1 C1' => [
                    '*',
                    '..pid',
                    'ppname' => '..nocolumn',
                ],
            ],
        ]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_evaluate($database)
    {
        $select = $database->select([
            'test' => ['id', 'name'],
            [
                'piyo' => function ($row) { return $row['id'] . ':' . $row['name']; },
                'func' => function () { return function ($prefix) { return $prefix . $this['name']; }; },
                'last' => new Expression("'dbval'"),
            ],
        ])->limit(1);

        $expected = [
            'id'   => '1',
            'name' => 'a',
            'piyo' => '1:a',
            'func' => function () { /* dummy */ },
            'last' => 'dbval',
        ];

        $this->assertEquals([0 => $expected], $select->array());
        $this->assertEquals([1 => $expected], $select->assoc());
        $this->assertEquals($expected, $select->tuple());

        $this->assertEquals('hoge-a', $select->tuple()['func']('hoge-'));
        $select->cast(null);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals('hoge-a', $select->tuple()->func('hoge-'));

        $select = $database->select([
            'test' => [
                'id',
                'name' => function ($name) { return $name . '-1'; },
            ],
        ])->limit(1);
        $this->assertEquals([1 => 'a-1'], $select->pairs());

        $select = $database->select([
            'test' => ['id' => function ($row) { return $row + 1; }],
        ])->limit(1);
        $this->assertEquals([0 => 2], $select->lists());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_callback($database)
    {
        $row = $database->select('test')->limit(1)->cast(function ($row) {
            return (object) $row;
        })->tuple();
        $this->assertInstanceOf('stdClass', $row);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_bindOrder($database)
    {
        $mainquery = $database->select('test1', ['first' => 'first']);
        $mainquery->addColumn([['second' => new Expression('?', 'second')]]);

        $subquery = $database->select('test2', ['third' => 'third']);
        $mainquery->addColumn([['sub' => $subquery]]);

        $this->assertEquals("SELECT test1.*, 'second' AS second, (SELECT test2.* FROM test2 WHERE third = 'third') AS sub FROM test1 WHERE first = 'first'", $mainquery->queryInto());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getLastInsertId($database)
    {
        $database->insert('test', ['name' => 'hoge']);
        $lastid = $database->getLastInsertId('test', 'id');
        $this->assertEquals($database->max('test.id'), $lastid);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_resetAutoIncrement($database)
    {
        // 未指定時は 1 …なんだけど、DBMS によっては変更時点でコケるので delete してからチェック
        $database->delete('auto');
        $database->resetAutoIncrement('auto');
        $database->insertOrThrow('auto', ['name' => 'hoge']);

        // reset 55 してから insert すれば 55 になるはず
        $database->resetAutoIncrement('auto', 55);
        $this->assertEquals(['id' => 55], $database->insertOrThrow('auto', ['name' => 'hoge']));

        // postgresql では modifyArray(on conflict による複数レコード挿入)でシーケンスが更新されない
        if (!$database->getCompatiblePlatform()->supportsIdentityAutoUpdate()) {
            $database->modifyArray('auto', [
                ['id' => 60, 'name' => 'hoge'],
                ['id' => 61, 'name' => 'fuga'],
                ['id' => 62, 'name' => 'piyo'],
            ]);
        }
        else {
            $database->insert('auto', ['id' => 62, 'name' => 'hoge']);
        }
        // null を与えると最大値+1になる
        $database->resetAutoIncrement('auto', null);
        $this->assertEquals(['id' => 63], $database->insertOrThrow('auto', ['name' => 'hoge']));

        $this->assertException('is not auto incremental', L($database)->resetAutoIncrement('noauto', 1));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_getAffectedRows($database)
    {
        $pk = $database->insertOrThrow('test', ['name' => 'hoge']);
        $this->assertEquals(1, $database->getAffectedRows());

        $database->update('test', ['name' => 'fuga'], ['id' => 0]);
        $this->assertEquals(0, $database->getAffectedRows());
        $database->update('test', ['name' => 'fuga'], $pk);
        $this->assertEquals(1, $database->getAffectedRows());

        $database->delete('test', ['id' => 0]);
        $this->assertEquals(0, $database->getAffectedRows());
        $database->delete('test', $pk);
        $this->assertEquals(1, $database->getAffectedRows());

        try_null([$database, 'delete'], 'test', 'unknown = 1');
        $this->assertNull($database->getAffectedRows());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_mapping($database)
    {
        $this->assertEquals(2, $database->count('t_article'));

        $row = $database->selectTuple('Comment + Article', ['comment_id' => 2]);
        $this->assertEquals([
            'comment_id' => 2,
            'article_id' => 1,
            'comment'    => 'コメント2です',
            'title'      => 'タイトルです',
            'checks'     => '',
        ], $row);

        /** @var \ryunosuke\Test\Entity\Article $row */
        $row = $database->entityTuple('Article.**', [], [], 1);
        $this->assertEquals('タイトルです', $row->title);
        $this->assertEquals('コメント1です', $row->Comment[1]->comment);
        $this->assertEquals('コメント2です', $row->Comment[2]->comment);
        $this->assertEquals('コメント3です', $row->Comment[3]->comment);

        $database->delete('Article');
        $this->assertEquals(0, $database->count('t_article'));

        $pri = $database->insertOrThrow('Article', ['article_id' => 1, 'title' => 'xxx', 'checks' => '']);
        $this->assertEquals('xxx', $database->selectValue('t_article.title', $pri));

        $database->update('Article', $pri + ['title' => 'yyy']);
        $this->assertEquals('yyy', $database->selectValue('t_article.title', $pri));

        $pri = $database->upsertOrThrow('Article', ['article_id' => 2, 'title' => 'zzz', 'checks' => '']);
        $this->assertEquals('zzz', $database->selectValue('t_article.title', $pri));

        $database->upsert('Article', $pri + ['title' => 'ZZZ']);
        $this->assertEquals('ZZZ', $database->selectValue('t_article.title', $pri));
    }
}
