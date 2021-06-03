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
        $this->assertException('mismatch', function () use ($database) {
            new Statement('this is no=?, this is named=:named', ['named' => 'hoge'], $database);
        });

        // 引数のほうが多い
        $this->assertException('mismatch', function () use ($database) {
            new Statement('this is no=?, this is named=:named', ['hoge', 'fuga'], $database);
        });
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

        $master->exec('CREATE TABLE test_master(id integer)');
        $master->exec('insert into test_master values(1)');
        $slave->exec('CREATE TABLE test_slave(id integer)');
        $slave->exec('insert into test_slave values(1)');

        $expected = [
            [
                'hoge' => 'hoge',
                'fuga' => 'fuga',
            ],
        ];

        // executeSelect はスレーブに接続されるのでエラーにならないはず
        $stmt = new Statement('select ? as hoge, :fuga as fuga from test_slave', ['hoge'], $database);
        $this->assertEquals($expected, $stmt->executeSelect([':fuga' => 'fuga'])->fetchAll());

        // executeAffect はマスターに接続されるのでエラーにならないはず
        $stmt = new Statement('select ? as hoge, :fuga as fuga from test_master', ['hoge'], $database);
        $this->assertEquals($expected, $stmt->executeAffect([':fuga' => 'fuga'])->fetchAll());

        // connection を指定すればそれが使われるはず
        $stmt = new Statement('select ? as hoge, :fuga as fuga from test_master', ['hoge'], $database);
        $this->assertEquals($expected, $stmt->executeSelect([':fuga' => 'fuga'], $master)->fetchAll());
        $stmt = new Statement('select ? as hoge, :fuga as fuga from test_slave', ['hoge'], $database);
        $this->assertEquals($expected, $stmt->executeSelect([':fuga' => 'fuga'], $slave)->fetchAll());
    }
}
