<?php

namespace ryunosuke\Test\dbml\Query;

use Doctrine\DBAL\DriverManager;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\Test\Database;

class StatementTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___construct($database)
    {
        // ? の数と引数が同じ
        $stmt = new Statement('this is no=?, this is named=:named', ['hoge'], $database);
        $this->assertEquals('this is no=:__dbml_auto_bind0, this is named=:named', $stmt->getQuery());
        $this->assertEquals(['__dbml_auto_bind0' => 'hoge'], $stmt->getParams());

        // 引数のほうが少ない
        $this->assertException('does not have', function () use ($database) {
            new Statement('this is no=?, this is named=:named', ['named' => 'hoge'], $database);
        });

        // 引数のほうが多い
        $this->assertException('length is long', function () use ($database) {
            new Statement('this is no=?, this is named=:named', ['hoge', 'fuga'], $database);
        });
    }

    function test___debugInfo()
    {
        $debugString = print_r(new Statement('query', [], self::getDummyDatabase()), true);
        $this->assertStringContainsString('query:', $debugString);
        $this->assertStringNotContainsString('database:', $debugString);
        $this->assertStringNotContainsString('statements:', $debugString);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_queryInto($database)
    {
        $queryInto = function (Statement $stmt, $params) use ($database) {
            return $database->queryInto($stmt->merge($params), $params);
        };

        $stmt = new Statement('this is no=1, this is named=:named', [], $database);
        $this->assertEquals("this is no=1, this is named='fuga'", $queryInto($stmt, ['named' => 'fuga']));

        $stmt = new Statement('this is no=?, this is named=:named', ['hoge'], $database);
        $this->assertEquals("this is no='hoge', this is named='fuga'", $queryInto($stmt, ['named' => 'fuga']));

        $stmt = new Statement('this is no=?, this is named1=:hoge, this is named2=:hogehoge', ['sss'], $database);
        $this->assertEquals("this is no='sss', this is named1='hoge1', this is named2='hoge2'", $queryInto($stmt, ['hoge' => 'hoge1', 'hogehoge' => 'hoge2']));
    }

    function test_execute()
    {
        $master = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $slave = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $database = new Database([$master, $slave]);

        $master->executeStatement('CREATE TABLE test_master(id integer, name string)');
        $master->executeStatement('insert into test_master values(1, "hoge")');
        $slave->executeStatement('CREATE TABLE test_slave(id integer, name string)');
        $slave->executeStatement('insert into test_slave values(1, "hoge")');

        $expected = [
            [
                'hoge' => 'hoge',
                'fuga' => 'fuga',
                'piyo' => '1',
            ],
        ];

        // executeSelect はスレーブに接続されるのでエラーにならないはず
        $stmt = new Statement('select ? as hoge, :fuga as fuga, ? as piyo from test_slave', [fn() => 'hoge', fn() => true], $database);
        $this->assertEquals($expected, $stmt->executeSelect(['fuga' => fn() => 'fuga'])->fetchAllAssociative());

        // executeAffect はマスターに接続されるのでエラーにならないはず
        $stmt = new Statement('update test_master set name = :fuga where id = ?', [1], $database);
        $this->assertEquals(1, $stmt->executeAffect(['fuga' => fn() => 'fuga']));

        // connection を指定すればそれが使われるはず
        $stmt = new Statement('select ? as hoge, :fuga as fuga, ? as piyo from test_master', [fn() => 'hoge', fn() => true], $database);
        $this->assertEquals($expected, $stmt->executeSelect(['fuga' => fn() => 'fuga'], $master)->fetchAllAssociative());
        $stmt = new Statement('select ? as hoge, :fuga as fuga, ? as piyo from test_slave', [fn() => 'hoge', fn() => true], $database);
        $this->assertEquals($expected, $stmt->executeSelect(['fuga' => fn() => 'fuga'], $slave)->fetchAllAssociative());
    }
}
