<?php

namespace ryunosuke\Test\dbml\Driver;

use Doctrine\DBAL\Cache\ArrayResult;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;

class ResultTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_fetchAssociative()
    {
        $result = new Result([
            ['a' => 'a1', 'b' => 'b1', 'c' => 'c1'],
            ['a' => 'a2', 'b' => 'b2', 'c' => 'c2'],
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
        $this->assertEquals(['a' => 'a1', 'b' => 'b1', 'c' => 'c1'], $result->fetchAssociative());

        $result->setSameCheckMethod('strict');
        $this->assertEquals(['A' => 'a2', 'B' => 'b2', 'C' => 'c2'], $result->fetchAssociative());

        $this->assertEquals(false, $result->fetchAssociative());
    }

    function test_fetchAllAssociative()
    {
        $result = new Result([
            ['a' => 'a1', 'b' => 'b1', 'c' => 'c1'],
            ['a' => 'a2', 'b' => 'b2', 'c' => 'c2'],
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
            ['a' => 'a1', 'b' => 'b1', 'c' => 'c1'],
            ['a' => 'a2', 'b' => 'b2', 'c' => 'c2'],
        ], $result->fetchAllAssociative());

        $result = new Result([
            ['a' => 'a1', 'b' => 'b1', 'c' => 'c1'],
            ['a' => 'a2', 'b' => 'b2', 'c' => 'c2'],
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

        $this->assertException('is invalid', L($result)->checkSameColumn(['a', 'b', 'c'], false));

        $result->setSameCheckMethod('noallow');
        $this->assertException('cause noallow', L($result)->checkSameColumn(['a', 'a', 'a'], false));

        $result->setSameCheckMethod('strict');
        $this->assertEquals(['a' => 1], $result->checkSameColumn([1, 1, 1], false));
        $this->assertException('cause strict', L($result)->checkSameColumn(['1', 1, null], false));

        $result->setSameCheckMethod('loose');
        $this->assertEquals(['a' => null], $result->checkSameColumn(['1', 1, null], false));
        $this->assertException('cause loose', L($result)->checkSameColumn(['1', 2, null], false));
    }
}

class MockResult
{
    private \Doctrine\DBAL\Driver\Result $internal;

    public function __construct($data)
    {
        $this->internal = new ArrayResult($data);
    }

    public function __call($name, $arguments)
    {
        return $this->internal->$name(...$arguments);
    }
}

class Result extends MockResult implements ResultInterface
{
    use ResultTrait {
        checkSameColumn as public;
    }

    private array $meta;

    public function __construct($data, $meta)
    {
        parent::__construct($data);

        $this->meta = $meta;
    }

    public function getMetadata(): array
    {
        return $this->meta;
    }
}
