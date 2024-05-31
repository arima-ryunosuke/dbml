<?php

namespace ryunosuke\Test\dbml\Driver;

use ryunosuke\dbml\Driver\ArrayResult;

class ArrayResultTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_fetch()
    {
        $result = new ArrayResult([
            ['a1', 'b1', 'c1'],
            ['a2', 'b2', 'c2'],
            ['a3', 'b3', 'c3'],
            ['a4', 'b4', 'c4'],
            ['a5', 'b5', 'c5'],
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

        $this->assertEquals(['a1', 'b1', 'c1'], $result->fetchNumeric());
        $this->assertEquals(['A' => 'a2', 'B' => 'b2', 'C' => 'c2'], $result->fetchAssociative());
        $this->assertEquals('a3', $result->fetchOne());
        $this->assertEquals([
            ['a4', 'b4', 'c4'],
            ['a5', 'b5', 'c5'],
        ], $result->fetchAllNumeric());

        $this->assertEquals(false, $result->fetchNumeric());
        $this->assertEquals(false, $result->fetchAssociative());
        $this->assertEquals(false, $result->fetchOne());
        $this->assertEquals([], $result->fetchAllAssociative());
        $this->assertEquals([], $result->fetchFirstColumn());

        $this->assertEquals(5, $result->rowCount());
        $this->assertEquals(3, $result->columnCount());
        $this->assertEquals(null, $result->free());
    }

    function test_checkSameColumn()
    {
        $result = new ArrayResult([
            ['a', 'a', 'X'],
            ['a', 'a', 'a'],
            ['b', 'b', 'b'],
        ], [
            [
                'aliasColumnName' => 'A',
            ],
            [
                'aliasColumnName' => 'A',
            ],
            [
                'aliasColumnName' => 'A',
            ],
        ]);

        $result->setSameCheckMethod('strict');
        that($result)->fetchAssociative()->wasThrown('cause strict');
        $this->assertEquals(['A' => 'a'], $result->fetchAssociative());
        $this->assertEquals([['A' => 'b']], $result->fetchAllAssociative());
    }
}
