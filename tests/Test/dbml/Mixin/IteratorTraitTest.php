<?php

namespace ryunosuke\Test\dbml\Mixin;

use ryunosuke\dbml\Mixin\IteratorTrait;

class IteratorTraitTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public function test_all()
    {
        $it = new IteratorTest();
        $it->setProvider(['test' => []]);

        // 直接取得とイテレーション結果は合致する
        $this->assertEquals($it->getResult(), iterator_to_array($it->getIterator()));
        $this->assertEquals($it->count(), iterator_count($it->getIterator()));

        // 内部キャッシュでこの時点でもコール回数は1
        $this->assertEquals(1, $it->callcount);

        // リセットしても同じ
        $it->resetResult();
        $this->assertEquals($it->getResult(), iterator_to_array($it->getIterator()));
        $this->assertEquals($it->count(), iterator_count($it->getIterator()));

        // ただしコール回数は+1される
        $this->assertEquals(2, $it->callcount);

        $it->resetProvider();
        that($it)->getResult()->wasThrown('provider is not set');
    }

    function test_provider()
    {
        $it = new IteratorTest();

        $it->setProvider(['method' => [1, 2, 3]]);
        $this->assertEquals([1, 2, 3], $it->getResult());

        $it->setProvider('method');
        $this->assertEquals([null, null, null], $it->getResult());

        $it->setProvider(function () {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->method(7, 8, 9);
        });
        $this->assertEquals([7, 8, 9], $it->getResult());

        that($it)->setProvider(123)->wasThrown('is invalid');
    }

    function test_applay()
    {
        $it = new IteratorTest();

        $it->setProvider((function () {
            yield 1 => 'a';
            yield 2 => 'b';
            yield 3 => 'c';
        }));

        $it->apply(function ($v) {
            return strtoupper($v);
        });
        $it->apply(function ($v, $k, $n) {
            return "$n:$k:$v";
        });

        $this->assertEquals([
            1 => '0:1:A',
            2 => '1:2:B',
            3 => '2:3:C',
        ], $it->getResult());
    }
}

class IteratorTest implements \Countable
{
    use IteratorTrait;

    public $callcount = 0;

    public function test()
    {
        $this->callcount++;
        return [rand(0, 999), rand(0, 999), rand(0, 999)];
    }

    public function method($a = null, $b = null, $c = null)
    {
        return [$a, $b, $c];
    }
}
