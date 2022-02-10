<?php

namespace ryunosuke\Test\dbml\Utility;

use ryunosuke\dbml\Utility\Adhoc;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

class AdhocTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_reargument()
    {
        $closure = function ($a = 1, $b = 2, $c = 3) { };

        $arguments = [10, 20];
        $this->assertEquals([], Adhoc::reargument($arguments, $closure));
        $this->assertEquals([10, 20, 3], $arguments);

        $arguments = [10, 'x', 20];
        $this->assertEquals(['oa' => 'x'], Adhoc::reargument($arguments, $closure, [1 => 'oa']));
        $this->assertEquals([10, 20, 3], $arguments);

        $arguments = [10, 'x', 'y', 20, 30];
        $this->assertEquals(['oa' => 'x', 'ob' => 'y'], Adhoc::reargument($arguments, $closure, [1 => 'oa', 2 => 'ob']));
        $this->assertEquals([10, 20, 30], $arguments);

        $arguments = [10, 'x', 20, 30, 'z'];
        $this->assertEquals(['oa' => 'x', 'oc' => 'z'], Adhoc::reargument($arguments, $closure, [1 => 'oa', 4 => 'oc']));
        $this->assertEquals([10, 20, 30], $arguments);

        $arguments = [1];
        $this->assertEquals([], Adhoc::reargument($arguments, function ($a, $b = 20) { }));
        $this->assertEquals([1, 20], $arguments);

        $arguments = [1];
        $this->assertEquals([], Adhoc::reargument($arguments, function ($a, $b = 20, $c = 30) { }));
        $this->assertEquals([1, 20, 30], $arguments);

        $this->assertException('too short', function () {
            $arguments = [];
            Adhoc::reargument($arguments, function ($x) { });
        });
    }

    function test_is_empty()
    {
        $this->assertTrue(Adhoc::is_empty([]));
        $this->assertTrue(Adhoc::is_empty(null));
        $this->assertTrue(Adhoc::is_empty(''));

        $this->assertFalse(Adhoc::is_empty([1]));
        $this->assertFalse(Adhoc::is_empty('0'));
        $this->assertFalse(Adhoc::is_empty(0));
        $this->assertFalse(Adhoc::is_empty(false));

        $this->assertFalse(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['id' => 1])));
        $this->assertFalse(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['!id' => 1])));
        $this->assertTrue(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['!id' => null])));
        $this->assertTrue(Adhoc::is_empty(self::getDummyDatabase()->select('t', ['id' => 1, '!id' => null])));
    }

    function test_wrapParentheses()
    {
        // シンプル配列
        $this->assertEquals([], Adhoc::wrapParentheses([]));
        $this->assertEquals(['hoge'], Adhoc::wrapParentheses(['hoge']));
        $this->assertEquals(['x' => 'hoge'], Adhoc::wrapParentheses(['x' => 'hoge']));
        $this->assertEquals(['(hoge)', '(fuga)'], Adhoc::wrapParentheses(['hoge', 'fuga']));
        $this->assertEquals(['x' => '(hoge)', 'y' => '(fuga)'], Adhoc::wrapParentheses(['x' => 'hoge', 'y' => 'fuga']));

        // 入れ子配列
        $this->assertEquals([
            '(hoge)',
            'x' => [
                '(fuga)',
                'y' => [
                    '(piyo)',
                    'z' => ['zzz'],
                ],
            ],
        ], Adhoc::wrapParentheses([
            'hoge',
            'x' => [
                'fuga',
                'y' => [
                    'piyo',
                    'z' => ['zzz'],
                ],
            ],
        ]));
    }

    function test_containQueryable()
    {
        $e = self::getDummyDatabase()->raw('column');
        $this->assertFalse(Adhoc::containQueryable(null));
        $this->assertFalse(Adhoc::containQueryable('hoge'));
        $this->assertFalse(Adhoc::containQueryable([]));
        $this->assertFalse(Adhoc::containQueryable(['hoge']));
        $this->assertFalse(Adhoc::containQueryable($e));
        $this->assertTrue(Adhoc::containQueryable([$e]));
        $this->assertTrue(Adhoc::containQueryable([$e, $e]));
        $this->assertTrue(Adhoc::containQueryable([null, $e, 'hoge']));
    }

    function test_modifier()
    {
        $this->assertEquals(['column'], Adhoc::modifier('', [], ['column']));
        $this->assertEquals(['c' => 'column'], Adhoc::modifier('', [], ['c' => 'column']));

        $this->assertEquals(['column'], Adhoc::modifier('T', [], ['column']));
        $this->assertEquals(['UPPER(column)' => 'hoge'], Adhoc::modifier('T', [], ['UPPER(column)' => 'hoge']));
        $this->assertEquals(['!T.c' => 'column'], Adhoc::modifier('T', ['c' => true], ['!c' => 'column']));
        $this->assertEquals(['T.c' => 'column'], Adhoc::modifier('T', ['c' => true], ['c' => 'column']));
        $this->assertEquals(['!T.c' => 'column'], Adhoc::modifier('T', ['c' => true], ['!c' => 'column']));
        $this->assertEquals(['-T.a <> ?' => 'column', '+T.b = ?' => 'column'], Adhoc::modifier('T', ['a' => true, 'b' => true], ['-a <> ?' => 'column', '+b = ?' => 'column']));
        $this->assertEquals(['T.a' => 'columnA', ['T.b' => 'columnB'], 'c' => 'columnC'], Adhoc::modifier('T', ['a' => true, 'b' => true], ['a' => 'columnA', ['b' => 'columnB'], 'c' => 'columnC']));

        $qb = self::getDummyDatabase()->subexists('t', ['id' => 1]);
        $this->assertEquals(['c' => $qb], Adhoc::modifier('T', ['c' => true], ['c' => $qb]));

        $qb = self::getDummyDatabase()->subquery('t', ['id' => 1]);
        $this->assertEquals(['T.c' => $qb], Adhoc::modifier('T', ['c' => true], ['c' => $qb]));

        $e = self::getDummyDatabase()->raw('column');
        $this->assertEquals(['T.c' => $e], Adhoc::modifier('T', ['c' => true], ['c' => $e]));
        $this->assertEquals(['c' => [$e]], Adhoc::modifier('T', ['c' => true], ['c' => [$e]]));
    }

    function test_cacheGetOrSet()
    {
        $cache = new Psr16Cache(new ArrayAdapter());

        $called = 0;
        $provider = function () use (&$called) {
            return ++$called;
        };
        $this->assertEquals(1, Adhoc::cacheGetOrSet($cache, 'hoge', $provider));
        $this->assertEquals(1, $called);
        $this->assertEquals(1, Adhoc::cacheGetOrSet($cache, 'hoge', $provider));
        $this->assertEquals(1, $called);
        $this->assertEquals(2, Adhoc::cacheGetOrSet($cache, 'fuga', $provider));
        $this->assertEquals(2, $called);
    }
}
