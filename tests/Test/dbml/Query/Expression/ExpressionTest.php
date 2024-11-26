<?php

namespace ryunosuke\Test\dbml\Query\Expression;

use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Queryable;

class ExpressionTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_case()
    {
        $expected = "(CASE WHEN id = 1 THEN ? WHEN id = 2 THEN ? END)";
        $actual = Expression::case(null, ['id = 1' => 'hoge', 'id = 2' => 'fuga']);
        $this->assertExpression($actual, $expected, ['hoge', 'fuga']);

        $expected = "(CASE id WHEN ? THEN ? WHEN ? THEN ? END)";
        $actual = Expression::case('id', ['1' => 'hoge', '2' => 'fuga']);
        $this->assertExpression($actual, $expected, [1, 'hoge', 2, 'fuga']);

        $expected = "(CASE NOW(?) WHEN ? THEN ? WHEN ? THEN ? END)";
        $actual = Expression::case(new Expression('NOW(?)', ['time']), ['1' => 'hoge', '2' => 'fuga']);
        $this->assertExpression($actual, $expected, ['time', 1, 'hoge', 2, 'fuga']);

        $expected = "(CASE id WHEN ? THEN ? WHEN ? THEN ? WHEN ? THEN ? ELSE ? END)";
        $actual = Expression::case('id', ['h' => 'hoge', 'f' => 'fuga', '1' => '123'], 'other');
        $this->assertExpression($actual, $expected, ['h', 'hoge', 'f', 'fuga', 1, '123', 'other']);

        $expected = "(CASE id WHEN ? THEN ? WHEN ? THEN ? WHEN ? THEN ? ELSE NOW(?) END)";
        $actual = Expression::case('id', ['h' => 'hoge', 'f' => 'fuga', '1' => '123'], new Expression('NOW(?)', ['time']));
        $this->assertExpression($actual, $expected, ['h', 'hoge', 'f', 'fuga', 1, '123', 'time']);

        // very very complex
        $expected = "(CASE (SELECT t.c FROM t WHERE w = ?) WHEN ? THEN (SELECT t.d FROM t WHERE dw = ?) WHEN ? THEN ADD(?, ?) ELSE NOW(?) END)";
        $actual = Expression::case(
            self::getDummyDatabase()->select('t.c', ['w' => 1]),
            [
                'qb'  => self::getDummyDatabase()->select('t.d', ['dw' => 2]),
                'exp' => new Expression('ADD(?, ?)', [3, 4]),
            ],
            new Expression('NOW(?)', ['time'])
        );
        $this->assertExpression($actual, $expected, [1, 'qb', 2, 'exp', 3, 4, 'time']);
    }

    function test_over()
    {
        $expected = "OVER()";
        $actual = Expression::over();
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY gid ORDER BY id ROWS 1 PRECEDING)";
        $actual = Expression::over('gid', 'id', 'ROWS 1 PRECEDING');
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY t.c, column)";
        $actual = Expression::over(['t.c', 'column']);
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(ORDER BY c1 ASC, c2 ASC, c3 DESC, c4 DESC)";
        $actual = Expression::over([], ['c1 ASC', 'c2' => true, 'c3' => false, '-c4']);
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING)";
        $actual = Expression::over(frame: 'ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING');
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY t.c, column ORDER BY c1 ASC, c2 ASC, c3 DESC)";
        $actual = Expression::over(['t.c', 'column'], ['c1 ASC', 'c2' => true, 'c3' => false]);
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY t.c, column ORDER BY c1 ASC, c2 ASC, c3 DESC ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING)";
        $actual = Expression::over(['t.c', 'column'], ['c1 ASC', 'c2' => true, 'c3' => false], 'ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING');
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY t.c, column ORDER BY c1 ASC, c2 ASC, c3 DESC ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING)";
        $actual = Expression::over(['t.c', 'column'], ['c1 ASC', 'c2' => true, 'c3' => false], ['ROWS' => [null, null]]);
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY t.c, column ORDER BY c1 ASC, c2 ASC, c3 DESC ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING)";
        $actual = Expression::over(['t.c', 'column'], ['c1 ASC', 'c2' => true, 'c3' => false], ['ROWS' => [1, 1]]);
        $this->assertExpression($actual, $expected, []);

        $expected = "OVER(PARTITION BY t.c, column ORDER BY c1 ASC, c2 ASC, c3 DESC ROWS CURRENT ROW)";
        $actual = Expression::over(['t.c', 'column'], ['c1 ASC', 'c2' => true, 'c3' => false], ['ROWS' => 0]);
        $this->assertExpression($actual, $expected, []);
        $expected = "OVER(PARTITION BY t.c, column ORDER BY c1 ASC, c2 ASC, c3 DESC ROWS NOW(?) PRECEDING)";
        $actual = Expression::over(['t.c', 'column'], ['c1 ASC', 'c2' => true, 'c3' => false], ['ROWS' => new Expression('NOW(?)', 123)]);
        $this->assertExpression($actual, $expected, [123]);
    }

    function test_forge()
    {
        $actual = Expression::forge('column');
        $this->assertEquals('column', $actual);

        $actual = Expression::forge('NOW()');
        $this->assertEquals(new Expression('NOW()'), $actual);

        $actual = Expression::forge('null');
        $this->assertEquals(new Expression('NULL'), $actual);

        $actual = Expression::forge(123);
        $this->assertEquals(new Expression('123'), $actual);

        $actual = Expression::forge('+123');
        $this->assertEquals(new Expression('+123'), $actual);

        $actual = Expression::forge(3.14);
        $this->assertEquals(new Expression('3.14'), $actual);

        $actual = Expression::forge(true);
        $this->assertEquals(new Expression(1), $actual);

        $actual = Expression::forge(false);
        $this->assertEquals(new Expression(0), $actual);
    }

    function test___construct()
    {
        $expr = new Expression('hogera', 1);
        $this->assertEquals('hogera', $expr->getQuery());
        $this->assertEquals([1], $expr->getParams());

        $expr = new Expression('hogera', [1, 2, 3]);
        $this->assertEquals('hogera', $expr->getQuery());
        $this->assertEquals([1, 2, 3], $expr->getParams());

        $expr = new Expression('hogera', new \ArrayObject([1, 2, 3]));
        $this->assertEquals('hogera', $expr->getQuery());
        $this->assertEquals([1, 2, 3], $expr->getParams());
    }

    function test___callStatic()
    {
        /** @var Expression $actual */

        $actual = Expression::{'GROUP_CONCAT(name ORDER BY id SEPARATOR ",")'}();
        $this->assertEquals('GROUP_CONCAT(name ORDER BY id SEPARATOR ",")', $actual);
        $this->assertEquals([], $actual->getParams());

        $actual = Expression::{'ADD(?, ?)'}(1, 2);
        $this->assertEquals('ADD(?, ?)', $actual);
        $this->assertEquals([1, 2], $actual->getParams());

        /** @noinspection PhpUndefinedMethodInspection */
        $actual = Expression::ADD(1, 2);
        $this->assertEquals('ADD(?, ?)', $actual);
        $this->assertEquals([1, 2], $actual->getParams());

        /** @noinspection PhpUndefinedMethodInspection */
        $actual = Expression::ADD(11, Expression::TIME(), Expression::RAND(33));
        $this->assertEquals('ADD(?, TIME(), RAND(?))', $actual);
        $this->assertEquals([11, 33], $actual->getParams());
    }

    function test___toString()
    {
        $expr = new Expression('hogera');
        $this->assertEquals('hogera', $expr);
    }

    function test_params()
    {
        $expr = new Expression('hogera', [1, 2]);
        $expr->setParams([9]);
        $this->assertEquals([9], $expr->getParams());
    }

    function test_merge()
    {
        $expr = new Expression('hogera', [1, 2, 3]);
        $this->assertEquals('hogera', $expr->merge($params));
        $this->assertEquals([1, 2, 3], $params);
    }

    function assertExpression(Queryable $expr, $expectedQuery, array $expectedparams)
    {
        $this->assertEquals($expectedQuery, (string) $expr);
        $this->assertEquals($expectedparams, $expr->getParams());
    }
}
