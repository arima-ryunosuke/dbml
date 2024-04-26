<?php

namespace ryunosuke\Test\dbml\Query\Expression;

use ryunosuke\dbml\Query\Expression\Alias;
use ryunosuke\Test\Database;

class AliasTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test___callStatic()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $actual = Alias::hoge('columnName');
        $this->assertEquals('columnName AS hoge', $actual);

        $this->assertException(new \InvalidArgumentException('length must be 1'), function () {
            /** @noinspection PhpUndefinedMethodInspection */
            Alias::hoge('columnName1', 'columnName2');
        });
    }

    function test_split()
    {
        $this->assertSame([null, 'hoge'], Alias::split('hoge'));
        $this->assertSame(['fuga', 'hoge'], Alias::split('hoge', 'fuga'));
        $this->assertSame(['fuga', 'hoge'], Alias::split('hoge as fuga'));
        $this->assertSame(['fuga', 'hoge'], Alias::split('hoge  as  fuga'));
        $this->assertSame(['fuga', 'hoge'], Alias::split('hoge    fuga'));
        $this->assertSame(['fuga', 'hoge'], Alias::split('hoge fuga'));
        $this->assertSame(['fuga', 'hoge'], Alias::split('hoge fuga', 'noooo'));
        $this->assertSame([null, 'hoge  as  fuga as piyo'], Alias::split('hoge  as  fuga as piyo'));
        $this->assertSame(['alias', 'hoge  fuga as piyo'], Alias::split('hoge  fuga as piyo', 'alias'));
        $this->assertSame(['hogefuga', 'hoge,fuga'], Alias::split('hoge,fuga as hogefuga'));
        $this->assertSame(['hogefuga', 'hoge, fuga'], Alias::split('hoge, fuga as hogefuga'));
        $this->assertSame([null, 'hoge, fuga'], Alias::split('hoge, fuga'));
    }

    function test_forge()
    {
        $this->assertEquals('actual AS alias', Alias::forge('alias', 'actual'));
        $this->assertEquals('actual', Alias::forge('', 'actual'));
        $this->assertEquals('actual AS alias', Alias::forge('', 'actual AS alias'));
    }

    function test_getAlias()
    {
        $alias = new Alias('alias', 'actual');
        $this->assertEquals('alias', $alias->getAlias());
    }

    function test_getActual()
    {
        $alias = new Alias('alias', 'actual');
        $this->assertEquals('actual', $alias->getActual());
    }

    function test_getModifier()
    {
        $alias = new Alias('alias', 'actual', 'table');
        $this->assertEquals('table', $alias->getModifier());
    }

    function test_isPlaceholdable()
    {
        $this->assertFalse((new Alias('alias', 'actual'))->isPlaceholdable());
        $this->assertTrue((new Alias('alias', 'actual', null, true))->isPlaceholdable());
        $this->assertTrue((Alias::forge(Database::AUTO_KEY . 'hoge', 'actual'))->isPlaceholdable());
        $this->assertTrue((Alias::forge('hoge', 'NULL'))->isPlaceholdable());
    }

    function test___toString()
    {
        $this->assertEquals('T.actual AS alias', (string) Alias::forge('alias', 'T.actual'));
        $this->assertEquals('T.actual', (string) Alias::forge('actual', 'T.actual'));
        $this->assertEquals('actual AS alias', (string) Alias::forge('alias', 'actual'));
        $this->assertEquals('actual', (string) Alias::forge('', 'actual'));
    }
}
