<?php

namespace ryunosuke\Test\dbml\Query\Clause;

use ryunosuke\dbml\Query\Clause\Select;
use ryunosuke\Test\Database;

class SelectTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test___callStatic()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $actual = Select::hoge('columnName');
        $this->assertEquals('columnName AS hoge', $actual);

        $this->assertException(new \InvalidArgumentException('length must be 1'), function () {
            /** @noinspection PhpUndefinedMethodInspection */
            Select::hoge('columnName1', 'columnName2');
        });
    }

    function test_split()
    {
        $this->assertSame([null, 'hoge'], Select::split('hoge'));
        $this->assertSame(['fuga', 'hoge'], Select::split('hoge', 'fuga'));
        $this->assertSame(['fuga', 'hoge'], Select::split('hoge as fuga'));
        $this->assertSame(['fuga', 'hoge'], Select::split('hoge  as  fuga'));
        $this->assertSame(['fuga', 'hoge'], Select::split('hoge    fuga'));
        $this->assertSame(['fuga', 'hoge'], Select::split('hoge fuga'));
        $this->assertSame(['fuga', 'hoge'], Select::split('hoge fuga', 'noooo'));
        $this->assertSame([null, 'hoge  as  fuga as piyo'], Select::split('hoge  as  fuga as piyo'));
        $this->assertSame(['alias', 'hoge  fuga as piyo'], Select::split('hoge  fuga as piyo', 'alias'));
        $this->assertSame(['hogefuga', 'hoge,fuga'], Select::split('hoge,fuga as hogefuga'));
        $this->assertSame(['hogefuga', 'hoge, fuga'], Select::split('hoge, fuga as hogefuga'));
        $this->assertSame([null, 'hoge, fuga'], Select::split('hoge, fuga'));
    }

    function test_forge()
    {
        $this->assertEquals('actual AS alias', Select::forge('alias', 'actual'));
        $this->assertEquals('actual', Select::forge('', 'actual'));
        $this->assertEquals('actual AS alias', Select::forge('', 'actual AS alias'));
    }

    function test_getAlias()
    {
        $alias = new Select('alias', 'actual');
        $this->assertEquals('alias', $alias->getAlias());
    }

    function test_getActual()
    {
        $alias = new Select('alias', 'actual');
        $this->assertEquals('actual', $alias->getActual());
    }

    function test_getModifier()
    {
        $alias = new Select('alias', 'actual', 'table');
        $this->assertEquals('table', $alias->getModifier());
    }

    function test_isPlaceholdable()
    {
        $this->assertFalse((new Select('alias', 'actual'))->isPlaceholdable());
        $this->assertTrue((new Select('alias', 'actual', null, true))->isPlaceholdable());
        $this->assertTrue((Select::forge(Database::AUTO_KEY . 'hoge', 'actual'))->isPlaceholdable());
        $this->assertTrue((Select::forge('hoge', 'NULL'))->isPlaceholdable());
    }

    function test___toString()
    {
        $this->assertEquals('T.actual AS alias', (string) Select::forge('alias', 'T.actual'));
        $this->assertEquals('T.actual', (string) Select::forge('actual', 'T.actual'));
        $this->assertEquals('actual AS alias', (string) Select::forge('alias', 'actual'));
        $this->assertEquals('actual', (string) Select::forge('', 'actual'));
    }
}
