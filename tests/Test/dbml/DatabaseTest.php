<?php

namespace ryunosuke\Test\dbml;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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
use ryunosuke\dbml\Logging\Logger;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Query\Clause\OrderBy;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\dbml\Transaction\Transaction;
use ryunosuke\dbml\Types\AbstractType;
use ryunosuke\Test\Database;
use ryunosuke\Test\Entity\Article;
use ryunosuke\Test\Entity\Comment;
use ryunosuke\Test\Entity\ManagedComment;
use ryunosuke\Test\Platforms\SqlitePlatform;
use function ryunosuke\dbml\array_order;
use function ryunosuke\dbml\array_remove;
use function ryunosuke\dbml\kvsort;
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

        // URL 指定+ランダム slave
        srand(1);
        $db = new Database([
            'url'  => 'mysqli+8.0.0:///masterslave',
            'host' => ['master', 'slave1', 'slave2'],
        ]);
        $this->assertNotSame($db->getMasterConnection(), $db->getSlaveConnection());
        $this->assertEquals([
            "driver"        => "mysqli",
            "serverVersion" => "8.0.0",
            "host"          => "master",
            "dbname"        => "masterslave",
            "driverOptions" => [],
        ], $db->getMasterConnection()->getParams());
        $this->assertEquals([
            "driver"        => "mysqli",
            "serverVersion" => "8.0.0",
            "host"          => "slave2",
            "dbname"        => "masterslave",
            "driverOptions" => [],
        ], $db->getSlaveConnection()->getParams());
        gc_collect_cycles();

        // logger and initCommand and cacheProvider
        $tmpdir = sys_get_temp_dir() . '/dbml/tmp';
        rm_rf($tmpdir);
        mkdir_p($tmpdir);
        $db = new Database(['url' => 'sqlite3:///:memory:'], [
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

        $this->assertException('$dbconfig must be', function () {
            /** @noinspection PhpParamsInspection */
            new Database(null);
        });
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
        $this->assertInstanceOf(SelectBuilder::class, $database->select('t', []));

        // select 系
        $this->assertEquals($database->selectArray('test'), $database->select('test')->array());
        $this->assertEquals($database->selectValue('test', [], [], 1), $database->select('test', [], [], 1)->value());
        $this->assertEquals($database->selectTuple('test', [], [], 1), $database->select('test', [], [], 1)->tuple());

        // select～ForUpdate|InShare 系(ロックされることを担保・・・は難しいのでエラーにならないことを担保)
        $this->assertEquals($database->selectArrayInShare('test'), $database->selectArrayForUpdate('test'));
        $this->assertEquals($database->selectAssocInShare('test'), $database->selectAssocForUpdate('test'));
        $this->assertEquals($database->selectListsInShare('test'), $database->selectListsForUpdate('test'));
        $this->assertEquals($database->selectPairsInShare('test'), $database->selectPairsForUpdate('test'));
        $this->assertEquals($database->selectTupleInShare('test', [], [], 1), $database->selectTupleForUpdate('test', [], [], 1));
        $this->assertEquals($database->selectValueInShare('test', [], [], 1), $database->selectValueForUpdate('test', [], [], 1));

        // select～OrThrow 系(見つかる場合に同じ結果になることを担保)
        $this->assertEquals($database->selectArray('test'), $database->selectArrayOrThrow('test'));
        $this->assertEquals($database->selectAssoc('test'), $database->selectAssocOrThrow('test'));
        $this->assertEquals($database->selectLists('test'), $database->selectListsOrThrow('test'));
        $this->assertEquals($database->selectPairs('test'), $database->selectPairsOrThrow('test'));
        $this->assertEquals($database->selectValue('test', [], [], 1), $database->selectValueOrThrow('test', [], [], 1));
        $this->assertEquals($database->selectTuple('test', [], [], 1), $database->selectTupleOrThrow('test', [], [], 1));

        // select～OrThrow 系(見つからなかった場合に例外が投がることを担保)
        $ex = new NonSelectedException('record is not found');
        $this->assertException($ex, L($database)->selectArrayOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectAssocOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectListsOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectPairsOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectTupleOrThrow('test', ['1=0']));
        $this->assertException($ex, L($database)->selectValueOrThrow('test', ['1=0']));

        // select～ForAffect 系(見つからなかった場合に例外が投がることを担保)
        $this->assertEquals($database->selectArray('test'), $database->selectArrayForAffect('test'));
        $this->assertException($ex, L($database)->selectArrayForAffect('test', ['1=0']));
        $this->assertException($ex, L($database)->selectAssocForAffect('test', ['1=0']));
        $this->assertException($ex, L($database)->selectListsForAffect('test', ['1=0']));
        $this->assertException($ex, L($database)->selectPairsForAffect('test', ['1=0']));
        $this->assertException($ex, L($database)->selectTupleForAffect('test', ['1=0']));
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

        // entity～ 系(流す程度に)
        $this->assertEquals($database->entityArrayOrThrow('test'), $database->entityArrayForAffect('test'));
        $this->assertEquals($database->entityAssocOrThrow('test'), $database->entityAssocForAffect('test'));
        $this->assertEquals($database->entityTupleOrThrow('test', [], [], 1), $database->entityTupleForAffect('test', [], [], 1));
        $this->assertEquals($database->entityArrayForUpdate('test'), $database->entityArrayInShare('test'));
        $this->assertEquals($database->entityAssocForUpdate('test'), $database->entityAssocInShare('test'));
        $this->assertEquals($database->entityTupleForUpdate('test', [], [], 1), $database->entityTupleInShare('test', [], [], 1));
        $this->assertException($ex, L($database)->entityArrayForAffect('test', ['1=0']));
        $this->assertException($ex, L($database)->entityAssocForAffect('test', ['1=0']));
        $this->assertException($ex, L($database)->entityTupleForAffect('test', ['1=0']));

        // affectOrThrow(作用した場合に主キーが返ることを担保)
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

        // affectAndPrimary(作用した場合に主キーが返ることを担保)
        $this->assertEquals(['id' => 88], $database->insertAndPrimary('test', ['id' => 88]));
        $this->assertEquals(['id' => 88], $database->upsertAndPrimary('test', ['id' => 88, 'name' => 'hogera']));
        $this->assertEquals(['id' => 88], $database->modifyAndPrimary('test', ['id' => 88, 'name' => 'rageho']));
        // ON AUTO_INCREMENT
        $lastid = $database->insertAndPrimary('test', ['name' => 'hogera']);
        $this->assertEquals(['id' => $database->getLastInsertId('test', 'id')], $lastid);
        $lastid = $database->upsertAndPrimary('test', ['name' => 'hogera']);
        $this->assertEquals(['id' => $database->getLastInsertId('test', 'id')], $lastid);
        $lastid = $database->modifyAndPrimary('test', ['name' => 'hogera']);
        $this->assertEquals(['id' => $database->getLastInsertId('test', 'id')], $lastid);
        // NO AUTO_INCREMENT
        $this->assertEquals(['id' => 'd'], $database->insertAndPrimary('noauto', ['id' => 'd', 'name' => 'fugara']));
        $this->assertEquals(['id' => 'e'], $database->upsertAndPrimary('noauto', ['id' => 'e', 'name' => 'fugara']));
        $this->assertEquals(['id' => 'f'], $database->modifyAndPrimary('noauto', ['id' => 'f', 'name' => 'fugara']));

        // update/delete
        $this->assertEquals(['id' => 1], $database->updateAndPrimary('test', ['name' => 'hogera1'], ['id' => 1]));
        $this->assertEquals(['id' => 1], $database->reviseAndPrimary('test', ['name' => 'hogera2'], ['id' => 1]));
        $this->assertEquals(['id' => 1], $database->upgradeAndPrimary('test', ['name' => 'hogera3'], ['id' => 1]));
        $this->assertEquals(['id' => 1], $database->invalidAndPrimary('test', ['id' => 1], ['name' => 'deleted']));
        $this->assertEquals(['id' => 1], $database->deleteAndPrimary('test', ['id' => 1]));
        $this->assertEquals(['id' => 2], $database->removeAndPrimary('test', ['id' => 2]));
        $this->assertEquals(['id' => 3], $database->destroyAndPrimary('test', ['id' => 3]));

        // 作用行系(見つからなかった場合に例外が投がることを担保)
        $ex = new NonAffectedException('affected row is nothing');
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $database = $database->context(['filterNullAtNotNullColumn' => false]); // not null に null を入れることでエラーを発生させる
            $this->assertException($ex, L($database)->insert('test', ['id' => 9, 'name' => 'hoge'], ['primary' => 1, 'ignore' => true]));
            if ($database->getCompatiblePlatform()->getName() !== 'mysql') {
                $this->assertException($ex, L($database)->modify('test', ['id' => 9, 'name' => null], [], 'PRIMARY', ['primary' => 1, 'ignore' => true]));
            }
        }
        $this->assertException($ex, L($database)->updateOrThrow('test', ['name' => 'd'], ['id' => -1]));
        $this->assertException($ex, L($database)->deleteOrThrow('test', ['id' => -1]));
        $this->assertException($ex, L($database)->removeOrThrow('test', ['id' => -1]));
        $this->assertException($ex, L($database)->destroyOrThrow('test', ['id' => -1]));
        if ($database->getCompatibleConnection()->getName() === 'pdo-mysql') {
            $this->assertException($ex, L($database)->upsertOrThrow('test', ['id' => 9, 'name' => 'i', 'data' => '']));
        }

        // affectConditionally
        $database->truncate('noauto');
        $this->assertEquals(['id' => 'a'], $database->insertAndPrimary('noauto[id:""]', ['id' => 'a', 'name' => 'hoge']));
        $this->assertEquals(['id' => 'b'], $database->upsertAndPrimary('noauto[id:""]', ['id' => 'b', 'name' => 'fuga']));
        $this->assertEquals(['id' => 'c'], $database->modifyAndPrimary('noauto[id:""]', ['id' => 'c', 'name' => 'piyo']));
        $this->assertEquals(['id' => 'a'], $database->insertAndPrimary('noauto[id:a]', ['id' => 'a', 'name' => 'hoge']));
        $this->assertEquals(['id' => 'b'], $database->upsertAndPrimary('noauto[id:b]', ['id' => 'b', 'name' => 'fuga']));
        $this->assertEquals(['id' => 'c'], $database->modifyAndPrimary('noauto[id:c]', ['id' => 'c', 'name' => 'piyo']));

        // affectIgnore
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $database = $database->context(['filterNullAtNotNullColumn' => false]); // not null に null を入れることでエラーを発生させる
            $database->truncate('noauto');
            $database->insert('noauto', ['id' => 'x', 'name' => '']);
            $this->assertEquals(['id' => 'b'], $database->createIgnore('noauto', ['id' => 'b', 'name' => 'hoge']));
            $this->assertEquals(['id' => 'a'], $database->insertIgnore('noauto', ['id' => 'a', 'name' => 'hoge']));
            $this->assertEquals(['id' => 'a'], $database->updateIgnore('noauto', ['name' => 'fuga'], ['id' => 'a']));
            $this->assertEquals(['id' => 'a'], $database->modifyIgnore('noauto', ['id' => 'a', 'name' => 'piyo']));
            $this->assertEquals([], $database->createIgnore('noauto', ['id' => 'x']));
            $this->assertEquals([], $database->insertIgnore('noauto', ['id' => 'x']));
            if ($database->getCompatibleConnection()->getName() !== 'mysqli') {
                $this->assertEquals([], $database->updateIgnore('noauto', ['id' => 'x'], ['id' => 'a']));
            }
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
            $updatedRow = $database->getCompatibleConnection()->getName() === 'mysqli' ? 2 : 0;
            $this->assertEquals($updatedRow, $database->updateArrayIgnore('noauto', [
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
            $this->assertEquals(['id' => 1], $database->saveIgnore('foreign_p', ['id' => 1]));

            if ($database->getPlatform() instanceof MySQLPlatform) {
                $this->assertEquals(['id' => 1], $database->deleteIgnore('foreign_p', ['id' => 1]));
            }
        }

        if ($database->getCompatibleConnection()->getName() === 'pdo-mysql') {
            $this->assertException($ex, L($database)->upsertOrThrow('test', ['id' => 9, 'name' => 'i', 'data' => '']));
        }

        if ($database->getCompatibleConnection()->getName() === 'pdo-mysql') {
            $this->assertException($ex, L($database)->upsertOrThrow('test', ['id' => 9, 'name' => 'i', 'data' => '']));
        }

        // テーブル記法＋OrThrowもきちんと動くことを担保
        $this->assertEquals(['id' => 9], $database->updateOrThrow('test[id: 9]', ['name' => 'hogera']));

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
    }

    function test___debugInfo()
    {
        $debugString = print_r(self::getDummyDatabase(), true);
        $this->assertStringNotContainsString('txConnection:', $debugString);
        $this->assertStringNotContainsString('cache:', $debugString);
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

        $cplatform = new class ( new \ryunosuke\Test\Platforms\SqlitePlatform() ) extends CompatiblePlatform { };

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
        catch (\Exception) {
            $this->assertFalse($database->getInsertSet());
        }
        try {
            $st = $database->stack();
            $st->setInsertSet(true)->fetchTuple('invalid query.');
        }
        catch (\Exception) {
            $this->assertFalse($database->getInsertSet());
        }
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
        $urlType = new class extends AbstractType {
            public function getName()
            {
                return 'url';
            }
        };
        $database->setAutoCastType([
            'url'     => $urlType,
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
            'url'     => [
                'select' => true,
                'affect' => true,
            ],
            'hoge'    => [
                'select' => true,
                'affect' => true,
            ],
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
        $database->refresh();
        $database->recache();

        $database->declareCommonTable([
            'fibonacci(n0, n)' => 'select 0, 1 union all select n, n0 + n from fibonacci WHERE n < 50',
            'selectbuilder'    => fn() => $database->selectAvg('aggregate.group_id2', [], 'group_id1'),
            'arrays'           => [
                'column'  => ['aggregate' => ['group_id1', 'max_id2' => 'MAX(group_id2)']],
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
        ], $database->selectPairs('selectbuilder'));
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
                    'select' => ['LOWER(?)' => 'A'],
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
        $this->assertEquals('a', $database->selectValue('test2.is_a', [], ['id'], 1));
        $this->assertEquals('a', $database->selectValue('test2.is_a', [], ['id' => false], 1));

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
    function test_addRelation_condition($database)
    {
        $fkeys = $database->addRelation([
            'tran_table1' => [
                'master_table' => [
                    'auto_1tomaster' => [
                        'master_id' => 'subid',
                        'options'   => [
                            'onUpdate'  => 'CASCADE',
                            'onDelete'  => 'CASCADE',
                            'condition' => ['category' => 'tran1'],
                        ],
                    ],
                ],
            ],
            'tran_table2' => [
                'master_table' => [
                    'auto_2tomaster' => [
                        'master_id' => 'subid',
                        'options'   => [
                            'onUpdate'  => 'SET NULL',
                            'onDelete'  => 'SET NULL',
                            'condition' => ['category' => 'tran2'],
                        ],
                    ],
                ],
            ],
            'tran_table3' => [
                'master_table' => [
                    'auto_3tomaster' => [
                        'master_id' => 'subid',
                        'options'   => [
                            'onUpdate'  => 'RESTRICT',
                            'onDelete'  => 'NO ACTION',
                            'condition' => ['category' => 'tran3'],
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertEquals(['auto_1tomaster', 'auto_2tomaster', 'auto_3tomaster'], $fkeys);

        $fk1 = $database->getSchema()->getTableForeignKeys('tran_table1')['auto_1tomaster'];
        $this->assertEquals('CASCADE', $fk1->getOption('onUpdate'));
        $this->assertEquals('CASCADE', $fk1->getOption('onDelete'));
        $this->assertEquals(['category' => 'tran1'], $fk1->getOption('condition'));
        $this->assertEquals(true, $fk1->getOption('virtual'));

        $fk2 = $database->getSchema()->getTableForeignKeys('tran_table2')['auto_2tomaster'];
        $this->assertEquals('SET NULL', $fk2->getOption('onUpdate'));
        $this->assertEquals('SET NULL', $fk2->getOption('onDelete'));
        $this->assertEquals(['category' => 'tran2'], $fk2->getOption('condition'));
        $this->assertEquals(true, $fk2->getOption('virtual'));

        $fk3 = $database->getSchema()->getTableForeignKeys('tran_table3')['auto_3tomaster'];
        $this->assertEquals('RESTRICT', $fk3->getOption('onUpdate'));
        $this->assertEquals('NO ACTION', $fk3->getOption('onDelete'));
        $this->assertEquals(['category' => 'tran3'], $fk3->getOption('condition'));
        $this->assertEquals(true, $fk3->getOption('virtual'));

        // subwhere
        $rows = $database->selectArray([
            'master_table' => '*',
        ], [
            $database->subexists('tran_table1'),
            'subid' => 10,
        ]);
        $this->assertEquals([
            [
                "category" => "tran1",
                "subid"    => "10",
            ],
        ], $rows);

        // join1
        $rows = $database->selectArray([
            'master_table' => [
                '*',
                '+tran_table1' => ['*'],
            ],
        ], [
            'subid' => 10,
        ]);
        $this->assertEquals([
            [
                "category"  => "tran1",
                "subid"     => "10",
                "id"        => "101",
                "master_id" => "10",
            ],
            [
                "category"  => "tran1",
                "subid"     => "10",
                "id"        => "201",
                "master_id" => "10",
            ],
        ], $rows);

        // join2
        $rows = $database->selectArray([
            'tran_table1 TT' => [
                '*',
                '+master_table MT' => ['*'],
            ],
        ], [
            'subid' => 10,
        ]);
        $this->assertEquals([
            [
                "category"  => "tran1",
                "subid"     => "10",
                "id"        => "101",
                "master_id" => "10",
            ],
            [
                "category"  => "tran1",
                "subid"     => "10",
                "id"        => "201",
                "master_id" => "10",
            ],
        ], $rows);

        // subtable
        $rows = $database->selectArray([
            'master_table' => [
                'tran_table1 as t1' => ['*'],
                't2'                => $database->subselectArray('tran_table2')->setLazyMode('fetch'),
                't2tuple'           => $database->subselectTuple('tran_table2', [], [], 1),
            ],
        ], [
            'subid' => 10,
        ]);
        $this->assertEquals([
            [
                "t1"       => [
                    101 => [
                        "id"        => "101",
                        "master_id" => "10",
                    ],
                    201 => [
                        "id"        => "201",
                        "master_id" => "10",
                    ],
                ],
                "t2"       => [],
                "t2tuple"  => false,
                "category" => "tran1",
                "subid"    => "10",
            ],
            [
                "t1"       => [],
                "t2"       => [
                    [
                        "id"        => "101",
                        "master_id" => "10",
                    ],
                    [
                        "id"        => "201",
                        "master_id" => "10",
                    ],
                ],
                "t2tuple"  => [
                    "id"        => "101",
                    "master_id" => "10",
                ],
                "category" => "tran2",
                "subid"    => "10",
            ],
            [
                "t1"       => [],
                "t2"       => [],
                "t2tuple"  => false,
                "category" => "tran3",
                "subid"    => "10",
            ],
        ], $rows);

        if ($database->getPlatform() instanceof SqlitePlatform || $database->getPlatform() instanceof MySQLPlatform) {
            $sqls = $database->dryrun()->delete('master_table', [
                'subid' => 10,
            ]);
            $this->assertEquals([
                "DELETE FROM tran_table1 WHERE (master_id) IN (SELECT master_table.subid FROM master_table WHERE subid = '10')",
                "UPDATE tran_table2 SET master_id = NULL WHERE (master_id) IN (SELECT master_table.subid FROM master_table WHERE subid = '10')",
                "DELETE FROM master_table WHERE subid = '10'",
            ], $sqls);

            $sqls = $database->dryrun()->update('master_table', [
                'subid' => 21,
            ], [
                'subid' => 20,
            ]);
            $this->assertEquals([
                "UPDATE tran_table1 SET master_id = '21' WHERE (master_id) IN (SELECT master_table.subid FROM master_table WHERE subid = '20')",
                "UPDATE tran_table2 SET master_id = NULL WHERE (master_id) IN (SELECT master_table.subid FROM master_table WHERE subid = '20')",
                "UPDATE master_table SET subid = '21' WHERE subid = '20'",
            ], $sqls);

            if ($database->getPlatform() instanceof MySQLPlatform) {
                $affected = $database->delete('master_table', [
                    'subid' => 10,
                ]);
                // 通常外部キーと同様に affected row には換算されない
                $this->assertEquals(3, $affected);
                // tran_table1 は CASCADE なので消えている
                $this->assertEquals([], $database->selectLists('tran_table1.id', ['master_id' => 10]));
                // tran_table2 は SET NULL なので NULL
                $this->assertEquals([101, 201], $database->selectLists('tran_table2.id', ['master_id' => null]));

                $affected = $database->update('master_table', [
                    'subid' => 21,
                ], [
                    'subid' => 20,
                ]);
                // 通常外部キーと同様に affected row には換算されない
                $this->assertEquals(3, $affected);
                // tran_table1 は CASCADE なので更新されている
                $this->assertEquals([], $database->selectLists('tran_table1.id', ['master_id' => 20]));
                // tran_table2 は SET NULL なので NULL
                $this->assertEquals([101, 102, 201, 202], $database->selectLists('tran_table2.id', ['master_id' => null]));
            }

            // RESTRICT が効く
            $this->assertException('Cannot delete or update', L($database)->delete('master_table', [
                'subid' => 100,
            ]));
            $this->assertException('Cannot delete or update', L($database)->update('master_table', [
                'subid' => 101,
            ], [
                'subid' => 100,
            ]));

            // 外部キーを無効化すれば作用しない
            $database->switchForeignKey(false, 'auto_3tomaster');
            $database->delete('master_table', [
                'subid' => 100,
            ]);
            $database->update('master_table', [
                'subid' => 101,
            ], [
                'subid' => 100,
            ]);
        }
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

        // さらに追加しても default:false にすれば・・・
        $fkey = $database->addForeignKey('foreign_c1', 'foreign_p', 'name', 'c1_p');
        $database->getSchema()->setForeignKeyMetadata($fkey, ['joinable' => false]);
        // リレーションに影響は出ない
        $this->assertEquals('SELECT P.*, C.* FROM foreign_p P INNER JOIN foreign_c1 C ON C.id = P.id', (string) $database->select('foreign_p P + foreign_c1 C'));
        // 明示的に指定すれば使える
        $this->assertEquals('SELECT P.*, C.* FROM foreign_p P INNER JOIN foreign_c1 C ON C.name = P.name', (string) $database->select('foreign_p P + foreign_c1:c1_p C'));

        // 後処理
        $database->getSchema()->refresh();
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_switchForeignKey($database)
    {
        // トランザクションありきの RDBMS がある
        $database->begin();
        try {
            $this->assertEquals(-1, $database->switchForeignKey(false, 'fk_parentchild1'));
            $this->assertEquals(-1, $database->switchForeignKey(false, 'fk_parentchild2'));

            // 外部キーエラーは発生しない
            $database->insert('foreign_c1', ['id' => 999, 'seq' => 1, 'name' => 'c1name1']);
            $database->insert('foreign_c2', ['cid' => 999, 'seq' => 2, 'name' => 'c2name1']);
        }
        finally {
            $database->rollback();
        }
        $this->assertEquals(0, $database->switchForeignKey(true, 'fk_parentchild1'));
        $this->assertEquals(0, $database->switchForeignKey(true, 'fk_parentchild2'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_view($database)
    {
        $v_blog_columns = $database->getSchema()->getTableColumns('v_blog');
        $this->assertEquals('integer', $v_blog_columns['article_id']->getType()->getName());
        $this->assertEquals('string', $v_blog_columns['title']->getType()->getName());
        $this->assertEquals('integer', $v_blog_columns['comment_id']->getType()->getName());
        $this->assertEquals('text', $v_blog_columns['comment']->getType()->getName());

        // まず v_blog<->t_article のリレーションがないことを担保して・・・
        $this->assertEquals('SELECT A.*, B.* FROM t_article A, v_blog B', (string) $database->select('t_article A,v_blog B'));
        // 仮想キーを追加すると・・・
        $database->addForeignKey('v_blog', 't_article', 'article_id');
        // リレーションが発生するはず
        $this->assertEquals('SELECT A.*, B.* FROM t_article A LEFT JOIN v_blog B ON B.article_id = A.article_id', (string) $database->select('t_article A < v_blog B'));

        // 後処理
        $database->getSchema()->refresh();
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
        $stmt = new Statement("select $hogefuga as hogefuga", ['hoge'], $database);
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
        $stmt = $database->prepare()->insert('test', ['id' => $database->raw(':id'), ':name']);
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
        $stmt = $database->prepare()->update('test', [':name'], ['id = :id']);
        $stmt->executeAffect(['id' => 101, 'name' => 'updateXXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'updateYYY']);
        $this->assertEquals(['updateXXX', 'updateYYY'], $database->selectLists('test.name', ['id' => [101, 102]]));

        // :hoge, :fuga の簡易記法
        $stmt = $database->prepare()->update('test', [':name'], [':id']);
        $stmt->executeAffect(['id' => 101, 'name' => 'bindXXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'bindYYY']);
        $this->assertEquals(['bindXXX', 'bindYYY'], $database->selectLists('test.name', ['id' => [101, 102]]));

        // delete
        $stmt = $database->prepare()->delete('test', ['id = :id']);
        $stmt->executeAffect(['id' => 101]);
        $stmt->executeAffect(['id' => 102]);
        $this->assertEquals([], $database->selectLists('test.name', ['id' => [101, 102]]));

        if ($database->getCompatiblePlatform()->supportsReplace()) {
            // replace
            $stmt = $database->prepare()->replace('test', [':id', ':name', ':data']);
            $stmt->executeAffect(['id' => 101, 'name' => 'replaceXXX', 'data' => '']);
            $stmt->executeAffect(['id' => 102, 'name' => 'replaceXXX', 'data' => '']);
            $this->assertEquals(['replaceXXX', 'replaceXXX'], $database->selectLists('test.name', ['id' => [101, 102]]));
        }

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            // modify
            $stmt = $database->prepare()->modify('test', [':id', ':name', ':data']);
            $stmt->executeAffect(['id' => 101, 'name' => 'modifyXXX', 'data' => '']);
            $stmt->executeAffect(['id' => 102, 'name' => 'modifyYYY', 'data' => '']);
            $stmt->executeAffect(['id' => 103, 'name' => 'modifyZZZ', 'data' => '']);
            $this->assertEquals(['modifyXXX', 'modifyYYY', 'modifyZZZ'], $database->selectLists('test.name', ['id' => [101, 102, 103]]));
        }

        // bulk insert
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $stmt = $database->prepare()->insertArray('test', [
                ['id' => 201, 'name' => $database->raw(':name1')],
                ['id' => 202, 'name' => $database->raw(':name2')],
                ['id' => 203, 'name' => $database->raw(':name3')],
            ]);
            $stmt->executeAffect(['name1' => 'insertArrayX1', 'name2' => 'insertArrayY1', 'name3' => 'insertArrayZ1']);
            $this->assertEquals(['insertArrayX1', 'insertArrayY1', 'insertArrayZ1'], $database->selectLists('test.name', ['id' => [201, 202, 203]]));
        }

        // 例外発生時は元に戻るはず
        $database->setOption('preparing', 0);
        $this->assertException(Schema\SchemaException::tableDoesNotExist('notfound'), L($database->prepare())->insert('notfound', [1]));
        $this->assertSame(0, $database->getOption('preparing'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_dryrun($database)
    {
        // クエリ文字列配列を返す
        $this->assertEquals(["DELETE FROM test WHERE id = '1'"], $database->dryrun()->delete('test', ['id' => 1]));

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
    function test_convertTo($database)
    {
        $database->insert('misctype', [
            'id'        => 9,
            'pid'       => true,
            'cint'      => false,
            'cfloat'    => true,
            'cdecimal'  => false,
            'cstring'   => true,
            'ctext'     => false,
            'cbinary'   => '1',
            'cblob'     => '0',
            'carray'    => true,
            'cjson'     => "null",
            'cdate'     => 1234567890,
            'cdatetime' => 1234567890.123,
        ]);
        $microsecond = strpos($database->getPlatform()->getDateTimeFormatString(), '.u') === false ? "" : ".122999";
        $expected = [
            'id'        => 9,
            'pid'       => 1,
            'cint'      => 0,
            'cfloat'    => 1,
            'cdecimal'  => 0,
            'cstring'   => 1,
            'ctext'     => 0,
            'carray'    => 1,
            'cjson'     => "null",
            'cdate'     => '2009-02-14',
            'cdatetime' => "2009-02-14 08:31:30{$microsecond}",
        ];
        $this->assertEquals($expected, array_intersect_key($database->selectTuple('misctype', ['id' => 9]), $expected));

        if ($database->getPlatform() instanceof MySQLPlatform) {
            $database->insert('misctype', [
                'id'        => 10,
                'cint'      => false,
                'cfloat'    => true,
                'cdecimal'  => false,
                'cstring'   => 'あいうえおアイウエオ',
                'ctext'     => str_repeat('a', 256),
                'cbinary'   => 'かきくけこカキクケコ',
                'cblob'     => str_repeat('b', 256),
                'cdate'     => '2009-02-14',
                'cdatetime' => "2009-02-14 08:31:30",
            ]);
            $expected = [
                'cstring' => 'あいうえおアイウ',
                'ctext'   => str_repeat('a', 255),
                'cbinary' => 'かきくけこカキク',
                'cblob'   => str_repeat('b', 255),
            ];
            $this->assertEquals($expected, array_intersect_key($database->selectTuple('misctype', ['id' => 10]), $expected));
        }
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
        $this->assertIsArray($database->fetchTuple('select 1 as "A.id", 1 as "B.id" from test where id = 1'));
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
        $this->assertIsArray($database->fetchTuple('select NULL as "A.id", 1 as "B.id", 1 as "C.id" from test where id = 1'));
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
        catch (\Exception) {
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

        // 基本的には Where::build と同じだし、実装も真似ている
        // のでここでは代表的な演算子のみに留める

        // = になるはず
        $assert('(column_name = ?)', [1], $database->operator(['column_name' => 1]));
        // IN になるはず
        $assert('(column_name IN (?,?,?))', [1, 2, 3], $database->operator(['column_name' => [1, 2, 3]]));
        // LIKE演算子明示
        $assert('(column_name LIKE ?)', ['%hogera%'], $database->operator(['column_name:%LIKE%' => ['hogera']]));
        // 区間演算子明示
        $assert('(column_name >= ? AND column_name <= ?)', [1, 99], $database->operator(['column_name:[~]' => [1, 99]]));
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
        $this->assertEquals("'hoge'", $database->quote(fn() => 'hoge', null));

        // int を特別扱いしてるので担保する（SQLServer は int をそのまま返すのがテスト上都合が悪い）
        if ($database->getCompatibleConnection()->getName() !== 'sqlsrv') {
            $this->assertEquals($database->getConnection()->quote(1), $database->quote(1, null));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_queryInto($database)
    {
        // 非連番パラメータの時の挙動がドライバーで異なる可能性があるので担保する
        $this->assertEquals([
            'a' => 1,
            'b' => 2,
        ], $database->fetchTuple('select ? as a, ? as b', [
            3 => 1,
            1 => 2,
        ]));

        $this->assertEquals("'1','2'", $database->queryInto('?,?', [1, 2]));
        $this->assertEquals("'1','2'", $database->queryInto('?,?', [9 => 1, 8 => 2]));
        $this->assertEquals("'1','2'", $database->queryInto(':hoge,:fuga', ['hoge' => 1, 'fuga' => 2]));
        $this->assertEquals("hoge", $database->queryInto('hoge'));
        $this->assertEquals("'1','2'", $database->queryInto('?,:hoge', [1, 'hoge' => 2]));
        $this->assertEquals("'2','1','3'", $database->queryInto(':hoge,?,:fuga', [1, 'fuga' => 3, 'hoge' => 2]));
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
        $this->assertException('does not have', L($database)->queryInto('select ?', []));
        $this->assertException('long', L($database)->queryInto('select ?', [1, 2]));

        // 不一致だと予期せぬ動作になることがあるのでテスト
        $this->assertException('does not have', L($database)->queryInto(':hoge', ['fuga' => 1]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_syntax($database)
    {
        $cplatform = $database->getCompatiblePlatform();

        // CASE WHEN の条件なしとか ELSE 節とかを実際に投げてみて正当性を担保
        $syntax = $cplatform->getCaseWhenSyntax("2", [1 => 10, 2 => 20], 99);
        $this->assertEquals(20, $database->fetchValue("SELECT $syntax", $syntax->getParams()));
        $syntax = $cplatform->getCaseWhenSyntax("9", [1 => 10, 2 => 20], 99);
        $this->assertEquals(99, $database->fetchValue("SELECT $syntax", $syntax->getParams()));

        // SQLServer は LIKE に特殊性があるので実際に投げてみて正当性を担保
        $this->assertSame([], $database->selectArray('test', ['name:LIKE' => 'w%r_o[d',]));

        // SQLServer は GROUP_CONCAT に対応していないのでトラップ
        $this->trapThrowable('is not supported by platform');
        $syntax = $cplatform->getGroupConcatSyntax('name', '|');
        $this->assertEquals('a|b|c|d|e|f|g|h|i|j', $database->fetchValue("SELECT $syntax FROM test"));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_export($database)
    {
        $path = sys_get_temp_dir() . '/export.tmp';

        $database->exportCsv(['file' => $path], 'test');
        $this->assertStringEqualsFile($path, "1,a,\n2,b,\n3,c,\n4,d,\n5,e,\n6,f,\n7,g,\n8,h,\n9,i,\n10,j,\n");

        $database->exportJson(['file' => $path], 'test');
        $this->assertJson(file_get_contents($path));

        $database->setCheckSameColumn('loose');
        $this->assertException('cause loose', L($database)->export('csv', 'select test.id, 0 as id from test'));
        $database->setCheckSameColumn(null);

        $this->assertException(new \BadMethodCallException('undefined'), L($database)->export('Hoge', 'select 1'));
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
    function test_differ($database)
    {
        // PostgreSQL は string = int の比較でコケるのでスルー
        if ($database->getPlatform() instanceof PostgreSQLPlatform) {
            return;
        }

        $this->assertEquals([], $database->differ([], 'multiprimary', ['multiprimary.mainid' => 1]));

        $this->assertEquals([
            'b' => ['subid' => 1, 'name' => 'x'],
            'c' => ['subid' => 2, 'name' => 'y', 'dummy' => null],
            'd' => ['subid' => 3, 'name' => 'x'],
            'e' => ['subid' => 6, 'name' => 'f'],
        ], $database->differ([
            'a' => ['subid' => 1, 'name' => 'a'],
            'b' => ['subid' => 1, 'name' => 'x'],
            'c' => ['subid' => 2, 'name' => 'y', 'dummy' => null],
            'd' => ['subid' => 3, 'name' => 'x'],
            'e' => ['subid' => 6, 'name' => 'f'],
        ], 'multiprimary', ['multiprimary.mainid' => 1]));

        $this->assertException('row is empty', L($database)->differ([
            ['unmatch1' => 1],
            ['unmatch2' => 2],
        ], 'multiprimary'));
        $this->assertException('column is unmatched', L($database)->differ([
            ['mainid' => 1],
            ['subid' => 1],
        ], 'multiprimary'));
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
        $tuple = $database->entityTuple('t_article(1)');
        $this->assertEquals([$tuple], $database->entityArray('t_article(1)'));
        $this->assertEquals([1 => $tuple], $database->entityAssoc('t_article(1)'));

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
            'datetimetz'   => [
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
            'closure'      => [
                'select' => fn($value) => explode(',', $value),
                'affect' => false,
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
            'cstring'   => 'ho,ge',
            'ctext'     => 'fuga',
            'carray'    => [1, 2, 3, 4, 5, 6, 7, 8, 9],
            'cjson'     => new Expression('?', [json_encode(['a' => 'A'])]),
        ]);
        $row = $database->selectTuple([
            'misctype MT' => [
                'cint',
                'cdatetime',
                'cstring|closure'     => 'cstring',
                'carray',
                'cjson',
                'tarray|simple_array' => 'carray',
                'tjson|json'          => 'cjson',
            ],
            ''            => [
                'now' => $database->getCompatiblePlatform()->getNowExpression(0),
            ],
        ], [], [], 1);
        $this->assertSame(1, $row['cint']);
        $this->assertEquals(['ho', 'ge'], $row['cstring']);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['tarray']);
        $this->assertEquals(['a' => 'A'], $row['tjson']);
        if (!$database->getPlatform() instanceof SQLServerPlatform && $database->getCompatibleConnection()->getName() !== 'sqlite3') {
            $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray']);
            $this->assertEquals(['a' => 'A'], $row['cjson']);
        }
        if (!$database->getPlatform() instanceof SqlitePlatform) {
            $this->assertInstanceOf('\DateTime', $row['cdatetime']);
            $this->assertInstanceOf('\DateTime', $row['now']);
        }

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

        $supported = $database->getCompatibleConnection()->getSupportedMetadata();
        if ($supported['table&&column']) {
            // テーブル取得がサポートされていれば * だけで型を活かすことができる
            $row = $database->selectTuple('misctype', [], [], 1);
            $this->assertSame(1, $row['cint']);
            $this->assertInstanceOf('\DateTime', $row['cdatetime']);
            $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray']);

            // 生クエリでも可能
            $row = $database->fetchTuple('select * from misctype');
            $this->assertSame(1, $row['cint']);
            $this->assertInstanceOf('\DateTime', $row['cdatetime']);
            $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray']);
        }
        if ($supported['actualColumnName']) {
            // オリジナルカラム取得もサポートされていればエイリアスを張っても型を活かすことができる
            $row = $database->fetchTuple('select cint as cint2, cdatetime as cdatetime2, carray as carray2 from misctype MT');
            $this->assertSame(1, $row['cint2']);
            $this->assertInstanceOf('\DateTime', $row['cdatetime2']);
            $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9], $row['carray2']);
        }

        // ビルダレベルのカバレッジ用
        $dummy = self::getDummyDatabase();
        $dummy->modify('test', ['id' => 1]);
        $row = $dummy->selectTuple('test.id', ['id' => 1]);
        $this->assertSame(1, $row['id']);
        $select = $dummy->select([])->from($dummy->select('test', ['' => 1]), 't')->addSelect(['id' => 't.id', 'hoge.num' => 123]);
        $row = $select->tuple();
        $this->assertSame(1, $row['id']);
        $this->assertSame(123, $row['hoge.num']);

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
        $it = $database->yieldLists('test.name', ['id' => [2, 3]]);
        $this->assertInstanceOf(Yielder::class, $it);
        $this->assertEquals(['b', 'c'], iterator_to_array($it));

        $it = $database->yieldArray('test', ['id' => 1]);
        $this->assertInstanceOf(Yielder::class, $it);
        $this->assertEquals([
            [
                'id'   => '1',
                'name' => 'a',
                'data' => '',
            ],
        ], iterator_to_array($it));

        $it = $database->yieldArray($database->select([
            'test' => [
                'id',
                'name',
                'test1{id:id}' => [
                    'name1',
                ],
                'test2{id:id}' => [
                    'name2',
                ],
            ],
        ], ['id' => [1, 2, 3, 4]]), ['id' => [2, 3]]);
        $this->assertEquals([
            [
                "id"    => "2",
                "name"  => "b",
                "test1" => [
                    "name1" => "b",
                ],
                "test2" => [
                    "name2" => "B",
                ],
            ],
            [
                "id"    => "3",
                "name"  => "c",
                "test1" => [
                    "name1" => "c",
                ],
                "test2" => [
                    "name2" => "C",
                ],
            ],
        ], iterator_to_array($it));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_perform($database)
    {
        $database = $database->context(['checkSameKey' => 'noallow']);
        $rows = [
            $row1 = ['id' => 'k', 'value' => 'v1'],
            $row2 = ['id' => 'k', 'value' => 'v2'],
        ];

        $this->assertException("duplicated key k", L($database)->perform($rows, 'assoc'));
        $this->assertException("duplicated key k", L($database)->perform($rows, 'pairs'));
        $this->assertException("missing value of k", L($database)->perform([['k']], 'pairs'));

        $database = $database->context(['checkSameKey' => 'skip']);

        $this->assertEquals(['k' => $row1], $database->perform($rows, 'assoc'));
        $this->assertEquals(['k' => end($row1)], $database->perform($rows, 'pairs'));

        $database = $database->context(['checkSameKey' => null]);

        $this->assertEquals(['k' => $row2], $database->perform($rows, 'assoc'));
        $this->assertEquals(['k' => end($row2)], $database->perform($rows, 'pairs'));

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
    function test_echoAnnotation($database)
    {
        // 定義に違いがあるわけではない（実はあるけど）ので sqlite だけで十分
        if (!$database->getPlatform() instanceof SqlitePlatform) {
            return;
        }

        $database = $database->context();
        $database->setAutoCastType([
            'birthday' => new class() extends AbstractType {
                public function convertToPHPValue($value, AbstractPlatform $platform): \DateTimeImmutable
                {
                    return parent::convertToPHPValue($value, $platform);
                }
            },
        ]);
        $database->overrideColumns([
            'misctype' => [
                'cdate' => [
                    'type' => 'birthday',
                ],
            ],
        ]);

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
        $this->assertStringContainsString('@var \\DateTimeImmutable', $annotation);
        $this->assertStringContainsString('$tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []', $annotation);

        $database->unstackAll();
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
            'birthday'          => new class() extends AbstractType {
                public function convertToPHPValue($value, AbstractPlatform $platform): \DateTimeImmutable
                {
                    return parent::convertToPHPValue($value, $platform);
                }
            },
        ]);
        $database->overrideColumns([
            'misctype'       => [
                'cdate' => [
                    'type' => 'birthday',
                ],
            ],
            'misctype_child' => [
                'cdate' => [
                    'type' => 'birthday',
                ],
            ],
        ]);

        $metafile = sys_get_temp_dir() . '/phpstormmeta';
        $phpstorm_meta = $database->echoPhpStormMeta(null, $metafile);
        $this->assertFileExists($metafile);
        $this->assertStringContainsString('namespace PHPSTORM_META', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\dbml\\Entity\\Entityable', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Article', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Comment', $phpstorm_meta);
        $this->assertStringContainsString('=> \DateTimeImmutable::class', $phpstorm_meta);
        $this->assertStringContainsString('=> \ArrayObject::class', $phpstorm_meta);

        $phpstorm_meta = $database->echoPhpStormMeta('EntityNamespace', null);
        $this->assertStringContainsString('namespace PHPSTORM_META', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Article', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Comment', $phpstorm_meta);
        $this->assertStringContainsString('new \\ryunosuke\\Test\\Entity\\Comment', $phpstorm_meta);
        $this->assertStringContainsString('new \\EntityNamespace\\test', $phpstorm_meta);
        $this->assertStringContainsString('=> \DateTimeImmutable::class', $phpstorm_meta);

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
    function test_executeSelect_and_Affect($database)
    {
        $database->insert('noauto', ['id' => false, 'name' => fn() => 'hoge']);
        $database->insert('noauto', ['id' => true, 'name' => fn() => 'fuga']);

        $this->assertCount(1, $database->executeSelect('SELECT * FROM test WHERE id = :id', ['id' => fn() => true])->fetchAllAssociative());
        $this->assertCount(0, $database->executeSelect('SELECT * FROM test WHERE id = :id', ['id' => fn() => false])->fetchAllAssociative());

        // PDO は設定によって違う（しかもドライバでバラバラ）し pgsql は少し特殊っぽくて、統一できない or コケやすいので動的に決める
        $sample = $database->executeSelect('SELECT ? as cint, ? cfloat', [1, 3.14])->fetchAssociative();
        $strval = function ($val) use ($sample) {
            if (is_int($val) && is_int($sample['cint'])) {
                return $val;
            }
            if (is_float($val) && is_float($sample['cfloat'])) {
                return $val;
            }
            return (string) $val;
        };

        $expected = [
            "cnull"   => null,
            "cfalse"  => $strval(0),
            "ctrue"   => $strval(1),
            "cint"    => $strval(123),
            "cfloat"  => $strval(3.14),
            "cstring" => "string",
        ];
        $query = <<<SQL
            SELECT
              ? as cnull,
              ? as cfalse,
              ? as ctrue,
              ? as cint,
              ? as cfloat,
              ? as cstring
        SQL;
        $params = [
            null,
            false,
            true,
            123,
            3.14,
            "string",
        ];

        $this->assertSame($expected, $database->executeSelect($query, $params)->fetchAssociative());
        $this->assertSame($expected, (new Statement($query, $params, $database))->executeSelect()->fetchAssociative());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeSelect_cache($database)
    {
        $query = 'select id, name from test where id = ?';
        $row1 = $database->executeSelect($query, [1], 10)->fetchAllAssociative();
        $row2 = $database->executeSelect($query, [2])->fetchAllAssociative();

        $database->update('test', ['name' => 'Z1'], ['id' => 1]);
        $database->update('test', ['name' => 'Z2'], ['id' => 2]);

        $this->assertEquals($row1, $database->executeSelect($query, [1])->fetchAllAssociative());
        $this->assertEquals([1 => ['name' => 'a']], $database->executeSelect($query, [1])->fetchAllAssociativeIndexed());

        $this->assertNotEquals($row2, $database->executeSelect($query, [2])->fetchAllAssociative());
        $this->assertEquals([2 => ['name' => 'Z2']], $database->executeSelect($query, [2])->fetchAllAssociativeIndexed());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeSelectAsync($database)
    {
        $this->trapThrowable('is not supported');

        // 「対応していない」というテストとカバレッジのために実行自体は行わせる
        try {
            $sleep1 = $database->queryInto($database->getCompatiblePlatform()->getSleepExpression(1));
        }
        catch (\Throwable) {
            $sleep1 = "sleep(1)";
        }

        $time = microtime(true);
        $result = $database->executeSelectAsync("SELECT id, $sleep1 as sleep, NULL as sleep FROM test WHERE id IN(?, ?)", [1, 2]);
        sleep(2);
        $actual = $result();

        // 非同期なので SLEEP(1) * 2 + sleep(2) で4秒…ではなく2秒以内に終わる
        $this->assertLessThan(2.5, microtime(true) - $time);

        $this->assertSame([
            ["id" => 1, "sleep" => null],
            ["id" => 2, "sleep" => null],
        ], $actual);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeAffect_retry($database)
    {
        $database->delete('test', ['id >= ?' => 3]);
        $time = microtime(true);

        $database = $database->context(['defaultRetry' => 5]);
        $id = 1;
        $affected = $database->insert("test", [
            'id'   => function () use (&$id) { return $id++; },
            'name' => 'x',
        ]);

        // リトライで成功する
        $this->assertEquals(1, $affected);
        // レコードもできている
        $this->assertEquals('x', $database->selectValue('test.name', ['id' => 3]));
        // 主キーの重複は短時間ウェイトにしてある
        $this->assertLessThanOrEqual(0.5, microtime(true) - $time);

        // リトライ回数を超えると通常通り例外を投げる
        $database = $database->context(['defaultRetry' => 2]);
        $id = 1;
        $this->assertException(UniqueConstraintViolationException::class, L($database)->insert("test", [
            'id'   => function () use (&$id) { return $id++; },
            'name' => 'x',
        ]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeAffectAsync($database)
    {
        $this->trapThrowable('is not supported');

        // 「対応していない」というテストとカバレッジのために実行自体は行わせる
        try {
            $sleep1 = $database->queryInto($database->getCompatiblePlatform()->getSleepExpression(1));
        }
        catch (\Throwable) {
            $sleep1 = "sleep(1)";
        }

        $time = microtime(true);
        $result = $database->executeAffectAsync("INSERT INTO test (data) SELECT $sleep1 UNION ALL SELECT $sleep1");
        sleep(2);
        $actual = $result();

        // 非同期なので SLEEP(1) * 2 + sleep(2) で4秒…ではなく2秒以内に終わる
        $this->assertLessThan(2.5, microtime(true) - $time);

        $this->assertEquals(2, $actual);
        $this->assertEquals(2, $database->getAffectedRows());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeAsync($database)
    {
        $this->trapThrowable('is not supported');

        // 「対応していない」というテストとカバレッジのために実行自体は行わせる
        try {
            $sleep1 = $database->queryInto($database->getCompatiblePlatform()->getSleepExpression(1));
        }
        catch (\Throwable) {
            $sleep1 = "sleep(1)";
        }

        $time = microtime(true);
        $result = $database->executeAsync([
            "SELECT id, $sleep1 as sleep, NULL as sleep FROM test WHERE id IN(?, ?)" => [1, 2],
            "INSERT INTO test (data) SELECT $sleep1 UNION ALL SELECT $sleep1"        => [],
            "SELECT id, $sleep1 as sleep, NULL as sleep FROM test WHERE id IN(3, ?)" => [4],
        ]);
        try {
            declare(ticks=1) {
                for ($i = 0; $i < 50; $i++) {
                    usleep(1000 * 100);
                }
            }
        }
        finally {
            $actual = $result();
        }

        // 非同期なので SLEEP(1) * 2 * 3 + usleep(100,000) * 50 で11秒…ではなく6秒以内に終わる
        $this->assertLessThan(6.5, microtime(true) - $time);

        $this->assertSame([
            [
                ["id" => 1, "sleep" => null],
                ["id" => 2, "sleep" => null],
            ],
            2,
            [
                ["id" => 3, "sleep" => null],
                ["id" => 4, "sleep" => null],
            ],
        ], $actual);
        $this->assertEquals(2, $database->getAffectedRows());

        // ネストしたパラメータはその数だけ実行する。さらにオフセット指定でその段階まで実行する
        $result = $database->executeAsync([
            "SELECT id, $sleep1 as sleep, NULL as sleep FROM test WHERE id IN(?, ?)" => [[1, 2], [3, 4], [5, 6]],
        ]);

        $time = microtime(true);
        $this->assertSame([
            ["id" => 1, "sleep" => null],
            ["id" => 2, "sleep" => null],
        ], $result(0));
        $this->assertGreaterThanOrEqual(2.0, microtime(true) - $time);

        $time = microtime(true);
        $this->assertSame([
            ["id" => 3, "sleep" => null],
            ["id" => 4, "sleep" => null],
        ], $result(1));
        $this->assertGreaterThanOrEqual(2.0, microtime(true) - $time);

        $time = microtime(true);
        $this->assertSame([
            ["id" => 5, "sleep" => null],
            ["id" => 6, "sleep" => null],
        ], $result(2));
        $this->assertGreaterThanOrEqual(2.0, microtime(true) - $time);

        unset($result);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_executeAsync_misc($database)
    {
        $database->setOption('dryrun', 1);
        $this->assertException('is not supported', L($database)->executeAsync(['SELECT 1' => []]));
        $database->setOption('dryrun', 0);

        $database->setOption('preparing', 1);
        $this->assertException('is not supported', L($database)->executeAsync(['SELECT 1' => []]));
        $database->setOption('preparing', 0);
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
                "DELETE FROM foreign_p WHERE NOT (foreign_p.id IN ('11', '12', '13'))",
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('12', 'fuga') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('13', 'piyo') ON",
            ], $result);
            $result = $database->migrate('foreign_p', 'change', $records, $opt + ['bulk' => true]);
            $this->assertCount(2, $result);
            $this->assertArrayStartsWith([
                "DELETE FROM foreign_p WHERE NOT (foreign_p.id IN ('11', '12', '13'))",
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
            ], $result);

            // SAVE
            $result = $database->migrate('foreign_p', 'save', $records, $opt + ['bulk' => false]);
            $this->assertCount(11, $result);
            $this->assertArrayStartsWith([
                "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge') ON",
                "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('11')) AND (NOT ((foreign_c1.seq = '1' AND foreign_c1.id = '11')))",
                "INSERT INTO foreign_c1 (seq, name, id) VALUES ('1', 'c1', '11') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('12', 'fuga') ON",
                "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('12')) AND (NOT ((foreign_c2.seq = '1' AND foreign_c2.cid = '12')))",
                "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('1', 'c2', '12') ON",
                "INSERT INTO foreign_p (id, name) VALUES ('13', 'piyo') ON",
                "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('13')) AND (NOT ((foreign_c1.seq = '2' AND foreign_c1.id = '13')))",
                "INSERT INTO foreign_c1 (seq, name, id) VALUES ('2', 'cc1', '13') ON",
                "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('13')) AND (NOT ((foreign_c2.seq = '2' AND foreign_c2.cid = '13')))",
                "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('2', 'cc2', '13') ON",
            ], $result);
            $result = $database->migrate('foreign_p', 'save', $records, $opt + ['bulk' => true]);
            $this->assertCount(5, $result);
            if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
                $this->assertArrayStartsWith([
                    "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
                    "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('11','13')) AND (NOT ((foreign_c1.seq, foreign_c1.id) IN (('1', '11'), ('2', '13'))))",
                    "INSERT INTO foreign_c1 (seq, name, id) VALUES ('1', 'c1', '11'), ('2', 'cc1', '13') ON",
                    "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('12','13')) AND (NOT ((foreign_c2.seq, foreign_c2.cid) IN (('1', '12'), ('2', '13'))))",
                    "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('1', 'c2', '12'), ('2', 'cc2', '13') ON",
                ], $result);
            }
            else {
                $this->assertArrayStartsWith([
                    "INSERT INTO foreign_p (id, name) VALUES ('11', 'hoge'), ('12', 'fuga'), ('13', 'piyo') ON",
                    "DELETE FROM foreign_c1 WHERE (foreign_c1.id IN ('11','13')) AND (NOT ((foreign_c1.seq = '1' AND foreign_c1.id = '11') OR (foreign_c1.seq = '2' AND foreign_c1.id = '13')))",
                    "INSERT INTO foreign_c1 (seq, name, id) VALUES ('1', 'c1', '11'), ('2', 'cc1', '13') ON",
                    "DELETE FROM foreign_c2 WHERE (foreign_c2.cid IN ('12','13')) AND (NOT ((foreign_c2.seq = '1' AND foreign_c2.cid = '12') OR (foreign_c2.seq = '2' AND foreign_c2.cid = '13')))",
                    "INSERT INTO foreign_c2 (seq, name, cid) VALUES ('1', 'c2', '12'), ('2', 'cc2', '13') ON",
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
                'delete_at'     => null,
                'g_parent'      => [
                    1 => [
                        'parent_id'   => '1',
                        'ancestor_id' => '1',
                        'parent_name' => 'AA',
                        'delete_at'   => null,
                        'g_child'     => [
                            1 => [
                                'child_id'   => '1',
                                'parent_id'  => '1',
                                'child_name' => 'AAA',
                                'delete_at'  => null,
                            ],
                            2 => [
                                'child_id'   => '2',
                                'parent_id'  => '1',
                                'child_name' => 'AAB',
                                'delete_at'  => null,
                            ],
                        ],
                    ],
                    2 => [
                        'parent_id'   => '2',
                        'ancestor_id' => '1',
                        'parent_name' => 'AB',
                        'delete_at'   => null,
                        'g_child'     => [
                            3 => [
                                'child_id'   => '3',
                                'parent_id'  => '2',
                                'child_name' => 'ABA',
                                'delete_at'  => null,
                            ],
                            4 => [
                                'child_id'   => '4',
                                'parent_id'  => '2',
                                'child_name' => 'ABB',
                                'delete_at'  => null,
                            ],
                        ],
                    ],
                ],
            ],
            2 => [
                'ancestor_id'   => '2',
                'ancestor_name' => 'B',
                'delete_at'     => null,
                'g_parent'      => [
                    3 => [
                        'parent_id'   => '3',
                        'ancestor_id' => '2',
                        'parent_name' => 'BA',
                        'delete_at'   => null,
                        'g_child'     => [
                            5 => [
                                'child_id'   => '5',
                                'parent_id'  => '3',
                                'child_name' => 'BAA',
                                'delete_at'  => null,
                            ],
                            6 => [
                                'child_id'   => '6',
                                'parent_id'  => '3',
                                'child_name' => 'BAB',
                                'delete_at'  => null,
                            ],
                        ],
                    ],
                    4 => [
                        'parent_id'   => '4',
                        'ancestor_id' => '2',
                        'parent_name' => 'BB',
                        'delete_at'   => null,
                        'g_child'     => [
                            7 => [
                                'child_id'   => '7',
                                'parent_id'  => '4',
                                'child_name' => 'BBA',
                                'delete_at'  => null,
                            ],
                            8 => [
                                'child_id'   => '8',
                                'parent_id'  => '4',
                                'child_name' => 'BBB',
                                'delete_at'  => null,
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
                'DELETE FROM g_parent',
                'INSERT INTO g_parent',
                'DELETE FROM g_child',
                'INSERT INTO g_child',
            ], $sqls);
        }
        else {
            $this->assertArrayStartsWith([
                'INSERT INTO g_ancestor',
                'INSERT INTO g_ancestor',
                'DELETE FROM g_parent',
                'INSERT INTO g_parent',
                'INSERT INTO g_parent',
                'INSERT INTO g_parent',
                'INSERT INTO g_parent',
                'DELETE FROM g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
                'INSERT INTO g_child',
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
            'DELETE FROM g_parent',
            'INSERT INTO g_parent',
            'INSERT INTO g_parent',
            'DELETE FROM g_child',
            'INSERT INTO g_child',
            'INSERT INTO g_child',
            'INSERT INTO g_child',
            'INSERT INTO g_child',
            'DELETE FROM g_grand1',
            'INSERT INTO g_grand1',
            'INSERT INTO g_grand1',
            'INSERT INTO g_grand1',
            'INSERT INTO g_grand1',
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

        // 空のテスト
        file_put_contents($csvfile, '');
        $this->assertEquals(0, $database->loadCsv('nullable', $csvfile, ['chunk' => 0]));
        $this->assertEquals(0, $database->loadCsv('nullable', $csvfile, ['chunk' => 1]));

        // skip と chunk
        file_put_contents($csvfile_head, <<<CSV
id,name,cint,cfloat,cdecimal
1,name1,1,1.1,1.11
2,name2,2,2.2,2.22
3,name3,3,3.3,3.33
CSV
        );
        $database->delete('nullable');
        $this->assertEquals(3, $database->context(['defaultChunk' => 2])->loadCsv('nullable', $csvfile_head, [
            'skip' => 1,
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
        $this->assertEquals([
            "INSERT INTO nullable (id, name, cdecimal, cfloat) VALUES ('1', 'direct', NULL, '1.23')",
        ], $database->dryrun()->loadCsv([
            'nullable' => [
                'id',
                'name'     => 'direct', // 範囲内直指定
                'cdecimal' => null,
                'cfloat'   => 1.23,     // 範囲外直指定
            ],
        ], $csvfile));

        // dryrun + chunk
        file_put_contents($csvfile_head, <<<CSV
1,name1,1,1.1,1.11
2,name2,2,2.2,2.22
3,name3,3,3.3,3.33
CSV
        );
        $this->assertEquals([
            "INSERT INTO nullable (id, name, cint, cfloat, cdecimal) VALUES ('1', 'name1', '1', '1.1', '1.11'), ('2', 'name2', '2', '2.2', '2.22')",
            "INSERT INTO nullable (id, name, cint, cfloat, cdecimal) VALUES ('3', 'name3', '3', '3.3', '3.33')",
        ], $database->context(['dryrun' => true, 'defaultChunk' => 2])->loadCsv('nullable', $csvfile));

        // カバレッジのために SQL 検証はしておく（実際のテストはすぐ↓）
        if (!$database->getPlatform() instanceof MySQLPlatform) {
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
                'escape' => '$',
            ]);
            $this->assertStringIgnoreBreak("
LOAD DATA LOCAL INFILE 'hoge.csv'
INTO TABLE nullable
CHARACTER SET 'utf8'
FIELDS
TERMINATED BY ','
ENCLOSED BY '\"'
ESCAPED BY '$'
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
        $this->trapThrowable(DriverException::class);

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

        // SelectBuilder でも同じ
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
        // 空のテスト
        $this->assertEquals(0, $database->insertArray('test', []));

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

        // Entity
        $affected = $database->insertArray('test', [
            (new Entity())->assign(['name' => 'E1']),
            (new Entity())->assign(['name' => 'E2']),
        ]);
        // 2件追加したら 2 が返るはず
        $this->assertEquals(2, $affected);
        // ケツから2件取れば突っ込んだデータのはず(ただし逆順)
        $this->assertEquals(['E2', 'E1'], $database->fetchLists($namequery->limit($affected)));

        // ジェネレータ
        $affected = $database->insertArray('test', (function () {
            foreach (['a', 'b', 'c'] as $v) {
                yield ['name' => $v];
            }
        })());
        // 3件追加したら 3 が返るはず
        $this->assertEquals(3, $affected);
        // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
        $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));


        if ($database->getCompatiblePlatform()->supportsIdentityNullable()) {
            $data = [
                ['id' => 99, 'name' => '99'],
                ['id' => null, 'name' => 'null'],
            ];

            $affected = $database->insertArray('test', $data);
            $this->assertEquals(2, $affected);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertArrayOrThrow($database)
    {
        if ($database->getPlatform() instanceof SqlitePlatform || $database->getPlatform() instanceof MySQLPlatform) {
            $manager = $this->scopeManager(function () use ($database) {
                $cplatform = $database->getCompatiblePlatform();
                $cache = $this->forcedRead($database, 'cache');
                $cache['compatiblePlatform'] = new class($cplatform->getWrappedPlatform()) extends CompatiblePlatform {
                    public function supportsIdentityNullable(): bool { return true; }
                };
                $this->forcedWrite($database, 'cache', $cache);
                return function () use ($database, $cache, $cplatform) {
                    $cache['compatiblePlatform'] = $cplatform;
                    $this->forcedWrite($database, 'cache', $cache);
                };
            });

            $database->delete('test', ['id' => [1, 2, 7, 8, 10]]);

            // 全指定
            $this->assertEquals([
                ["id" => 1],
                ["id" => 2],
            ], $database->insertArrayOrThrow('test', [
                ['id' => 1, 'name' => '1'],
                ['id' => 2, 'name' => '2'],
            ]));

            // 混在
            $this->assertEquals([
                ["id" => 7],
                ["id" => 8],
                ["id" => 12],
                ["id" => 13],
                ["id" => 11],
            ], $database->insertArrayOrThrow('test', [
                ['id' => 7, 'name' => '7'],
                ['id' => 8, 'name' => '8'],
                ['id' => null, 'name' => '11'],
                ['id' => 12, 'name' => '12'],
                ['id' => null, 'name' => '13'],
            ]));

            // 全未指定（兼準備が大変なのでゲートウェイ側もこっちでテスト）
            $database->resetAutoIncrement('test', 14);
            $this->assertEquals([
                ["id" => 15],
                ["id" => 14],
            ], $database->test->insertArrayOrThrow([
                ['id' => null, 'name' => '14'],
                ['id' => null, 'name' => '15'],
            ]));

            $this->assertException('affected row is nothing', L($database)->insertArrayOrThrow('test', []));
            $this->assertException('only autoincrement table', L($database)->insertArrayOrThrow('noauto', []));

            unset($manager);
        }
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
            $database = $database->context(['defaultChunk' => 1]);
            $affected = $database->insertArray('test', [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);
            // 3件追加したら 3 が返るはず
            $this->assertEquals(3, $affected);
            // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));

            // チャンク(2)
            $database = $database->context(['defaultChunk' => 2]);
            $affected = $database->insertArray('test', [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);
            // 3件追加したら 3 が返るはず
            $this->assertEquals(3, $affected);
            // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));

            // チャンク(3)
            $database = $database->context(['defaultChunk' => 3]);
            $affected = $database->insertArray('test', [
                ['name' => 'a'],
                ['name' => 'b'],
                ['name' => 'c'],
            ]);
            // 3件追加したら 3 が返るはず
            $this->assertEquals(3, $affected);
            // ケツから3件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['c', 'b', 'a'], $database->fetchLists($namequery->limit($affected)));

            // チャンク(params:3)
            $database = $database->context(['defaultChunk' => 'params:5']);
            $affected = $database->insertArray('test', [
                ['name' => 'c', 'data' => 'C'],
                ['name' => 'h', 'data' => 'H'],
                ['name' => 'u', 'data' => 'U'],
                ['name' => 'n', 'data' => 'N'],
                ['name' => 'k', 'data' => 'K'],
            ]);
            // 5件追加したら 5 が返るはず
            $this->assertEquals(5, $affected);
            // ケツから5件取れば突っ込んだデータのはず(ただし逆順)
            $this->assertEquals(['k', 'n', 'u', 'h', 'c'], $database->fetchLists($namequery->limit($affected)));
        });
        $this->assertEquals([
            "INSERT INTO test (name) VALUES ('a')",
            "INSERT INTO test (name) VALUES ('b')",
            "INSERT INTO test (name) VALUES ('c')",
            "INSERT INTO test (name) VALUES ('a'), ('b')",
            "INSERT INTO test (name) VALUES ('c')",
            "INSERT INTO test (name) VALUES ('a'), ('b'), ('c')",
            "INSERT INTO test (name, data) VALUES ('c', 'C'), ('h', 'H')",
            "INSERT INTO test (name, data) VALUES ('u', 'U'), ('n', 'N')",
            "INSERT INTO test (name, data) VALUES ('k', 'K')",
        ], array_values(preg_grep('#^INSERT#', $logs)));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertArray_misc($database)
    {
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
((SELECT UPPER(name1) FROM test1 WHERE id = '4'))", $affected[0]);

        $database = $database->context(['defaultChunk' => 3]);
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
        // 空のテスト
        $this->assertEquals(0, $database->updateArray('test', [], []));

        $data = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => new Expression('UPPER(\'b\')')],
            ['id' => 3, 'name' => new Expression('UPPER(?)', 'c')],
            ['id' => 4, 'name' => $database->select('test1.UPPER(name1)', ['id' => 4])],
            ['id' => 5, 'name' => 'nothing'],
            ['id' => 6, 'name' => 'f'],
        ];

        $affected = $database->updateArray('test', $data, ['id <> ?' => 5]);

        // 6件与えているが、変更されるのは4件のはず(pdo-mysql の場合。他DBMSは5件)
        $expected = $database->getCompatibleConnection()->getName() === 'pdo-mysql' ? 4 : 5;
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

            $database = $database->context(['defaultChunk' => 2]);
            $affected = $database->updateArray('test', $data, ['id <> ?' => 5]);

            // 6件与えているが、変更されるのは4件のはず(pdo-mysql の場合。他DBMSは5件)
            $expected = $database->getCompatibleConnection()->getName() === 'pdo-mysql' ? 4 : 5;
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

        // 6件与えているが、変更されるのは4件のはず(pdo-mysql の場合。他DBMSは5件)
        $expected = $database->getCompatibleConnection()->getName() === 'pdo-mysql' ? 4 : 5;
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

        $affected = $database->updateArray('test', (function () {
            foreach (['X', 'Y', 'Z'] as $n => $v) {
                yield ['id' => $n + 1, 'name' => $v];
            }
        })());

        $this->assertEquals(3, $affected);
        $this->assertEquals(['X', 'Y', 'Z'], $database->selectLists('test.name', ['id' => [1, 2, 3]]));

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

        // 空のテスト
        $this->assertEquals(0, $database->modifyArray('test', [], [], 'PRIMARY'));

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
        if ($database->getCompatiblePlatform()->getName() === 'mysql') {
            $expected = 10;
        }
        else {
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
            ['id' => 998, 'name' => 'ZZZ'],
        ], [
            'name' => 'UUU',
        ]);
        $this->assertEquals([
            'UUU',
            'ZZZ',
        ], $database->selectLists('test.name', [
            'id' => [1, 998],
        ]));

        $database->modifyArray('test', [
            ['id' => 1, 'name' => 'AAA'],
            ['id' => 999, 'name' => 'ZZZ'],
        ], [
            '*'    => null,
            'data' => 'common data',
        ]);
        $this->assertEquals([
            ['name' => 'AAA', 'data' => 'common data'],
            ['name' => 'ZZZ', 'data' => ''],
        ], $database->selectArray('test.name,data', [
            'id' => [1, 999],
        ]));

        if ($database->getCompatiblePlatform()->supportsIdentityNullable()) {
            $data = [
                ['id' => 9, 'name' => '9'],
                ['id' => null, 'name' => 'null'],
            ];

            $affected = $database->modifyArray('test', $data);

            // mysql は 1件変更・1件追加で計3affected, sqlite は単純に 2affected
            if ($database->getCompatiblePlatform()->getName() === 'mysql') {
                $expected = 3;
            }
            else {
                $expected = 2;
            }
            $this->assertEquals($expected, $affected);
        }
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
            $database = $database->context(['defaultChunk' => 1]);
            $affected = $database->modifyArray('test', [
                ['id' => 1, 'name' => 'U1'],
                ['id' => 2, 'name' => 'U2'],
                ['id' => 93, 'name' => 'A1'],
            ], []);
            // mysql は 2件変更・1件追加で計5affected, sqlite は単純に 3affected
            if ($database->getCompatiblePlatform()->getName() === 'mysql') {
                $expected = 5;
            }
            else {
                $expected = 3;
            }
            $this->assertEquals($expected, $affected);
            // 実際に取得して変わっている/いないを確認
            $this->assertEquals(['U1', 'U2', 'A1'], $database->selectLists('test.name', ['id' => [1, 2, 93]]));

            // チャンク(2件updateData)
            $database = $database->context(['defaultChunk' => 2]);
            $affected = $database->modifyArray('test', [
                ['id' => 3, 'name' => 'U1'],
                ['id' => 4, 'name' => 'U2'],
                ['id' => 95, 'name' => 'A1'],
            ], ['name' => 'U']);
            // mysql は 2件変更・1件追加で計5affected, sqlite は単純に 3affected
            if ($database->getCompatiblePlatform()->getName() === 'mysql') {
                $expected = 5;
            }
            else {
                $expected = 3;
            }
            $this->assertEquals($expected, $affected);
            // 実際に取得して変わっている/いないを確認
            $this->assertEquals(['U', 'U', 'A1'], $database->selectLists('test.name', ['id' => [3, 4, 95]]));

            // チャンク(3)
            $database = $database->context(['defaultChunk' => 3]);
            $affected = $database->modifyArray('test', [
                ['id' => 3, 'name' => 'U'],
                ['id' => 4, 'name' => 'U1'],
                ['id' => 5, 'name' => 'U2'],
                ['id' => 96, 'name' => 'A1'],
            ], []);
            // mysql は 2件変更・1件追加で計5affected, sqlite は単純に 4affected
            if ($database->getCompatiblePlatform()->getName() === 'mysql') {
                $expected = 5;
            }
            else {
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
{$merge(['id'])} id = {$refer('id')}, name = {$refer('name')}", $affected[0]);

        $affected = $database->dryrun()->modifyArray('test', $data, ['name' => 'hoge']);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'A'),
('2', UPPER('b')),
('3', UPPER('c')),
('4', (SELECT UPPER(name1) FROM test1 WHERE id = '4')),
('990', 'nothing'),
('991', 'zzz')
{$merge(['id'])} name = 'hoge'", $affected[0]);

        $affected = $database->dryrun()->modifyArray('test', $data, ['data' => 'hoge', '*' => fn($c, $d) => $d[$c]]);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'A'),
('2', UPPER('b')),
('3', UPPER('c')),
('4', (SELECT UPPER(name1) FROM test1 WHERE id = '4')),
('990', 'nothing'),
('991', 'zzz')
{$merge(['id'])} data = 'hoge', name = 'A'", $affected[0]);

        $database = $database->context(['defaultChunk' => 4]);
        $affected = $database->dryrun()->modifyArray('test', $data, ['name' => 'hoge']);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'A'),
('2', UPPER('b')),
('3', UPPER('c')),
('4', (SELECT UPPER(name1) FROM test1 WHERE id = '4'))
{$merge(['id'])} name = 'hoge'", $affected[0]);

        $database = $database->context(['defaultChunk' => 4]);
        $affected = $database->dryrun()->modifyArray('test', $data, ['name' => 'hoge']);
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('990', 'nothing'),
('991', 'zzz')
{$merge(['id'])} name = 'hoge'", $affected[1]);

        $database = $database->unstackAll();
        $affected = $database->dryrun()->modifyArray('test', (function () {
            foreach (['X', 'Y', 'Z'] as $n => $v) {
                yield ['id' => $n + 1, 'name' => $v];
            }
        })());
        $this->assertStringIgnoreBreak("
INSERT INTO test (id, name) VALUES
('1', 'X'),
('2', 'Y'),
('3', 'Z')
{$merge(['id'])} id = {$refer('id')}, name = {$refer('name')}", $affected[0]);
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

        // テーブル記法
        $database->insert('test.name', 'yy');
        $this->assertEquals('yy', $database->selectValue('test.name', [], ['id' => 'desc'], 1));

        // into
        $database->insert('test', [
            'name' => new Expression('UPPER(?)', 'lower'),
        ]);
        $this->assertEquals('LOWER', $database->selectValue('test.name', [], ['id' => 'desc'], 1));

        // for mysql
        if ($database->getCompatiblePlatform()->supportsInsertSet()) {
            $sql = $database->context(['insertSet' => true])->dryrun()->insert('test', ['name' => 'zz']);
            $this->assertEquals(["INSERT INTO test SET name = 'zz'"], $sql);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insertAndPrimary($database)
    {
        $result = $database->insertAndPrimary('test[id:1]', [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals(['id' => 1], $result);

        $result = $database->insertAndPrimary('test[id:100]', [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $sql = $database->dryrun()->insert('test[id:1]', [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertStringContainsString('INSERT INTO test (id, name) SELECT', $sql[0]);
        $this->assertStringContainsString('WHERE (NOT EXISTS (SELECT * FROM test WHERE id =', $sql[0]);
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

        // テーブル記法
        $affected = $database->update('test[id:2].name', 'td', []);
        $this->assertEquals(1, $affected);
        $affected = $database->update($database->descriptor('test[id: [?, ?]]', [3, 4]), [
            'name' => 'td',
        ], ['id IN (4, 5)']);
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

        // 空
        $realaffected = $database->update('multiprimary', [
            'name' => 'XXX',
        ], [
            'mainid' => 1,
        ]);
        $affected = $database->update('multiprimary', [
            // empty
        ], [
            'mainid' => 1,
        ]);
        $this->assertEquals($database->getCompatibleConnection()->getName() === 'pdo-mysql' ? 0 : $realaffected, $affected);
        $this->assertEquals($database->getPlatform() instanceof MySQLPlatform ? 0 : $realaffected, $database->getAffectedRows());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_update_descriptor($database)
    {
        $this->assertEquals(1, $database->update('test(2)', ['name' => 'XXX']));
        $this->assertEquals('XXX', $database->selectValue('test(2).name'));

        $database->test->addScope('affect', [], ['id <> ?' => 1]);
        $this->assertEquals(1, $database->update('test(1, 2, 3)@affect', ['name' => 'YYY'], ['id <> ?' => 2]));
        $this->assertEquals('a', $database->selectValue('test(1).name'));
        $this->assertEquals('XXX', $database->selectValue('test(2).name'));
        $this->assertEquals('YYY', $database->selectValue('test(3).name'));
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

        // テーブル記法
        $affected = $database->delete('test[id:6]');
        $this->assertEquals(1, $affected);
        $affected = $database->delete($database->descriptor('test[id: [?, ?]]', [7, 8]), ['id IN (8, 9)']);
        $this->assertEquals(1, $affected);

        // 文字列指定
        $affected = $database->delete('test', '1=1');
        $this->assertEquals(3, $affected);

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
    function test_delete_descriptor($database)
    {
        $count = $database->count('test');

        $this->assertEquals(1, $database->delete('test(2)'));
        $this->assertEquals($count - 1, $database->count('test'));

        $database->test->addScope('affect', [], ['id <> ?' => 1]);
        $this->assertEquals(1, $database->delete('test(1, 2, 3)[id <> 2]@affect'));
        $this->assertEquals($count - 2, $database->count('test'));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_invalid($database)
    {
        $database->save('g_ancestor', [
            "ancestor_id"   => 1,
            'ancestor_name' => 'A1',
            'g_parent'      => [
                [
                    "parent_id"   => 1,
                    'parent_name' => 'A1P1',
                    'g_child'     => [
                        [
                            'child_name' => 'A1P1C1',
                        ],
                    ],
                    'g_grand1'    => [
                        [
                            'grand1_name' => 'A1P1G1',
                        ],
                    ],
                    'g_grand2'    => [
                        [
                            'grand2_name' => 'A1P1G2',
                        ],
                    ],
                ],
            ],
        ]);

        // g_grand1 が RESTRICT なので失敗する
        $this->assertException('Cannot delete or update', L($database)->invalid('g_ancestor', [], ['delete_at' => '2014-12-24 00:00:00']));

        // g_grand1 を無効化すれば・・・
        $this->assertEquals(1, $database->invalid('g_grand1', [], ['delete_at' => '2014-12-24 00:00:00']));

        // g_ancestor で一挙に無効化できる
        $this->assertGreaterThanOrEqual(1, $database->invalid('g_ancestor', [], ['delete_at' => '2014-12-24 00:00:00']));

        // g_parent/g_child などにも伝播している
        $this->assertEquals([
            1 => "2014-12-24 00:00:00",
        ], array_map(fn($v) => date('Y-m-d H:i:s', strtotime($v)), $database->selectPairs('g_parent.parent_id,delete_at')));
        $this->assertEquals([
            1 => "2014-12-24 00:00:00",
        ], array_map(fn($v) => date('Y-m-d H:i:s', strtotime($v)), $database->selectPairs('g_child.child_id,delete_at')));

        // sqlserver がわけのわからんコケ方をするのでさしあたり除外（外部キーの取得が実体と一致しない？）
        if (!$database->getPlatform() instanceof SQLServerPlatform) {
            // dryrun はクエリ配列を返す
            $sqls = $database->dryrun()->invalid('g_ancestor', ['ancestor_id' => 1], ['delete_at' => '2014-12-24 00:00:00']);
            if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
                $this->assertEquals([
                    "UPDATE g_child SET delete_at = '2014-12-24 00:00:00' WHERE (parent_id) IN (SELECT g_parent.parent_id FROM g_parent WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1'))",
                    "UPDATE g_grand1 SET delete_at = '2014-12-24 00:00:00' WHERE (parent_id) IN (SELECT g_parent.parent_id FROM g_parent WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1'))",
                    "UPDATE g_grand2 SET delete_at = '2014-12-24 00:00:00' WHERE (parent_id,ancestor_id) IN (SELECT g_parent.parent_id, g_parent.ancestor_id FROM g_parent WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1'))",
                    "UPDATE g_parent SET delete_at = '2014-12-24 00:00:00' WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1')",
                    "UPDATE g_ancestor SET delete_at = '2014-12-24 00:00:00' WHERE ancestor_id = '1'",
                ], $sqls);
            }
            else {
                $this->assertEquals([
                    "UPDATE g_child SET delete_at = '2014-12-24 00:00:00' WHERE (parent_id) IN (SELECT g_parent.parent_id FROM g_parent WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1'))",
                    "UPDATE g_grand1 SET delete_at = '2014-12-24 00:00:00' WHERE (parent_id) IN (SELECT g_parent.parent_id FROM g_parent WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1'))",
                    "UPDATE g_grand2 SET delete_at = '2014-12-24 00:00:00' WHERE (g_grand2.parent_id = '1' AND g_grand2.ancestor_id = '1')",
                    "UPDATE g_parent SET delete_at = '2014-12-24 00:00:00' WHERE (ancestor_id) IN (SELECT g_ancestor.ancestor_id FROM g_ancestor WHERE ancestor_id = '1')",
                    "UPDATE g_ancestor SET delete_at = '2014-12-24 00:00:00' WHERE ancestor_id = '1'",
                ], $sqls);
            }
        }

        $this->assertEquals(['id' => 1], $database->invalidOrThrow('test', ['id' => 1], ['name' => 'deleted']));
        $this->assertException('affected row is nothing', L($database)->invalidOrThrow('test', ['id' => -1], ['name' => 'deleted']));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_revise($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_p', ['id' => 4, 'name' => 'name4']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 21, 'name' => 'c2name1']);

        $affected = $database->revise('foreign_p', ['id' => $database->raw('id + 5')], [
            'id' => [1, 2, 3],
        ]);

        // 1, 2 は子供で使われていて 4 は指定していない。結果 3 しか更新されない
        $this->assertEquals(1, $affected);

        // 実際に取得してみて担保する
        $this->assertEquals([
            ['id' => 1, 'name' => 'name1'],
            ['id' => 2, 'name' => 'name2'],
            ['id' => 4, 'name' => 'name4'],
            ['id' => 3 + 5, 'name' => 'name3'],
        ], $database->selectArray('foreign_p'));

        // OrThrow
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $this->assertException('affected row is nothing', L($database)->reviseOrThrow('test', ['id' => -1], ['id' => -1]));
        }

        // 相互外部キー
        $this->assertEquals([
            "UPDATE foreign_d1 SET name = 'hoge' WHERE (id = '1') AND ((NOT EXISTS (SELECT * FROM foreign_d2 WHERE foreign_d2.id = foreign_d1.id)))",
        ], $database->dryrun()->revise('foreign_d1', ['name' => 'hoge'], ['id' => 1]));
        $this->assertEquals([
            "UPDATE foreign_d2 SET name = 'hoge' WHERE (id = '1') AND ((NOT EXISTS (SELECT * FROM foreign_d1 WHERE foreign_d1.d2_id = foreign_d2.id)))",
        ], $database->dryrun()->revise('foreign_d2', ['name' => 'hoge'], ['id' => 1]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_upgrade($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_p', ['id' => 4, 'name' => 'name4']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 21, 'name' => 'c2name1']);
        $database->insert('foreign_c2', ['cid' => 4, 'seq' => 41, 'name' => 'c4name1']);

        $database->begin();
        try {
            // 1, 2 は子供で使われているが強制更新される。 4 は指定していない。結果 4 が残る
            $affected = $database->upgrade('foreign_p', ['id' => 1 + 5], ['id' => 1]);
            $this->assertEquals(1, $affected);
            $affected = $database->upgrade('foreign_p', ['id' => 2 + 5], ['id' => 2]);
            $this->assertEquals(1, $affected);
            $affected = $database->upgrade('foreign_p', ['id' => 3 + 5], ['id' => 3]);
            $this->assertEquals(1, $affected);

            // 実際に取得してみて担保する
            $this->assertEquals([
                ['id' => 4 + 0, 'name' => 'name4'],
                ['id' => 1 + 5, 'name' => 'name1'],
                ['id' => 2 + 5, 'name' => 'name2'],
                ['id' => 3 + 5, 'name' => 'name3'],
            ], $database->selectArray('foreign_p'));
            $this->assertEquals([
                ['cid' => 4 + 0, 'seq' => 41, 'name' => 'c4name1'],
                ['cid' => 2 + 5, 'seq' => 21, 'name' => 'c2name1'],
            ], $database->selectArray('foreign_c2'));
        }
        finally {
            $database->rollback();
        }

        // OrThrow
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $this->assertException('affected row is nothing', L($database)->upgradeOrThrow('test', ['id' => -1], ['id' => -1]));
        }

        // 主キーを更新しない
        $this->assertEquals([
            "UPDATE foreign_p SET name = 'hoge1' WHERE id = '4'",
        ], $database->dryrun()->upgrade('foreign_p', ['name' => 'hoge1'], ['id' => 4]));

        // dryrun はクエリ配列を返す
        $this->assertEquals([
            "UPDATE foreign_c1 SET id = '9' WHERE (id) IN (SELECT foreign_p.id FROM foreign_p WHERE id = '4')",
            "UPDATE foreign_c2 SET cid = '9' WHERE (cid) IN (SELECT foreign_p.id FROM foreign_p WHERE id = '4')",
            "UPDATE foreign_p SET id = '9' WHERE id = '4'",
        ], $database->dryrun()->upgrade('foreign_p', ['id' => 4 + 5], ['id' => 4]));

        // not row constructor + 2column
        $sqls = $database->dryrun()->upgrade('multiprimary', ['mainid' => new Expression('mainid + 99'), 'subid' => 1], ['name' => ['a', 'b']]);
        if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertEquals([
                "UPDATE multifkey SET mainid = mainid + 99, subid = '1' WHERE (mainid,subid) IN (SELECT multiprimary.mainid, multiprimary.subid FROM multiprimary WHERE name IN ('a','b'))",
                "UPDATE multiprimary SET mainid = mainid + 99, subid = '1' WHERE name IN ('a','b')",
            ], $sqls);
        }
        else {
            $this->assertEquals([
                "UPDATE multifkey SET mainid = mainid + 99, subid = '1' WHERE (multifkey.mainid = '1' AND multifkey.subid = '1') OR (multifkey.mainid = '1' AND multifkey.subid = '2')",
                "UPDATE multiprimary SET mainid = mainid + 99, subid = '1' WHERE name IN ('a','b')",
            ], $sqls);
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

        // 相互外部キー
        $this->assertEquals([
            'DELETE FROM foreign_d1 WHERE (NOT EXISTS (SELECT * FROM foreign_d2 WHERE foreign_d2.id = foreign_d1.id))',
        ], $database->dryrun()->remove('foreign_d1'));
        $this->assertEquals([
            'DELETE FROM foreign_d2 WHERE (NOT EXISTS (SELECT * FROM foreign_d1 WHERE foreign_d1.d2_id = foreign_d2.id))',
        ], $database->dryrun()->remove('foreign_d2'));
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
        $this->assertEquals(3, $affected);

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

        // not row constructor + 2column
        $sqls = $database->dryrun()->destroy('multiprimary', ['name' => ['a', 'b']]);
        if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertEquals([
                "DELETE FROM multifkey WHERE (mainid,subid) IN (SELECT multiprimary.mainid, multiprimary.subid FROM multiprimary WHERE name IN ('a','b'))",
                "DELETE FROM multiprimary WHERE name IN ('a','b')",
            ], $sqls);
        }
        else {
            $this->assertEquals([
                "DELETE FROM multifkey WHERE (multifkey.mainid = '1' AND multifkey.subid = '1') OR (multifkey.mainid = '1' AND multifkey.subid = '2')",
                "DELETE FROM multiprimary WHERE name IN ('a','b')",
            ], $sqls);
        }
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

        // ↑のテーブル記法
        $database->begin();
        $this->assertEquals(11, $database->reduce('oprlog["log_date<\'2009-07-04\'"]<category,primary_id>-log_date#-5'));
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
            'data' => 'data',
        ];
        $row2 = [
            'name' => 'zz',
            '*'    => null,
        ];
        $database->upsert('test', $row1, $row2);

        // $row2 で「更新」されているはず
        $this->assertEquals(['id' => 2, 'name' => 'zz', 'data' => 'data'], $database->fetchTuple('select * from test where id = 2'));

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
    function test_upsertAndPrimary($database)
    {
        $result = $database->upsertAndPrimary('test[id:1]', [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals(['id' => 1], $result);

        $result = $database->upsertAndPrimary('test[id:100]', [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $result = $database->upsertAndPrimary('test[id:-1]', [
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

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            $database->modify('test', ['id' => $id, 'name' => 'repN2'], ['*' => null, 'data' => 'upD2']);
            $this->assertEquals(['name' => 'repN2', 'data' => 'upD2'], $database->selectTuple('test.name,data', ['id' => $id]));
        }

        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $merge = function ($columns) use ($database) { return $database->getCompatiblePlatform()->getMergeSyntax($columns); };
            $refer = function ($column) use ($database) { return $database->getCompatiblePlatform()->getReferenceSyntax($column); };

            $affected = $database->dryrun()->modify('test', ['id' => 1, 'name' => 'name1'], ['*' => null, 'data' => 'updateData']);
            $this->assertEquals([
                "INSERT INTO test (id, name) VALUES ('1', 'name1') {$merge(['id'])} name = {$refer('name')}, data = 'updateData'",
            ], $affected);

            $affected = $database->dryrun()->modify('test', ['id' => 1, 'name' => 'name1'], ['data' => 'updateData', '*' => null]);
            $this->assertEquals([
                "INSERT INTO test (id, name) VALUES ('1', 'name1') {$merge(['id'])} data = 'updateData', name = {$refer('name')}",
            ], $affected);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyOrThrow($database)
    {
        if (!$database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            return;
        }

        // 普通にやれば次の連番が返るはず
        $primary = $database->modifyOrThrow('test', ['name' => 'modify1']);
        $this->assertEquals(['id' => 11], $primary);

        // null も数値で返るはず
        $primary = $database->modifyOrThrow('test', ['id' => null, 'name' => 'modify2']);
        $this->assertEquals(['id' => 12], $primary);

        // Expression も数値で返るはず
        $primary = $database->modifyOrThrow('test', ['id' => new Expression('?', 13), 'name' => 'modify3_1']);
        $this->assertEquals(['id' => 13], $primary);
        $primary = $database->modifyOrThrow('test', ['id' => new Expression('?', 13), 'name' => 'modify3_2']);
        $this->assertEquals(['id' => 13], $primary);

        // SelectBuilder も数値で返るはず
        $primary = $database->modifyOrThrow('test', ['id' => $database->select(['test T' => 'id+100'], ['id' => 1]), 'name' => 'modify4_1']);
        $this->assertEquals(['id' => 101], $primary);
        $primary = $database->modifyOrThrow('test', ['id' => $database->select(['test T' => 'id'], ['id' => 1]), 'name' => 'modify4_2']);
        $this->assertEquals(['id' => 1], $primary);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_modifyAndPrimary($database)
    {
        $result = $database->modifyAndPrimary('test[id:1]', [
            'id'   => 1,
            'name' => 'a',
        ]);
        $this->assertEquals(['id' => 1], $result);

        $result = $database->modifyAndPrimary('test[id:100]', [
            'id'   => 100,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 100], $result);

        $result = $database->modifyAndPrimary('test[id:-1]', [
            'id'   => 10,
            'name' => 'zzz',
        ]);
        $this->assertEquals(['id' => 10], $result);

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            $sql = $database->dryrun()->modifyAndPrimary('test[id:-1]', [
                'id'   => 10,
                'name' => 'zzz',
            ]);
            $this->assertStringContainsString('INSERT INTO test (id, name) SELECT', $sql[0]);
            $this->assertStringContainsString('WHERE (NOT EXISTS (SELECT * FROM test WHERE id =', $sql[0]);
            $merge = $database->getCompatiblePlatform()->getMergeSyntax(['id']);
            $reference = $database->getCompatiblePlatform()->getReferenceSyntax('id');
            $this->assertStringContainsString("$merge id = $reference", $sql[0]);
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

        $database->setInsertSet(true);
        if ($database->getCompatiblePlatform()->supportsInsertSet()) {
            $sql = $database->dryrun()->modifyOrThrow('test', ['name' => 'zz']);
            $this->assertStringContainsString("INSERT INTO test SET name = 'zz' ", $sql[0]);
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
    function test_changeArray_uk($database)
    {
        if (!$database->getCompatiblePlatform()->supportsMerge()) {
            return;
        }

        $group2 = $database->selectArray('multiunique', ['groupkey' => 2]);

        $primaries = $database->changeArray('multiunique', [
            ['id' => 11, 'uc_s' => 'a1', 'uc_i' => 1, 'uc1' => 'X', 'uc2' => 1, 'groupkey' => 1],
            ['id' => 12, 'uc_s' => 'b1', 'uc_i' => 2, 'uc1' => 'Y', 'uc2' => 2, 'groupkey' => 1],
            ['id' => 13, 'uc_s' => 'c1', 'uc_i' => 3, 'uc1' => 'Z', 'uc2' => 3, 'groupkey' => 1],
        ], ['groupkey' => 1], 'uk3');
        // 与えた配列のとおりになっている
        $this->assertEquals([
            ["uc1" => "X", "uc2" => "1"],
            ["uc1" => "Y", "uc2" => "2"],
            ["uc1" => "Z", "uc2" => "3"],
        ], $database->selectArray('multiunique.uc1,uc2', ['groupkey' => 1]));
        // 一意キーを返している
        $this->assertEquals([
            ["uc1" => "X", "uc2" => "1"],
            ["uc1" => "Y", "uc2" => "2"],
            ["uc1" => "Z", "uc2" => "3"],
        ], $primaries);

        $primaries = $database->changeArray('multiunique', [
            ['uc_s' => 'a2', 'uc_i' => 4, 'uc1' => 'X', 'uc2' => 1, 'groupkey' => 1],
            ['uc_s' => 'b2', 'uc_i' => 5, 'uc1' => 'YY', 'uc2' => 2, 'groupkey' => 1],
        ], ['groupkey' => 1], 'uk3');
        // 与えた配列のとおりになっている
        $this->assertEquals([
            ["uc_s" => "a2", "uc_i" => 4, "uc1" => "X", "uc2" => 1],
            ["uc_s" => "b2", "uc_i" => 5, "uc1" => "YY", "uc2" => 2],
        ], $database->selectArray('multiunique.uc_s,uc_i,uc1,uc2', ['groupkey' => 1]));
        // 一意キーを返している
        $this->assertEquals([
            ["uc1" => "X", "uc2" => 1],
            ["uc1" => "YY", "uc2" => 2],
        ], $primaries);

        // 一連の流れで groupkey=2 に波及していないことを担保
        $this->assertEquals($group2, $database->selectArray('multiunique', ['groupkey' => 2]));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_changeArray_returning($database)
    {
        $updatedAffectedRows = $database->getCompatiblePlatform()->getName() === 'mysql' ? 0 : 2;

        $primaries = $database->changeArray('multiprimary', [
            ['mainid' => 1, 'subid' => 1, 'name' => 'a'],
            ['mainid' => 1, 'subid' => 2, 'name' => 'X'],
            ['mainid' => 1, 'subid' => 9, 'name' => 'Z'],
        ], ['mainid' => 1], 'PRIMARY', null);
        $this->assertEquals([
            [
                "mainid" => "1",
                "subid"  => "1",
                ""       => $updatedAffectedRows,
            ],
            [
                "mainid" => "1",
                "subid"  => "2",
                ""       => 2,
            ],
            [
                "mainid" => 1,
                "subid"  => 9,
                ""       => 1,
            ],
            [
                "mainid" => "1",
                "subid"  => "3",
                ""       => -1,
            ],
            [
                "mainid" => "1",
                "subid"  => "4",
                ""       => -1,
            ],
            [
                "mainid" => "1",
                "subid"  => "5",
                ""       => -1,
            ],
        ], $primaries);

        $primaries = $database->changeArray('test', [
            "first" => ['id' => 1, 'name' => 'a'],
            2       => ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'Z'],
            "last"  => ['id' => 93, 'name' => 'Z3'],
        ], [], 'PRIMARY', ['id', 'nameX' => 'name']);
        $this->assertEquals([
            "first" => [
                "id"    => "1",
                "nameX" => "a",
                ""      => $updatedAffectedRows,
            ],
            2       => [
                "id"    => "2",
                "nameX" => "b",
                ""      => $updatedAffectedRows,
            ],
            3       => [
                "id"    => "3",
                "nameX" => "c",
                ""      => 2,
            ],
            "last"  => [
                "id"    => "93",
                "nameX" => "Z3",
                ""      => 1,
            ],
            4       => [
                "id"    => "4",
                "nameX" => "d",
                ""      => -1,
            ],
            5       => [
                "id"    => "5",
                "nameX" => "e",
                ""      => -1,
            ],
            6       => [
                "id"    => "6",
                "nameX" => "f",
                ""      => -1,
            ],
            7       => [
                "id"    => "7",
                "nameX" => "g",
                ""      => -1,
            ],
            8       => [
                "id"    => "8",
                "nameX" => "h",
                ""      => -1,
            ],
            9       => [
                "id"    => "9",
                "nameX" => "i",
                ""      => -1,
            ],
            10      => [
                "id"    => "10",
                "nameX" => "j",
                ""      => -1,
            ],
        ], $primaries);

        if ($database->getCompatiblePlatform()->getName() === 'mysql') {
            $primaries = $database->changeArray('test', [
                "first" => ['id' => 1, 'name' => 'a'],
                ['id' => 2, 'name' => 'b'],
                ['id' => 3, 'name' => 'Z'],
                "last"  => ['id' => 100, 'name' => 'Z'],
            ], ['false'], 'PRIMARY', ['name']);
            $this->assertEquals([
                "first" => [
                    "name" => "a",
                    ""     => 0,
                ],
                [
                    "name" => "b",
                    ""     => 0,
                ],
                [
                    "name" => "Z",
                    ""     => 0,
                ],
                "last"  => [
                    "name" => "Z",
                    ""     => 1,
                ],
            ], $primaries);

            if ($database->getCompatiblePlatform()->supportsIgnore()) {
                $database = $database->context(['filterNullAtNotNullColumn' => false]); // not null に null を入れることでエラーを発生させる

                $primaries = $database->changeArrayIgnore('test', [
                    ['id' => 1, 'name' => null],
                ], ['id' => 1], 'PRIMARY', ['name']);
                $this->assertEquals([
                    [
                        "name" => "a",
                        ""     => 2,
                    ],
                ], $primaries);
            }
        }
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
                'DELETE FROM test',
                'INSERT INTO test',
                'INSERT INTO test',
            ], $changed[1]);
        }

        $changed = $database->dryrun()->changeArray('multiprimary', [
            ['mainid' => 1, 'subid' => 1, 'name' => 'X'],
            ['mainid' => 1, 'subid' => 2, 'name' => 'Y'],
            ['mainid' => 1, 'subid' => 3, 'name' => 'Z'],
        ], ['mainid' => 1], 'PRIMARY', null, ['bulk' => false]);

        $this->assertEquals([
            ['mainid' => 1, 'subid' => 1],
            ['mainid' => 1, 'subid' => 2],
            ['mainid' => 1, 'subid' => 3],
        ], $changed[0]);

        if ($database->getCompatiblePlatform()->supportsBulkMerge()) {
            $this->assertArrayStartsWith([
                'DELETE FROM multiprimary',
                'INSERT INTO multiprimary',
                'INSERT INTO multiprimary',
                'INSERT INTO multiprimary',
            ], $changed[1]);
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_affectArray($database)
    {
        if ($database->getCompatiblePlatform()->getName() === 'mysql') {
            $noaffectedUpdatedRows = 0;
        }
        else {
            $noaffectedUpdatedRows = 1;
        }

        // 画面からこのようなデータが来たと仮定
        $post = [
            0   => ['@method' => 'delete', 'id' => '1', 'name' => 'delete1'],
            1   => ['@method' => 'update', 'id' => '2', 'name' => 'update1'],
            2   => ['@method' => 'update', 'id' => '3', 'name' => 'c'],
            3   => ['@method' => 'invalid', 'id' => '4', 'name' => 'invalid'],
            -1  => ['@method' => 'insert', 'name' => 'insert1'],
            -2  => ['@method' => 'insert', 'name' => 'insert2'],
            999 => ['@method' => 'delete', 'id' => '999', 'name' => 'delete2'],
        ];

        $sqls = $database->dryrun()->affectArray('test', $post);
        $this->assertEquals([
            "DELETE FROM test WHERE id = '1'",
            "DELETE FROM test WHERE id = '999'",
            "UPDATE test SET name = 'update1' WHERE id = '2'",
            "UPDATE test SET name = 'c' WHERE id = '3'",
            "UPDATE test SET name = 'invalid' WHERE id = '4'",
            "INSERT INTO test (name) VALUES ('insert1')",
            "INSERT INTO test (name) VALUES ('insert2')",
        ], $sqls);

        $primaries = $database->affectArray('test', $post);
        $this->assertEquals([
            0   => ["id" => "1", "" => 1],
            999 => ["id" => "999", "" => 0],
            1   => ["id" => "2", "" => 1],
            2   => ["id" => "3", "" => $noaffectedUpdatedRows],
            3   => ["id" => "4", "" => 1],
            -1  => ["id" => "11", "" => 1],
            -2  => ["id" => "12", "" => 1],
        ], $primaries);

        $this->assertException('is invalid', L($database)->affectArray('test', [['@method' => 'unknown']]));
        $this->assertException('primary data mismatch', L($database)->affectArray('multiprimary', [['@method' => 'update', 'mainid' => 1]]));
        $this->assertException('primary data mismatch', L($database)->affectArray('multiprimary', [['@method' => 'delete', 'mainid' => 1]]));

        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $primaries = $database->affectArrayIgnore('test', [
                ['@method' => 'insert', 'id' => 5, 'name' => 'X'],
                ['@method' => 'update', 'id' => 6, 'name' => 'Y'],
            ]);
            $this->assertEquals([
                1 => ["id" => 6, "" => 1],
                0 => ["id" => 5, "" => 0],
            ], $primaries);

            $this->assertEquals('e', $database->selectValue('test(5).name'));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_affectArray_misc($database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_p', ['id' => 4, 'name' => 'name4']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 21, 'name' => 'c2name1']);
        $database->insert('foreign_c1', ['id' => 3, 'seq' => 31, 'name' => 'c3name1']);
        $database->insert('foreign_c2', ['cid' => 4, 'seq' => 41, 'name' => 'c4name1']);

        // 画面からこのようなデータが来たと仮定
        $post = [
            ['@method' => 'destroy', 'id' => '1', 'name' => 'destroy'],
            ['@method' => 'remove', 'id' => '2', 'name' => 'remove'],
            ['@method' => 'upgrade', 'id' => '3', 'name' => 'upgrade'],
            ['@method' => 'revise', 'id' => '4', 'name' => 'revise'],
            ['@method' => 'modify', 'id' => '5', 'name' => 'modify'],
        ];

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            $sqls = $database->dryrun()->affectArray('foreign_p', $post);
            $this->assertArrayStartsWith([
                "DELETE FROM foreign_c1 WHERE (id) IN (",
                "DELETE FROM foreign_c2 WHERE (cid) IN (",
                "DELETE FROM foreign_p WHERE id = '1'",
                "DELETE FROM foreign_p WHERE (id = '2') AND (",
                "UPDATE foreign_p SET name = 'upgrade' WHERE id = '3'",
                "UPDATE foreign_p SET name = 'revise' WHERE ",
                "INSERT INTO foreign_p (id, name) VALUES ('5', 'modify') ON ",
            ], $sqls);
        }

        $primaries = $database->affectArray('foreign_p', $post);
        $this->assertEquals([
            ["id" => "1", "" => 1],
            ["id" => "2", "" => 0],
            ["id" => "3", "" => 1],
            ["id" => "4", "" => 0],
            ["id" => "5", "" => 1],
        ], $primaries);
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

            $database->replace('test', ['id' => $id, 'name' => 'defN']);
            $this->assertEquals(['name' => 'defN', 'data' => 'repD'], $database->selectTuple('test.name,data', ['id' => $id]));

            $this->assertEquals(['id' => $id + 1], $database->replaceOrThrow('test', ['id' => $id + 1, 'name' => '', 'data' => '']));
            $this->assertEquals(['id' => $id + 1], $database->replaceAndPrimary('test', ['id' => $id + 1, 'name' => '', 'data' => '']));
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

        $this->assertStringContainsString('test', $database->dryrun()->truncate('test')[0]);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_eliminate($database)
    {
        $this->assertIsArray($database->dryrun()->eliminate('g_ancestor'));

        // PostgreSQLPlatform は truncate CASCADE がある故外部キーを無効化できない？
        // SQLServer は truncate の CASCADE も外部キー無効も対応していない
        if (!$database->getPlatform() instanceof PostgreSQLPlatform && !$database->getPlatform() instanceof SQLServerPlatform) {
            $database->import([
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
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            // truncate の affected rows はバラバラなので int で緩く
            $this->assertIsInt($database->eliminate('g_ancestor'));

            // すべて消えている
            $this->assertEquals(0, $database->count('g_ancestor'));
            $this->assertEquals(0, $database->count('g_parent'));
            $this->assertEquals(0, $database->count('g_child'));

            // 制約の種類は問わない
            $this->assertIsInt($database->eliminate('foreign_p'));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_affect_ignore($database)
    {
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $this->assertEquals([], $database->insertIgnore('test', ['id' => 1]));
            if ($database->getCompatibleConnection()->getName() !== 'mysqli') {
                $this->assertEquals([], $database->updateIgnore('test', ['id' => 1], ['id' => 2]));
            }

            $database->insert('foreign_p', ['id' => 1, 'name' => 'p']);
            $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1']);

            $this->assertEquals([], $database->reviseIgnore('foreign_p', ['id' => 2, 'name' => 'pp'], ['id' => 1]));
            $this->assertEquals(['id' => 2], $database->upgradeIgnore('foreign_p', ['id' => 2, 'name' => 'pp'], ['id' => 1]));

            // sqlite は外部キーを無視できない（というか DELETE OR IGNORE が対応していない？）のでシンタックスだけ
            $ignore = $database->getCompatiblePlatform()->getIgnoreSyntax();
            $database = $database->dryrun();
            $this->assertEquals([
                "DELETE $ignore FROM foreign_p WHERE id = '1'",
            ], $database->deleteIgnore('foreign_p', ['id' => 1]));
            $this->assertEquals([
                "UPDATE $ignore foreign_p SET name = 'deleted' WHERE id = '1'",
            ], $database->invalidIgnore('foreign_p', ['id' => 1], ['name' => 'deleted']));
            $this->assertStringIgnoreBreak(<<<ACTUAL
                DELETE $ignore FROM foreign_p WHERE
                (id = '1')
                AND ((NOT EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = foreign_p.id)))
                AND ((NOT EXISTS (SELECT * FROM foreign_c2 WHERE foreign_c2.cid = foreign_p.id)))
                ACTUAL, $database->removeIgnore('foreign_p', ['id' => 1])[0]);
            $this->assertEquals([
                "DELETE $ignore FROM foreign_c1 WHERE (id) IN (SELECT foreign_p.id FROM foreign_p WHERE id = '1')",
                "DELETE $ignore FROM foreign_c2 WHERE (cid) IN (SELECT foreign_p.id FROM foreign_p WHERE id = '1')",
                "DELETE $ignore FROM foreign_p WHERE id = '1'",
            ], $database->destroyIgnore('foreign_p', ['id' => 1]));
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
    function test_subquery($database)
    {
        // SQLServer は GROUP_CONCAT に対応していないのでトラップ
        $this->trapThrowable('is not supported by platform');

        $cplatform = $database->getCompatiblePlatform();

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
                'cavg' => $database->subavg('t_comment.comment_id'),
            ],
        ], [], [], 1);
        $this->assertEquals('1', $row['cmin']);
        $this->assertEquals('3', $row['cmax']);
        $this->assertEquals(2.0, $row['cavg']);

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
    function test_selectAtRandom($database)
    {
        // これらのテストは 1/120 の確率でコケるが気にしなくてよい（再実行でパスすれば OK）

        $random = $database->selectAssoc('test', ['id > ?' => 5], OrderBy::randomSuitably(), 5);
        $sorted = $database->selectAssoc('test', ['id > ?' => 5], 'id', 5);
        $this->assertNotSame($sorted, $random);
        $this->assertSame($sorted, kvsort($random, fn($av, $bv, $ak, $bk) => $ak <=> $bk));

        $random = $database->selectLists('test.id', ['id > ?' => 5], OrderBy::randomSuitably(), 5);
        $sorted = $database->selectLists('test.id', ['id > ?' => 5], 'id', 5);
        $this->assertNotSame($sorted, $random);
        $this->assertSame($sorted, array_values(kvsort($random, fn($av, $bv, $ak, $bk) => intval($av) <=> intval($bv))));

        $random = $database->selectPairs('test.id,name', ['id > ?' => 5], OrderBy::randomSuitably(), 5);
        $sorted = $database->selectPairs('test.id,name', ['id > ?' => 5], 'id', 5);
        $this->assertNotSame($sorted, $random);
        $this->assertSame($sorted, kvsort($random, fn($av, $bv, $ak, $bk) => $ak <=> $bk));

        $random = $database->selectValue('test.id', ['id > ?' => 5], OrderBy::randomSuitably(), 1);
        $sorted = $database->selectValue('test.id', ['id' => $random]);
        $this->assertSame($sorted, $random);

        $random = $database->selectTuple('test.id,name', ['id > ?' => 5], OrderBy::randomSuitably(), 1);
        $sorted = $database->selectTuple('test.id,name', ['id' => $random['id']]);
        $this->assertSame($sorted, $random);

        $random = $database->selectArray('multiprimary', ['mainid' => 2], OrderBy::randomSuitably(), 5);
        $sorted = $database->selectArray('multiprimary', ['mainid' => 2], 'subid', 5);
        $this->assertNotSame($sorted, $random);
        $this->assertSame($sorted, array_values(kvsort($random, fn($av, $bv, $ak, $bk) => intval($av['subid']) <=> intval($bv['subid']))));

        // よく分からないエラーが出るのでいったん退避
        if (!$database->getPlatform() instanceof SQLServerPlatform) {
            $database->insert('noauto', ['id' => 'a', 'name' => 'name1']);
            $database->insert('noauto', ['id' => 'b', 'name' => 'name2']);
            $database->insert('noauto', ['id' => 'c', 'name' => 'name3']);

            $random = $database->selectArray('noauto', [], OrderBy::randomSuitably());
            $sorted = $database->selectArray('noauto');
            $this->assertSame($sorted, array_values(kvsort($random, fn($av, $bv, $ak, $bk) => $av['id'] <=> $bv['id'])));
        }

        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'cname11']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'cname12']);
        $database->insert('foreign_c1', ['id' => 2, 'seq' => 1, 'name' => 'cname21']);

        $columns = [
            'foreign_p P' => [
                'foreign_c1 C1' => ['*'],
                'foreign_c2 C2' => ['*'],
            ],
        ];
        $random = $database->selectArray($columns, [], OrderBy::randomSuitably(), 5);
        $sorted = $database->selectArray($columns, [], [], 5);
        $this->assertSame($sorted, array_values(kvsort($random, fn($av, $bv, $ak, $bk) => intval($av['id']) <=> intval($bv['id']))));

        $columns = [
            'foreign_p P' => [
                '<foreign_c1 C1' => ['*'],
                '<foreign_c2 C2' => ['*'],
            ],
        ];
        $random = $database->selectArray($columns, [], OrderBy::randomSuitably(), 5);
        $sorted = $database->selectArray($columns, [], [], 5);
        $this->assertSame($sorted, array_values(kvsort($random, fn($av, $bv, $ak, $bk) => intval($av['id']) <=> intval($bv['id']))));
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
        $this->assertEquals("SELECT COUNT(aggregate.id) AS {$qi('aggregate.id@count')} FROM aggregate", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals(10, $builder->value());

        $builder = $database->selectMax('aggregate.id');
        $this->assertEquals("SELECT MAX(aggregate.id) AS {$qi('aggregate.id@max')} FROM aggregate", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals(10, $builder->value());

        $builder = $database->selectCount('aggregate.id', [], ['group_id2']);
        $this->assertEquals("SELECT group_id2, COUNT(aggregate.id) AS {$qi('aggregate.id@count')} FROM aggregate GROUP BY group_id2", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals([
            10 => 5,
            20 => 5,
        ], $builder->pairs());

        $builder = $database->selectMin('aggregate.id', [], ['group_id2']);
        $this->assertEquals("SELECT group_id2, MIN(aggregate.id) AS {$qi('aggregate.id@min')} FROM aggregate GROUP BY group_id2", "$builder");
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

        $rows = $database->context(['arrayFetch' => null])->selectArrayInShare([
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
                    ['name' => 'c1name1'],
                ],
                'C2'  => [
                    ['name' => 'c2name1'],
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
        $this->assertEquals('hoge-a', $select->tuple()->func('hoge-'));

        $select = $database->select([
            'test' => [
                'id',
                'name' => function ($name = null) { return $name . '-1'; },
            ],
        ])->limit(1);
        $this->assertEquals([1 => 'a-1'], $select->pairs());

        $select = $database->select([
            'test' => ['id' => function ($id = null) { return $id + 1; }],
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
            'delete_at'  => null,
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
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_recache($database)
    {
        $database->refresh();

        $this->assertTrue($database->getSchema()->hasTable('test'));
        $this->assertFalse($database->getSchema()->hasTable('newtable'));

        self::createTables($database->getConnection(), [
            new Schema\Table('newtable',
                [new Schema\Column('id', Type::getType('integer'))],
                [new Schema\Index('PRIMARY', ['id'], true, true)]
            ),
        ]);

        $this->assertFalse($database->getSchema()->hasTable('newtable'));
        $database->recache()->getSchema()->refresh();
        $this->assertTrue($database->getSchema()->hasTable('newtable'));

        $database->getConnection()->createSchemaManager()->dropTable('newtable');
        $database->refresh();
    }
}
