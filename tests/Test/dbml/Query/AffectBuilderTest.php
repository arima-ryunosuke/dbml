<?php

namespace ryunosuke\Test\dbml\Query;

use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Query\AffectBuilder;
use ryunosuke\dbml\Query\Expression\Expression;

class AffectBuilderTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideAffectBuilder()
    {
        return array_map(function ($v) {
            return [
                new AffectBuilder($v[0]),
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider provideAffectBuilder
     * @param AffectBuilder $builder
     */
    function test_build($builder)
    {
        $builder->build([
            'table'   => 'test(1) as T:PRIMARY.id,name',
            'set'     => ['name' => 'A', 'dummy' => 'D'],
            'merge'   => ['data' => 'D', '*' => null],
            'select'  => 'select id,name',
            'where'   => ['name' => 'hoge'],
            'groupBy' => ['key'],
            'having'  => ['group' => 'X'],
            'orderBy' => ['id' => false],
            'limit'   => 10,
        ]);

        $this->assertEquals('test', $builder->getTable());
        $this->assertEquals('T', $builder->getAlias());
        $this->assertEquals('PRIMARY', $builder->getConstraint());
        $this->assertEquals(['name' => 'A'], $builder->getSet());
        $this->assertEquals('D', $builder->getMerge()['data']);
        $this->assertEquals(['id', 'name'], $builder->getColumn());
        $this->assertEquals('select id,name', $builder->getSelect());
        $this->assertEquals([
            "name" => "hoge",
            new Expression('T.id = ?', [1]),
        ], $builder->getWhere());
        $this->assertEquals(['key'], $builder->getGroupBy());
        $this->assertEquals(['group' => 'X'], $builder->getHaving());
        $this->assertEquals(['id' => false], $builder->getOrderBy());
        $this->assertEquals(10, $builder->getLimit());
    }

    /**
     * @dataProvider provideAffectBuilder
     * @param AffectBuilder $builder
     */
    function test_method($builder)
    {
        $builder->insert('test', ['name' => 'A', 'dummy' => 'D']);
        $this->assertEquals('INSERT INTO test (name) VALUES (?)', (string) $builder);

        $builder->reset()->insert('test(1)', ['name' => 'A', 'dummy' => 'D']);
        $this->assertStringStartsWith('INSERT INTO test (name) SELECT ?', (string) $builder);
    }

    /**
     * @dataProvider provideAffectBuilder
     * @param AffectBuilder $builder
     */
    function test_normalize($builder)
    {
        $row = $builder->getDatabase()->Article->pk(1)->tuple();
        $this->assertSame($row->arrayize(), $builder->build(['table' => 't_article'])->normalize($row));

        $row->article_id = 99;
        $this->assertSame($row->arrayize(), $builder->build(['table' => 't_article'])->normalize($row));

        $row->title = 'newest';
        $this->assertSame($row->arrayize(), $builder->build(['table' => 't_article'])->normalize($row));

        $row = ['name' => (object) ['a' => 'A']];
        $this->assertSame([
            'name' => '{"a":"A"}',
        ], $builder->build(['table' => 'test'])->normalize($row));
    }

    /**
     * @dataProvider provideAffectBuilder
     * @param AffectBuilder $builder
     */
    function test_reset($builder)
    {
        $builder->build([
            'table'   => 'test as T',
            'where'   => ['date > ?' => "2014-12-24"],
            'groupBy' => ['group'],
            'orderBy' => ['date'],
            'limit'   => 10,
        ]);

        $this->assertEquals(new AffectBuilder($builder->getDatabase()), $builder->reset());
    }

    /**
     * @dataProvider provideAffectBuilder
     * @param AffectBuilder $builder
     */
    function test_misc($builder)
    {
        $builder->setInsertSet(true);
        $cache = that($builder->getDatabase())->var('cache');
        $this->finalize(fn() => $cache->offsetUnset('compatiblePlatform'));
        $cache['compatiblePlatform'] = new class($builder->getDatabase()->getPlatform(), $builder->getDatabase()->getCompatiblePlatform()->getVersion()) extends CompatiblePlatform {
            public function supportsCompatibleCharAndBinary(): bool { return false; }

            public function supportsIdentityNullable(): bool { return false; }

            public function supportsInsertSet(): bool { return true; }
        };

        // バイナリ型はバイナリになる
        $builder->reset()->build(['table' => 'misctype']);
        $this->assertInstanceOf(Expression::class, $builder->normalize(['cbinary' => 'x'])['cbinary']);

        // null なオートインクリメントは伏せられる
        $builder->reset()->build(['table' => 'test']);
        $this->assertEquals([], $builder->normalize(['id' => null]));

        // INSERT SET(insert)
        $builder->reset()->insert('test', ['name' => 'A', 'dummy' => 'D']);
        $this->assertEquals('INSERT INTO test SET name = ?', (string) $builder);

        // INSERT SET(modify)
        $builder->reset()->modify('test', ['name' => 'A', 'dummy' => 'D']);
        $this->assertStringStartsWith('INSERT INTO test SET name = ?', (string) $builder);
    }
}
