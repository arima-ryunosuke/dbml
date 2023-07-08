<?php

namespace ryunosuke\Test\dbml\Query\Pagination;

use ryunosuke\dbml\Query\Pagination\Paginator;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\Test\Database;

class PaginatorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function providePaginator()
    {
        return array_map(function ($v) {
            return [
                new Paginator((new QueryBuilder($v[0]))->column('paging')),
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     * @param Database $database
     */
    function test___construct($paginator, $database)
    {
        $builder = $database->select('paging');
        $paginator = new Paginator($builder);

        $ref = new \ReflectionProperty($paginator, 'builder');
        $ref->setAccessible(true);
        $this->assertSame($builder, $ref->getValue($paginator));
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     * @param Database $database
     */
    function test_paginate($paginator, $database)
    {
        $database->delete('paging', []);
        $paginator->paginate(2, 9, 5);
        $this->assertEquals(0, $paginator->getFirst());
        $this->assertEquals(0, $paginator->getLast());
        $this->assertEquals(0, $paginator->getTotal());
        $this->assertEquals(0, $paginator->getPageCount());
        $this->assertEquals([], $paginator->getPageRange());

        $this->assertException(new \InvalidArgumentException('must be positive number'), L($paginator)->paginate(2, 0, 5));
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getPage($paginator)
    {
        // ページ内なら指定したページのはず
        $paginator->paginate(2, 9);
        $this->assertEquals(2, $paginator->getPage());

        // ページ数以下なら最小ページのはず
        $paginator->paginate(-2, 9);
        $this->assertEquals(1, $paginator->getPage());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getFirst($paginator)
    {
        // 2ページ目の最初のインデックスは 10（=(2 - 1) * 9 + 1） のはず
        $paginator->paginate(2, 9);
        $this->assertEquals(10, $paginator->getFirst());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getLast($paginator)
    {
        // 2ページ目の最後のインデックスは 10（=(2 - 1) * 9 + 9） のはず
        $paginator->paginate(2, 9);
        $this->assertEquals(18, $paginator->getLast());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getTotal($paginator)
    {
        // 突っ込んだ数と等値のはず
        $paginator->paginate(2, 9);
        $this->assertEquals(100, $paginator->getTotal());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getPageRange($paginator)
    {
        // $shownpage を省略すれば全部のはず
        $paginator->paginate(2, 9);
        $this->assertSame(range(1, 12), $paginator->getPageRange());

        // $shownpage を指定すれば指定分のはず
        $paginator->paginate(2, 9, 5);
        $this->assertSame(range(1, 5), $paginator->getPageRange());
        $this->assertSame(range(1, 3), $paginator->getPageRange(3));

        // 1ページしか表示しないなら最初のページだけのはず
        $this->assertSame([2], $paginator->paginate(2, 9, 1)->getPageRange());
        $this->assertSame([3], $paginator->paginate(3, 9, 1)->getPageRange());
        $this->assertSame([4], $paginator->paginate(4, 9, 1)->getPageRange(1));
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getPageCount($paginator)
    {
        // paginate を呼んでいなければ0のはず
        $this->assertEquals(0, $paginator->getPageCount());

        // $shownpage に関わらず 12 ページ固定のはず
        $paginator->paginate(2, 9);
        $this->assertEquals(12, $paginator->getPageCount());

        // $shownpage に関わらず 12 ページ固定のはず
        $paginator->paginate(2, 9, 5);
        $this->assertEquals(12, $paginator->getPageCount());

        // 12 ページはセーフだが 13 ページは例外
        $this->assertEquals(12, $paginator->paginate(12, 9)->getPageCount());
        $this->assertException('too long', L($paginator->paginate(13, 9))->getPageCount());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_hasPrevNext($paginator)
    {
        $this->assertFalse($paginator->hasPrev());
        $this->assertFalse($paginator->hasNext());

        $paginator->paginate(1, 40);
        $this->assertFalse($paginator->hasPrev());
        $this->assertTrue($paginator->hasNext());

        $paginator->paginate(2, 40);
        $this->assertTrue($paginator->hasPrev());
        $this->assertTrue($paginator->hasNext());

        $paginator->paginate(3, 40);
        $this->assertTrue($paginator->hasPrev());
        $this->assertFalse($paginator->hasNext());
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     * @param Database $database
     */
    function test_continue($paginator, $database)
    {
        // 全ページを舐めれば全件取得と同じになるはず
        $rows = [];
        $rows = array_merge($rows, $paginator->paginate(1, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(2, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(3, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(4, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(5, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(6, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(7, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(8, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(9, 10)->getItems());
        $rows = array_merge($rows, $paginator->paginate(10, 10)->getItems());
        $this->assertEquals($database->selectArray('paging'), $rows);
    }

    /**
     * @dataProvider providePaginator
     * @param Paginator $paginator
     */
    function test_getIterator($paginator)
    {
        $paginator->paginate(2, 9);
        $this->assertInstanceOf('\ArrayIterator', $paginator->getIterator());
        $this->assertCount(9, $paginator->getIterator());
    }
}
