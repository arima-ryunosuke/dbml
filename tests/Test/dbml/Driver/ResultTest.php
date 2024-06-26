<?php

namespace ryunosuke\Test\dbml\Driver;

use ryunosuke\dbml\Driver\ArrayResult;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;

class ResultTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_doctrineType()
    {
        // for coverage
        $this->assertNull(Result::doctrineType('hoge'));
    }

    function test_fetchAssociative()
    {
        $result = new Result([
            ['a1', 'b1', 'c1'],
            ['a2', 'b2', 'c2'],
        ], [
            [
                'aliasColumnName' => 'A',
            ],
            [
                'aliasColumnName' => 'B',
            ],
            [
                'aliasColumnName' => 'C',
            ],
        ]);
        $this->assertEquals(['A' => 'a1', 'B' => 'b1', 'C' => 'c1'], $result->fetchAssociative());

        $result->setSameCheckMethod('strict');
        $this->assertEquals(['A' => 'a2', 'B' => 'b2', 'C' => 'c2'], $result->fetchAssociative());

        $this->assertEquals(false, $result->fetchAssociative());
    }

    function test_fetchAllAssociative()
    {
        $result = new Result([
            ['a1', 'b1', 'c1'],
            ['a2', 'b2', 'c2'],
        ], [
            [
                'aliasColumnName' => 'A',
            ],
            [
                'aliasColumnName' => 'B',
            ],
            [
                'aliasColumnName' => 'C',
            ],
        ]);
        $this->assertEquals([
            ['A' => 'a1', 'B' => 'b1', 'C' => 'c1'],
            ['A' => 'a2', 'B' => 'b2', 'C' => 'c2'],
        ], $result->fetchAllAssociative());

        $result = new Result([
            ['A' => 'a1', 'B' => 'b1', 'C' => 'c1'],
            ['A' => 'a2', 'B' => 'b2', 'C' => 'c2'],
        ], [
            [
                'aliasColumnName' => 'A',
            ],
            [
                'aliasColumnName' => 'B',
            ],
            [
                'aliasColumnName' => 'C',
            ],
        ]);
        $result->setSameCheckMethod('strict');
        $this->assertEquals([
            ['A' => 'a1', 'B' => 'b1', 'C' => 'c1'],
            ['A' => 'a2', 'B' => 'b2', 'C' => 'c2'],
        ], $result->fetchAllAssociative());
    }

    function test_checkSameColumn()
    {
        $result = new Result([], [
            [
                'aliasColumnName' => 'a',
            ],
            [
                'aliasColumnName' => 'a',
            ],
            [
                'aliasColumnName' => 'a',
            ],
        ]);

        that($result)->checkSameColumn(['a', 'b', 'c'], false)->wasThrown('is invalid');

        $result->setSameCheckMethod('noallow');
        that($result)->checkSameColumn(['a', 'a', 'a'], false)->wasThrown('cause noallow');

        $result->setSameCheckMethod('strict');
        $this->assertEquals(['a' => 1], $result->checkSameColumn([1, 1, 1], false));
        that($result)->checkSameColumn(['1', 1, null], false)->wasThrown('cause strict');

        $result->setSameCheckMethod('loose');
        $this->assertEquals(['a' => null], $result->checkSameColumn(['1', 1, null], false));
        that($result)->checkSameColumn(['1', 2, null], false)->wasThrown('cause loose');
    }
}

class Result extends ArrayResult implements ResultInterface
{
    use ResultTrait {
        checkSameColumn as public;
    }
}
