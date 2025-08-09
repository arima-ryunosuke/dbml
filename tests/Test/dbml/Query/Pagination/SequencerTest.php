<?php

namespace ryunosuke\Test\dbml\Query\Pagination;

use ryunosuke\dbml\Query\Pagination\Sequencer;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\Test\Database;

class SequencerTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideSequencer()
    {
        return array_map(function ($v) {
            return [
                new Sequencer((new QueryBuilder($v[0]))->column('paging')),
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function test___construct($sequencer, $database)
    {
        $builder = $database->select('paging');
        $sequencer = new Sequencer($builder);

        $ref = new \ReflectionProperty($sequencer, 'builder');
        $ref->setAccessible(true);
        $this->assertSame($builder, $ref->getValue($sequencer));
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_sequence($sequencer)
    {
        $sequencer->sequence(['id' => 0], 10);
        $this->assertCount(10, $sequencer->getItems());

        $sequencer->sequence(['id' => 1], 5);
        $this->assertCount(5, $sequencer->getItems());

        $this->assertException(new \InvalidArgumentException('length must be > 0'), L($sequencer)->sequence([], 2));

        $this->assertException(new \InvalidArgumentException('must be positive number'), L($sequencer)->sequence(['id' => 0], 0));
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function test_sequence_transaction($sequencer, $database)
    {
        $database->transact(function () use ($sequencer) {
            that($sequencer)->sequence(['id' => 0], 3)->getItems()->is([
                ["id" => "1", "name" => "a"],
                ["id" => "2", "name" => "b"],
                ["id" => "3", "name" => "c"],
            ]);
            that($sequencer)->sequence(['id' => 3], 3)->getItems()->is([
                ["id" => "4", "name" => "d"],
                ["id" => "5", "name" => "e"],
                ["id" => "6", "name" => "f"],
            ]);
        });
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function test_sequence_multi($sequencer, $database)
    {
        if (!$database->getCompatiblePlatform()->supportsRowConstructor()) {
            return;
        }

        $builder = $database->select('multiprimary');
        $sequencer = new Sequencer($builder);

        $sequencer->sequence([
            'mainid' => 1,
            'subid'  => 2,
        ], 5, null, null);
        $this->assertEquals([
            [
                "mainid" => "1",
                "subid"  => "3",
                "name"   => "c",
            ],
            [
                "mainid" => "1",
                "subid"  => "4",
                "name"   => "d",
            ],
            [
                "mainid" => "1",
                "subid"  => "5",
                "name"   => "e",
            ],
            [
                "mainid" => "2",
                "subid"  => "6",
                "name"   => "f",
            ],
            [
                "mainid" => "2",
                "subid"  => "7",
                "name"   => "g",
            ],
        ], $sequencer->getItems());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function test_getPrev($sequencer, $database)
    {
        $sequencer->sequence(['id' => 1], 10);
        $this->assertEquals(['id' => -2], $sequencer->getPrev());

        $sequencer->sequence(['id' => 5], 10);
        $this->assertEquals(['id' => -6], $sequencer->getPrev());

        $database->delete('paging', ['id' => 1]);
        $sequencer->sequence(['id' => -1], 10, true);
        $this->assertFalse($sequencer->getPrev());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_getNext($sequencer)
    {
        $sequencer->sequence(['id' => 0], 10);
        $this->assertEquals(['id' => 10], $sequencer->getNext());

        $sequencer->sequence(['id' => 1], 10);
        $this->assertEquals(['id' => 11], $sequencer->getNext());

        $sequencer->sequence(['id' => 1], 100);
        $this->assertFalse($sequencer->getNext());

        $sequencer->sequence(['id' => 1], 101);
        $this->assertFalse($sequencer->getNext());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_getDidirection($sequencer)
    {
        // 片方向：正順(prev が常に false)
        $sequencer->sequence(['id' => 50], 10, true, false);
        $this->assertFalse($sequencer->getPrev());
        $sequencer->sequence(['id' => 50], 10, false, false);
        $this->assertFalse($sequencer->getPrev());
        // 片方向：正順(next は通常通り)
        $sequencer->sequence(['id' => 50], 10, true, false);
        $this->assertEquals(['id' => 60], $sequencer->getNext());
        $sequencer->sequence(['id' => 50], 10, false, false);
        $this->assertEquals(['id' => 40], $sequencer->getNext());
        // 片方向：逆順(prev は通常通り)
        $sequencer->sequence(['id' => -50], 10, true, false);
        $this->assertEquals(['id' => -40], $sequencer->getPrev());
        $sequencer->sequence(['id' => -50], 10, false, false);
        $this->assertEquals(['id' => -60], $sequencer->getPrev());
        // 片方向：逆順(next が常に false)
        $sequencer->sequence(['id' => -50], 10, true, false);
        $this->assertFalse($sequencer->getNext());
        $sequencer->sequence(['id' => -50], 10, false, false);
        $this->assertFalse($sequencer->getNext());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_direction_null($sequencer)
    {
        $sequencer->sequence(['id' => 5], 10, null, null);
        $this->assertFalse($sequencer->getPrev());
        $this->assertFalse($sequencer->getNext());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_getItems($sequencer)
    {
        $sequencer->sequence(['id' => 0], 3);
        $this->assertEquals([
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'c'],
        ], $sequencer->getItems());

        $sequencer->sequence(['id' => 3], 3);
        $this->assertEquals([
            ['id' => 4, 'name' => 'd'],
            ['id' => 5, 'name' => 'e'],
            ['id' => 6, 'name' => 'f'],
        ], $sequencer->getItems());

        $sequencer->sequence(['id' => 98], 3);
        $this->assertEquals([
            ['id' => 99, 'name' => 'cu'],
            ['id' => 100, 'name' => 'cv'],
        ], $sequencer->getItems());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_getItems_reverse($sequencer)
    {
        $sequencer->sequence(['id' => -1], 3);
        $this->assertEquals([
            ['id' => 4, 'name' => 'd'],
            ['id' => 3, 'name' => 'c'],
            ['id' => 2, 'name' => 'b'],
        ], $sequencer->getItems());

        $sequencer->sequence(['id' => -3], 3);
        $this->assertEquals([
            ['id' => 6, 'name' => 'f'],
            ['id' => 5, 'name' => 'e'],
            ['id' => 4, 'name' => 'd'],
        ], $sequencer->getItems());

        $sequencer->sequence(['id' => -98], 3);
        $this->assertEquals([
            ['id' => 100, 'name' => 'cv'],
            ['id' => 99, 'name' => 'cu'],
        ], $sequencer->getItems());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function test_continue($sequencer, $database)
    {
        // 全ページを舐めれば全件取得と同じになるはず
        $rows = [];
        $rows = array_merge($rows, $sequencer->sequence(['id' => 0], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10)->getItems());
        $this->assertEquals($database->selectArray('paging'), $rows);
        $this->assertFalse($sequencer->getNext());

        // 同上(逆順)
        $rows = [];
        $rows = array_merge($sequencer->sequence(['id' => -999], 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, true)->getItems(), $rows);
        $this->assertEquals($database->selectArray('paging'), $rows);
        $this->assertFalse($sequencer->getPrev());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function test_continue_reverse($sequencer, $database)
    {
        // 全ページを舐めれば全件取得と同じになるはず
        $rows = [];
        $rows = array_merge($rows, $sequencer->sequence(['id' => 0], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence($sequencer->getNext(), 10, false)->getItems());
        $this->assertEquals($database->selectArray('paging', [], ['id' => 'desc']), $rows);
        $this->assertFalse($sequencer->getNext());

        // 同上(逆順)
        $rows = [];
        // 降順で後ろから辿ろうとすると「-1以上の0ではない整数」を指定する必要がある。が、そんな整数はないので0.5でお茶を濁す
        $rows = array_merge($sequencer->sequence(['id' => -0.5], 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $rows = array_merge($sequencer->sequence($sequencer->getPrev(), 10, false)->getItems(), $rows);
        $this->assertEquals($database->selectArray('paging', [], ['id' => 'desc']), $rows);
        $this->assertFalse($sequencer->getPrev());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     */
    function test_getIterator($sequencer)
    {
        $sequencer->sequence(['id' => 0], 9);
        $this->assertInstanceOf('Iterator', $sequencer->getIterator());
        $this->assertCount(9, $sequencer->getIterator());
    }
}
