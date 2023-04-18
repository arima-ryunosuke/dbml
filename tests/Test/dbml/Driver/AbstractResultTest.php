<?php

namespace ryunosuke\Test\dbml\Driver;

use Doctrine\DBAL\Result;
use ryunosuke\dbml\Driver\AbstractResult;

class AbstractResultTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $driverResult = new class() extends AbstractResult {
            private $array = [
                ['1', 'hoge', 'A', 'x'],
                ['2', 'fuga', 'B', 'y'],
                ['3', 'piyo', 'C', 'z'],
            ];

            public function fetchNumeric()
            {
                $current = current($this->array);
                next($this->array);
                return $current;
            }

            public function fetchAssociative()
            {
                $current = $this->fetchNumeric();

                if ($this->groupByName) {
                    return $current ? $this->_fetchGroup(['id', 'name', 'id', 'id'], $current) : false;
                }

                return $current ? array_combine(['id', 'name', 'group', 'letter'], $current) : false;
            }

            public function rowCount(): int
            {
                return 3;
            }

            public function columnCount(): int
            {
                return 2;
            }

            public function free(): void
            {
                reset($this->array);
            }
        };
        $result = new Result($driverResult, self::getDummyDatabase()->getConnection());

        $result->free();
        $this->assertEquals([
            "1",
            "2",
            "3",
        ], $result->fetchFirstColumn());

        $result->free();
        $this->assertEquals([
            ["1", "hoge", 'A', 'x'],
            ["2", "fuga", 'B', 'y'],
            ["3", "piyo", 'C', 'z'],
        ], $result->fetchAllNumeric());

        $result->free();
        $this->assertEquals([
            ['id' => '1', 'name' => 'hoge', 'group' => 'A', 'letter' => 'x'],
            ['id' => '2', 'name' => 'fuga', 'group' => 'B', 'letter' => 'y'],
            ['id' => '3', 'name' => 'piyo', 'group' => 'C', 'letter' => 'z'],
        ], $result->fetchAllAssociative());

        $result->free();
        $this->assertEquals([
            1 => ['name' => 'hoge', 'group' => 'A', 'letter' => 'x'],
            2 => ['name' => 'fuga', 'group' => 'B', 'letter' => 'y'],
            3 => ['name' => 'piyo', 'group' => 'C', 'letter' => 'z'],
        ], $result->fetchAllAssociativeIndexed());

        $driverResult->groupByName();
        $result->free();
        $this->assertEquals([
            ['id' => ["1", "A", "x"], 'name' => 'hoge',],
            ['id' => ["2", "B", "y"], 'name' => 'fuga'],
            ['id' => ["3", "C", "z"], 'name' => 'piyo'],
        ], $result->fetchAllAssociative());

        $this->assertEquals(3, $result->rowCount());
        $this->assertEquals(2, $result->columnCount());
    }
}
