<?php

namespace ryunosuke\Test\dbml\Query\Pagination;

use ryunosuke\dbml\Query\Pagination\Sequencer;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\Test\Database;

class SequencerTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideSequencer()
    {
        return array_map(function ($v) {
            return [
                new Sequencer((new SelectBuilder($v[0]))->column('paging')),
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

        that($sequencer)->sequence([], 2)->wasThrown(new \InvalidArgumentException('length must be > 0'));

        that($sequencer)->sequence(['id' => 0], 0)->wasThrown(new \InvalidArgumentException('must be positive number'));
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
        ], 5);
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
     */
    function test_hasMore($sequencer)
    {
        $sequencer->sequence(['id' => 0], 10);
        $this->assertTrue($sequencer->hasMore());

        $sequencer->sequence(['id' => 1], 10);
        $this->assertTrue($sequencer->hasMore());

        $sequencer->sequence(['id' => 1], 100);
        $this->assertFalse($sequencer->hasMore());

        $sequencer->sequence(['id' => 1], 101);
        $this->assertFalse($sequencer->hasMore());
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
        $sequencer->sequence(['id' => 98], 3, false);
        $this->assertEquals([
            ['id' => 97, 'name' => 'cs'],
            ['id' => 96, 'name' => 'cr'],
            ['id' => 95, 'name' => 'cq'],
        ], $sequencer->getItems());

        $sequencer->sequence(['id' => 3], 3, false);
        $this->assertEquals([
            ['id' => 2, 'name' => 'b'],
            ['id' => 1, 'name' => 'a'],
        ], $sequencer->getItems());

        $sequencer->sequence(['id' => 1], 3, false);
        $this->assertEquals([], $sequencer->getItems());
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
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10)->getItems());
        $this->assertEquals($database->selectArray('paging'), $rows);
        $this->assertFalse($sequencer->hasMore());
    }

    /**
     * @dataProvider provideSequencer
     * @param Sequencer $sequencer
     * @param Database $database
     */
    function tesst_continue_reverse($sequencer, $database)
    {
        // 全ページを舐めれば全件取得と同じになるはず
        $rows = [];
        $rows = array_merge($rows, $sequencer->sequence(['id' => 999], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $rows = array_merge($rows, $sequencer->sequence(['id' => end($rows)['id']], 10, false)->getItems());
        $this->assertEquals($database->selectArray('paging', [], ['id' => 'desc']), $rows);
        $this->assertFalse($sequencer->hasMore());
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
