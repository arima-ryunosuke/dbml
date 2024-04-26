<?php

namespace ryunosuke\Test\dbml\Query\Expression;

use ryunosuke\dbml\Query\Expression\Expression;

class ExpressionTest extends \ryunosuke\Test\AbstractUnitTestCase
{
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
}
