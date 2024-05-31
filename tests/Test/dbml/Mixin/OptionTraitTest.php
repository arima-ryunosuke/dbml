<?php

namespace ryunosuke\Test\dbml\Mixin;

use ryunosuke\dbml\Mixin\OptionTrait;

class OptionTraitTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    use OptionTrait;

    public static function getDefaultOptions(): array
    {
        return [
            'op1'       => 'val1',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ];
    }

    function setUp(): void
    {
        $this->setDefault(static::getDefaultOptions());
    }

    function test___callGetSet()
    {
        $this->assertEquals('hogera', $this->OptionTrait__callGetSet('setCamelCase', ['hogera'], $called)->OptionTrait__call('getCamelCase', [], $called));

        $this->OptionTrait__callGetSet('setCamelCase', ['hogera'], $called);
        $this->assertTrue($called);
        $this->OptionTrait__callGetSet('hoge', ['hogera'], $called);
        $this->assertFalse($called);

        $this->OptionTrait__callGetSet('getCamelCase', [], $called);
        $this->assertTrue($called);
        $this->OptionTrait__callGetSet('hoge', [], $called);
        $this->assertFalse($called);
    }

    function test___callOption()
    {
        $this->assertEquals('rageho', $this->OptionTrait__callOption('camelCase', ['rageho'], $called)->OptionTrait__callOption('camelCase', [], $called));

        $this->OptionTrait__callOption('camelCase', ['hogera'], $called);
        $this->assertTrue($called);
        $this->OptionTrait__callOption('hoge', ['hogera'], $called);
        $this->assertFalse($called);

        $this->OptionTrait__callOption('camelCase', [], $called);
        $this->assertTrue($called);
        $this->OptionTrait__callOption('hoge', [], $called);
        $this->assertFalse($called);
    }

    function test___call()
    {
        $this->assertEquals('rageho', $this->OptionTrait__call('camelCase', ['rageho'])->OptionTrait__call('getCamelCase', []));

        try {
            $this->OptionTrait__call('hoge', []);

            $this->fail();
        }
        catch (\Exception $ex) {
            $this->assertStringContainsString('does not exists', $ex->getMessage());
        }
    }

    function test_getDefaultOptions()
    {
        that(function () {
            $stub = new class {
                use OptionTrait;
            };
            $stub::getDefaultOptions();
        })()->wasThrown(new \DomainException());
    }

    function test_getOriginal()
    {
        $optioner = new OptionTest();
        $optioner1 = $optioner->context();
        $optioner2 = $optioner1->context();
        $optioner3 = $optioner2->context();

        $this->assertSame($optioner, $optioner->getOriginal());
        $this->assertSame($optioner, $optioner1->getOriginal());
        $this->assertSame($optioner, $optioner2->getOriginal());
        $this->assertSame($optioner, $optioner3->getOriginal());
    }

    function test_context()
    {
        // 元オプションを用意
        $optioner = new OptionTest();
        $optioner->setDefault();

        // 元オプションからコンテキストを生成すると・・・
        $context1 = $optioner->context();
        $context1->setOption('test', 1);
        // 設定した項目が伝播するはず
        $this->assertEquals(1, $context1->getOption('test'));
        $this->assertEquals(1, $optioner->getOption('test'));

        // 元オプション・コンテキストからコンテキストを生成すると・・・
        $context0 = $optioner->context();
        $context2 = $context1->context();
        $context2->setOption('test', 2);
        // 元にのみ伝播し、並列生成したコンテキストには伝播しないはず
        $this->assertEquals(1, $context0->getOption('test'));
        $this->assertEquals(2, $context1->getOption('test'));
        $this->assertEquals(2, $context2->getOption('test'));
        $this->assertEquals(2, $optioner->getOption('test'));
        unset($context0);

        unset($context2);
        // 伏せると値はもとに戻るはず
        $this->assertEquals(1, $context1->getOption('test'));
        $this->assertEquals(1, $optioner->getOption('test'));

        unset($context1);
        // 伏せると値はもとに戻るはず
        $this->assertEquals(0, $optioner->getOption('test'));
    }

    function test_stacking()
    {
        // 問題なく設定できる
        $this->stack();
        $this->setOption('op1', 'hoge');
        $this->assertEquals([
            'op1'       => 'hoge',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());

        // ネストしてもOKだし配列も渡せる
        $this->stack(['op2' => 'fuga', 'array' => []]);
        $this->assertEquals([
            'op1'       => 'hoge',
            'op2'       => 'fuga',
            'camelCase' => 'val3',
            'array'     => [],
        ], $this->getOptions());

        // 1つ前に戻るはず
        $this->unstack();
        $this->assertEquals([
            'op1'       => 'hoge',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());

        // 2つ前に戻るはず
        $this->unstack();
        $this->assertEquals([
            'op1'       => 'val1',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());
    }

    function test_stacking_misc()
    {
        $this->stack();
        $this->setOption('op1', 'hoge');
        $this->stack();
        $this->setUnsafeOption('hoge', 'fuga');

        // 全部戻るはず
        $this->unstackAll();
        $this->assertEquals([
            'op1'       => 'val1',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());

        // unstackAll は何回でも実行できる
        $this->assertSame($this, $this->unstackAll()->unstackAll()->unstackAll());

        // 例外が飛ぶはず
        that($this)->unstack()->wasThrown(new \UnexpectedValueException('empty'));
    }

    function test_setDefault()
    {
        $this->setDefault(['opX' => 'hoge', 'op1' => 'hoge']);
        $this->assertEquals([
            'op1'       => 'hoge',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());
    }

    function test_getsetOptions()
    {
        // デフォルトが返るはず
        $this->setOptions([]);
        $this->assertEquals([
            'op1'       => 'val1',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());

        // 変更されるのは op1 だけのはず
        $this->setOptions(['op1' => 'hoge']);
        $this->assertEquals([
            'op1'       => 'hoge',
            'op2'       => 'val2',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());

        // 余計なものは切り落とされるはず
        $this->setOptions(['opX' => 'hoge', 'op2' => 'fuga']);
        $this->assertEquals([
            'op1'       => 'hoge',
            'op2'       => 'fuga',
            'camelCase' => 'val3',
            'array'     => [
                'e1' => [
                    'ee1' => 'v1',
                ],
                'e2' => [
                    'ee2' => 'v2',
                ],
            ],
        ], $this->getOptions());
    }

    function test_getsetOption()
    {
        // 設定したものがそのまま返ってくるはず
        $this->setOption('op1', 'hoge');
        $this->assertEquals('hoge', $this->getOption('op1'));

        // 設定したものがそのまま返ってくるはず(ノーチェック)
        $this->setUnsafeOption('nocheck', 'hoge');
        $this->assertEquals('hoge', $this->getUnsafeOption('nocheck'));

        // 存在しないものはそれぞれ例外が投げられるはず
        that($this)->getOption('hoge')->wasThrown(new \InvalidArgumentException());
        that($this)->setOption('hoge', 'fuga')->wasThrown(new \InvalidArgumentException());
    }

    function test_storeOption()
    {
        $restore = $this->storeOptions(['op1' => 'hoge']);
        $this->assertEquals('hoge', $this->getOption('op1'));
        $restore();
        $this->assertEquals('val1', $this->getOption('op1'));
    }

    function test_mergeOption()
    {
        $this->assertEquals([
            'e1' => [
                'ee1' => 'vv1',
            ],
            'e2' => [
                'ee2' => 'v2',
                'ee3' => 'vv2',
            ],
        ], $this->mergeOption('array', [
            'e1' => [
                'ee1' => 'vv1',
            ],
            'e2' => [
                'ee3' => 'vv2',
            ],
        ])->option('array'));

        that($this)->mergeOption('hoge', [])->wasThrown(new \InvalidArgumentException());
        that($this)->mergeOption('op1', [])->wasThrown(new \InvalidArgumentException());
    }

    function test_option()
    {
        $this->assertEquals('hogera', $this->option('op1', 'hogera')->option('op1'));
    }
}

class OptionTest
{
    use OptionTrait;

    public static function getDefaultOptions(): array { return ['test' => 0]; }
}
