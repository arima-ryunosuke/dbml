<?php

namespace ryunosuke\Test\dbml\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use ryunosuke\dbml\Entity\Entity;
use ryunosuke\dbml\Exception\NonSelectedException;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\dbml\Query\Expression\Alias;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\Expression\SelectOption;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\Test\Database;
use function ryunosuke\dbml\arrayval;
use function ryunosuke\dbml\try_return;

class QueryBuilderTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideQueryBuilder()
    {
        return array_map(function ($v) {
            return [
                new QueryBuilder($v[0]),
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider provideConnection
     * @param Connection $connection
     */
    function test_getDefaultOptions($connection)
    {
        $database = new Database($connection);
        $builder = new QueryBuilder($database);
        $options = $builder::getDefaultOptions();
        foreach ($options as $key => $dummy) {
            $this->assertSame($builder, $builder->{'set' . $key}($key));
        }
        foreach ($options as $key => $dummy) {
            $this->assertSame($key, $builder->{'get' . $key}());
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_fetchXXX($builder)
    {
        $this->assertEquals('a', $builder->select('name1')->from('test1')->limit(1)->value());

        $this->assertEquals('e', $builder->select('name1')->from('test1')->where('id = ?')->limit(1)->value([5]));

        $this->assertSame($builder, $builder->setLazyMode('eager')->valueOrThrow());

        $empty = $builder->reset()->from('test1')->where('id = -1');
        $this->assertException(new NonSelectedException("record is not found"), L($empty)->arrayOrThrow());
        $this->assertException(new NonSelectedException("record is not found"), L($empty)->assocOrThrow());
        $this->assertException(new NonSelectedException("record is not found"), L($empty)->listsOrThrow());
        $this->assertException(new NonSelectedException("record is not found"), L($empty)->pairsOrThrow());
        $this->assertException(new NonSelectedException("record is not found"), L($empty)->tupleOrThrow());
        $this->assertException(new NonSelectedException("record is not found"), L($empty)->valueOrThrow());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_yield($builder)
    {
        $builder->setArrayFetch(null);

        $yielder = $builder->reset()->select('name1')->from('test1')->limit(3)->yield();
        $this->assertInstanceOf(Yielder::class, $yielder);
        $this->assertEquals([
            ["name1" => "a"],
            ["name1" => "b"],
            ["name1" => "c"],
        ], iterator_to_array($yielder));

        $database = $builder->getDatabase();
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1name11']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'c1name12']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'c2name11']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 2, 'name' => 'c2name12']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 2, 'seq' => 1, 'name' => 'c1name21']);
        $database->insert('foreign_c1', ['id' => 2, 'seq' => 2, 'name' => 'c1name22']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 1, 'name' => 'c2name21']);
        $database->insert('foreign_c2', ['cid' => 2, 'seq' => 2, 'name' => 'c2name22']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 3, 'seq' => 1, 'name' => 'c1name31']);
        $database->insert('foreign_c1', ['id' => 3, 'seq' => 2, 'name' => 'c1name32']);
        $database->insert('foreign_c2', ['cid' => 3, 'seq' => 1, 'name' => 'c2name31']);
        $database->insert('foreign_c2', ['cid' => 3, 'seq' => 2, 'name' => 'c2name32']);

        $expected = [
            1 => [
                "id"         => "1",
                "name"       => "name1",
                "foreign_c1" => [
                    1 => [
                        "id"   => "1",
                        "seq"  => "1",
                        "name" => "c1name11",
                    ],
                    2 => [
                        "id"   => "1",
                        "seq"  => "2",
                        "name" => "c1name12",
                    ],
                ],
                "foreign_c2" => [
                    1 => [
                        "cid"  => "1",
                        "seq"  => "1",
                        "name" => "c2name11",
                    ],
                    2 => [
                        "cid"  => "1",
                        "seq"  => "2",
                        "name" => "c2name12",
                    ],
                ],
            ],
            2 => [
                "id"         => "2",
                "name"       => "name1",
                "foreign_c1" => [
                    1 => [
                        "id"   => "2",
                        "seq"  => "1",
                        "name" => "c1name21",
                    ],
                    2 => [
                        "id"   => "2",
                        "seq"  => "2",
                        "name" => "c1name22",
                    ],
                ],
                "foreign_c2" => [
                    1 => [
                        "cid"  => "2",
                        "seq"  => "1",
                        "name" => "c2name21",
                    ],
                    2 => [
                        "cid"  => "2",
                        "seq"  => "2",
                        "name" => "c2name22",
                    ],
                ],
            ],
            3 => [
                "id"         => "3",
                "name"       => "name1",
                "foreign_c1" => [
                    1 => [
                        "id"   => "3",
                        "seq"  => "1",
                        "name" => "c1name31",
                    ],
                    2 => [
                        "id"   => "3",
                        "seq"  => "2",
                        "name" => "c1name32",
                    ],
                ],
                "foreign_c2" => [
                    1 => [
                        "cid"  => "3",
                        "seq"  => "1",
                        "name" => "c2name31",
                    ],
                    2 => [
                        "cid"  => "3",
                        "seq"  => "2",
                        "name" => "c2name32",
                    ],
                ],
            ],
        ];
        $yielder = $builder->reset()->column([
            'foreign_p' => [
                '*',
                'foreign_c1' => ['*'],
                'foreign_c2' => ['*'],
            ],
        ])->yield(0, 'assoc');
        $this->assertEquals($expected, iterator_to_array($yielder));

        $yielder = $builder->reset()->column([
            'foreign_p' => [
                '*',
                'foreign_c1' => ['*'],
                'foreign_c2' => ['*'],
            ],
        ])->yield(2, 'assoc');
        $this->assertEquals($expected, iterator_to_array($yielder));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test___call($builder)
    {
        $builder->reset()->select('*')->from('test1');
        $this->assertEquals($builder->getDatabase()->selectArray('test1'), $builder->array());
        $this->assertEquals($builder->getDatabase()->selectValue('test1', [], [], 1), $builder->limit(1)->value());
        $this->assertEquals($builder->getDatabase()->selectTuple('test1', [], [], 1), $builder->limit(1)->tuple());

        $builder->reset()->select('*')->from('t_table1')->innerJoinOn('t_join1', ['hoge = fuga']);
        $this->assertEquals('SELECT * FROM t_table1 INNER JOIN t_join1 ON hoge = fuga', "$builder");

        $builder->reset()->select('*')->from('t_table1')->innerJoinOn('t_join1', 'hoge')->innerJoinOn('t_join2', 'fuga');
        $this->assertEquals('SELECT * FROM t_table1 INNER JOIN t_join1 ON hoge INNER JOIN t_join2 ON fuga', "$builder");

        $this->assertException(new \BadMethodCallException("is undefined"), [$builder, 'fetchHoge']);
        $this->assertException(new \BadMethodCallException("is undefined"), [$builder, 'joinHoge']);
    }

    function test___debugInfo()
    {
        $builder = new QueryBuilder(self::getDummyDatabase());
        $debugString = print_r($builder, true);
        $this->assertStringContainsString('sqlParts:', $debugString);
        $this->assertStringNotContainsString('database:', $debugString);
        $this->assertStringNotContainsString('__result:', $debugString);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_iterate($builder)
    {
        foreach ($builder->column('test#1.id, name') as $n => $row) {
            $this->assertEquals(0, $n);
            $this->assertEquals(['id' => '2', 'name' => 'b'], $row);
        }
        $this->assertCount(1, $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_cast($builder)
    {
        /** @var Entity $row */

        $this->assertEquals(null, $builder->cast('array')->getCaster());

        $row = call_user_func($builder->cast(Entity::class)->getCaster(), ['c' => 'v']);
        $this->assertInstanceOf(Entity::class, $row);
        $this->assertEquals(['c' => 'v'], $row->arrayize());

        $row = call_user_func($builder->cast(Entity::class)->getCaster(), ['c' => 'v']);
        $this->assertInstanceOf(Entity::class, $row);
        $this->assertEquals(['c' => 'v'], $row->arrayize());

        $row = call_user_func($builder->cast(function ($row) { return $row; })->getCaster(), ['c' => 'v']);
        $this->assertIsArray($row);
        $this->assertEquals(['c' => 'v'], $row);

        // default entity
        $row = call_user_func($builder->cast(null)->getCaster(), ['c' => 'v']);
        $this->assertInstanceOf(Entity::class, $row);

        // entity
        $builder->column('Article.**');
        $row = call_user_func($builder->cast(null)->getCaster(), ['c' => 'v']);
        $this->assertInstanceOf(\ryunosuke\Test\Entity\Article::class, $row);
        $subuilder = $builder->getSubbuilder('Comment');
        $row = call_user_func($subuilder->getCaster(), ['c' => 'v']);
        $this->assertInstanceOf(\ryunosuke\Test\Entity\Comment::class, $row);

        $this->assertException(new \InvalidArgumentException('is not exists'), L($builder)->cast('NotFound'));

        $this->assertException(new \InvalidArgumentException('must be implements'), L($builder)->cast('\stdClass'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_builder($builder)
    {
        $builder->build([
            'column'  => 'test',
            'where'   => 'id = 1',
            'orderBy' => 'id',
            'limit'   => 5,
            'groupBy' => 'id',
            'having'  => 'MIN(id) > 1',
        ]);

        $offset_limit = function ($offset, $limit, $order) use ($builder) {
            $offset_limit = $builder->getDatabase()->getPlatform()->modifyLimitQuery('', $limit, $offset);
            return trim($order ? $offset_limit : strtr($offset_limit, ['ORDER BY (SELECT 0)' => '']));
        };

        $this->assertQuery('SELECT test.* FROM test WHERE id = 1 GROUP BY id HAVING MIN(id) > 1 ORDER BY id ASC ' . $offset_limit(0, 5, false), $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_scope($builder)
    {
        $builder->scope('Article', 'scope1 scope2', 1);
        $this->assertQuery("SELECT NOW() FROM t_article Article WHERE Article.article_id IN (?)", $builder);
        $this->assertEquals([1], $builder->getParams());

        $builder->scope('t_comment', 'scope1 scope2', 2, 3);
        $this->assertQuery("SELECT NOW(), NOW() FROM t_article Article, t_comment WHERE (Article.article_id IN (?)) AND (t_comment.comment_id IN (?,?))", $builder);
        $this->assertEquals([1, 2, 3], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_with($builder)
    {
        $WITH = $builder->getDatabase()->getCompatiblePlatform()->getWithRecursiveSyntax();

        $builder->reset()->with('cte (col1, col2)', 'SELECT 1, 2 UNION ALL SELECT 3, 4');
        $this->assertQuery("$WITH cte (col1, col2) AS (SELECT 1, 2 UNION ALL SELECT 3, 4) SELECT C.* FROM cte C", $builder->column([
            'cte as C' => ['*'],
        ]));
        $builder->with('cte (col1, col2)', null);
        $this->assertQuery("SELECT C.* FROM cte C", $builder->column([
            'cte as C' => ['*'],
        ]));

        $builder->reset()
            ->with('cte1', 'SELECT 1 AS col1, 2 AS col2 UNION ALL SELECT 3, 4')
            ->with('cte2', 'SELECT 1 AS col1, 2 AS col2 UNION ALL SELECT 3, 4');
        $this->assertQuery("$WITH cte1 AS (SELECT 1 AS col1, 2 AS col2 UNION ALL SELECT 3, 4),cte2 AS (SELECT 1 AS col1, 2 AS col2 UNION ALL SELECT 3, 4) SELECT C1.*, C2.* FROM cte1 C1, cte2 C2", $builder->column([
            'cte1 as C1' => ['*'],
            'cte2 as C2' => ['*'],
        ]));

        $builder->reset()->with('qb', $builder->getDatabase()->select('test1', ['id > ?' => 0]));
        $this->assertQuery("$WITH qb AS (SELECT test1.* FROM test1 WHERE id > ?) SELECT Q.id AS qid, test2.* FROM qb Q INNER JOIN test2 ON test2.id = Q.id WHERE test2.id = ?", $builder->column([
            'qb Q'   => [
                'qid' => 'id',
            ],
            '+test2' => [
                '*',
                ['test2.id = Q.id'],
            ],
        ])->where(['test2.id' => 1]));
        $this->assertEquals([0, 1], $builder->getParams());

        $this->assertEquals([
            [
                'qid'   => 1,
                'id'    => 1,
                'name2' => 'A',
            ],
        ], $builder->array());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_selects($builder)
    {
        $builder->column('foreign_p + foreign_c1');
        $builder->addSelect(1, 2);
        $builder->addSelect('NOW1()', 'NOW2()');
        $builder->addSelect(new Alias('alias', 'actual'));
        $builder->addSelect(['now' => 'NOW()']);
        $this->assertStringIgnoreBreak("
SELECT foreign_p.*, foreign_c1.*,
1, 2,
NOW1(), NOW2(),
actual AS alias,
NOW() AS now
FROM foreign_p INNER JOIN foreign_c1 ON foreign_c1.id = foreign_p.id
", $builder);

        // add を付けなければ全て破棄される。ただし from, join は無事（addColumn との最大の違い）
        $builder->select(new Expression('hoge'));
        $this->assertQuery("SELECT hoge FROM foreign_p INNER JOIN foreign_c1 ON foreign_c1.id = foreign_p.id", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_from($builder)
    {
        $subuilder = clone $builder;
        $subuilder->reset()->column('test1')->where(['id' => 1]);

        // 普通に from
        $builder->reset()->select('*')->from($subuilder, 't');
        $this->assertQuery('SELECT * FROM (SELECT test1.* FROM test1 WHERE id = ?) t', $builder);
        $this->assertEquals([1], $builder->getParams());

        // innerJoinOn くらいはできる
        $builder->reset()->select('*')->from($subuilder, 't')->innerJoinOn('test2', 't.id = test2.id');
        $this->assertQuery('SELECT * FROM (SELECT test1.* FROM test1 WHERE id = ?) t INNER JOIN test2 ON t.id = test2.id', $builder);
        $this->assertEquals([1], $builder->getParams());

        // 配列だとエイリアス名になる
        $builder->reset()->select('*')->from(['AT' => $subuilder]);
        $this->assertQuery('SELECT * FROM (SELECT test1.* FROM test1 WHERE id = ?) AT', $builder);

        // エイリアス未指定だと自動命名される
        $builder->reset()->select('*')->from($subuilder);
        $this->assertQuery('SELECT * FROM (SELECT test1.* FROM test1 WHERE id = ?) __dbml_auto_from_0', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_from_on($builder)
    {
        $builder->reset()->from('table', 'T')->from('table1', 'T1', null, (object) ['jCol1' => 'fCol1', 'jCol2' => 'fCol2']);
        $this->assertQuery('SELECT * FROM table T, table1 T1 WHERE (T1.jCol1 = T.fCol1) AND (T1.jCol2 = T.fCol2)', $builder);

        $builder->reset()->from('table')->from('table1', 'T1', null, (object) ['ucol1', 'ucol2']);
        $this->assertQuery('SELECT * FROM table, table1 T1 WHERE (T1.ucol1 = table.ucol1) AND (T1.ucol2 = table.ucol2)', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column($builder)
    {
        $clone = function ($object) { return clone $object; };
        $this->assertQuery("SELECT test1.ccc FROM test1", $builder->column('test1.ccc'));
        $this->assertQuery("SELECT test1.id FROM test1", $builder->column('test1.!name1'));
        $this->assertQuery("SELECT test.id, SELECT NOW() AS now, (select 1) as hoge, (SELECT 1 FROM test WHERE a = ?) FROM test",
            $builder->column([
                'test' => [
                    'id',
                ],
                [
                    // @todo
                    'now' => 'SELECT NOW()',
                    new Expression('(select 1) as hoge'),
                    $clone($builder)
                        ->reset()
                        ->select('1')
                        ->from('test')
                        ->where(['a' => 1]),
                ],
            ])
        );
        $this->assertQuery("SELECT hogefuga (select 1) as hoge",
            $builder->column([
                new Expression('(select 1) as hoge'),
                new SelectOption('hogefuga'),
            ])
        );

        $this->assertException(
            new \UnexpectedValueException('some columns are not found'),
            [$builder, 'column'], 'test1.!hogera'
        );
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_ignore($builder)
    {
        $this->assertQuery("SELECT test.id FROM test", $builder->column('test.!name,!data'));
        $this->assertQuery("SELECT test.id FROM test", $builder->column(['test' => ['!name', '!data']]));
        $this->assertQuery("SELECT test.id, test.id * 2 AS id2 FROM test", $builder->column(['test' => ['!name', '!data', 'id2' => 'id * 2']]));
        $this->assertQuery("SELECT test.id * 2 AS id FROM test", $builder->column(['test' => ['!name', '!data', 'id' => 'id * 2']]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_string($builder)
    {
        $expected = "SELECT test1.*, test2.* FROM test1, test2";
        $this->assertQuery($expected, $builder->column('test1, test2'));

        $expected = "SELECT T1.name, T1.id AS id1, T2.id AS id2, T2.name2 FROM test1 T1, test2 T2";
        $this->assertQuery($expected, $builder->column('test1 T1.name, test2 T2.id id2, name2, T1.id id1'));

        $expected = "SELECT test.col1, test.col2 FROM test";
        $this->assertQuery($expected, $builder->column(['test' => ['col1, col2']]));

        $this->assertException('other schema', L($builder)->column('other.table.c1'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_expression($builder)
    {
        $builder->column([
            'test' => [
                'id',
                'mixed' => function ($row, $name = 'test.name', $data = 'data') { return "{$row['id']}-$name-$data"; },
            ],
        ]);
        $this->assertEquals('SELECT test.id, test.name AS __dbml_auto_column_dependtest_name, test.data AS __dbml_auto_column_dependtest_data, NULL AS mixed FROM test', (string) $builder);
        $this->assertEquals([
            'id'    => '1',
            'mixed' => '1-a-',
        ], $builder->limit(1)->tuple());

        $builder->reset()->column([
            'test1' => [
                'piyo'          => function ($row) { return 'xx-' . $row['name1']; },
                'name1'         => function ($v) { return 'ss_' . $v; },
                'id,name1 idn1' => function ($id, $name) { return "$id-$name"; },
                'idn2'          => function ($id = 'id', $name = 'name1') { return "$id-$name"; },
                'idn3'          => function ($row, $name = 'name1') { return "{$row['idn2']}-" . strtoupper($name); },
                'last'          => new Expression("'dbval'"),
            ],
        ]);
        $this->assertEquals([
            'name1' => 'ss_a',
            'piyo'  => 'xx-ss_a',
            'idn1'  => '1-a',
            'idn2'  => '1-a',
            'idn3'  => '1-a-A',
            'last'  => 'dbval',
        ], $builder->limit(1)->tuple());

        $builder->where('1=0');
        $this->assertEmpty($builder->tuple());

        $builder->reset()->column([
            'test1' => [
                'name1' => \Closure::fromCallable('strtoupper'),
            ],
        ]);
        $this->assertEquals([
            'name1' => 'A',
        ], $builder->limit(1)->tuple());

        $this->assertException('cannot be mixed', L($builder->reset())->column([
            'test1' => [
                'id,name idn' => function ($id, $name) { return "$id-$name"; },
            ],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_special($builder)
    {
        $builder->column([
            'test1' => [
                'i'  => 123,       // int 値はそのまま埋め込まれる
                'si' => '123',       // int 文字列はそのまま埋め込まれる
                'd'  => 123.456,   // float 値はそのまま埋め込まれる
                'sd' => '123.456', // float 文字列はそのまま埋め込まれる
                'n'  => 'Null',    // Null 文字列はそのまま埋め込まれる
                't'  => true,      // 真偽値はそのまま埋め込まれる
                'f'  => false,     // 真偽値はそのまま埋め込まれる
                'a,b',             // カンマ区切りはバラされる
                'GREATEST(1,2,3)', // カッコ＋カンマ区切りはカッコ優先
            ],
        ]);
        $this->assertStringIgnoreBreak('
SELECT
123 AS i,
123 AS si,
123.456 AS d,
123.456 AS sd,
NULL AS n,
1 AS t,
0 AS f,
test1.a, test1.b,
GREATEST(1,2,3) FROM test1', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_alias($builder)
    {
        $this->assertQuery("SELECT t1.col FROM test1 t1", $builder->column('test1 t1.col'));
        $this->assertQuery("SELECT t2.* FROM test2 t2", $builder->column(['test2 as t2']));
        $this->assertQuery('SELECT t1.id AS hoge FROM test1 t1', $builder->column(['test1 as t1' => 'id AS hoge']));
        $this->assertQuery('SELECT t1.id AS id1, t1.name AS nm1 FROM test1 t1', $builder->column([
            'test1  t1' => [
                'id as id1',
                'name as nm1',
            ],
        ]));
        $this->assertQuery('SELECT t2.id AS id2, t2.name AS nm2 FROM test2 t2', $builder->column([
            'test2 as t2' => [
                'id2' => 'id',
                'nm2' => 'name',
            ],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_entity($builder)
    {
        $this->assertQuery('SELECT Article.* FROM t_article Article WHERE Article.article_id = 1', $builder->column('Article')->where('Article.article_id = 1'));
        $this->assertQuery('SELECT A.* FROM t_article A WHERE A.article_id = 1', $builder->column('Article A')->where('A.article_id = 1'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_from($builder)
    {
        $this->assertQuery("SELECT test1.* FROM test1", $builder->column('test1'));

        $this->assertQuery('SELECT t.id AS hoge FROM test1 t', $builder->column([
            'test1 t' => 'id AS hoge',
        ])
        );
        $this->assertQuery('SELECT test1.id AS id1, test1.name AS nm1 FROM test1',
            $builder->column([
                'test1' => [
                    'id as id1',
                    'name as nm1',
                ],
            ])
        );
        $this->assertQuery('SELECT test2.id AS id2, test2.name AS nm2 FROM test2',
            $builder->column([
                'test2' => [
                    'id2' => 'id',
                    'nm2' => 'name',
                ],
            ])
        );
        $this->assertQuery("SELECT test1.*, test1.ccc FROM test1", $builder->column('test1.*, ccc'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_sub($builder)
    {
        $builder->reset()->column('t_article A.*, article_id/t_comment C.comment, t_comment.*');
        $this->assertEquals([
            'A.*',
            'A.article_id',
            Alias::forge(Database::AUTO_PRIMARY_KEY . "c", 'A.article_id'),
            Alias::forge("C", 'NULL'),
            Alias::forge(Database::AUTO_PRIMARY_KEY . "comment", 'A.article_id'),
            Alias::forge("Comment", 'NULL'),
        ], $builder->getQueryPart('select'));
        $this->assertEquals([
            Alias::forge(Database::AUTO_CHILD_KEY, 'C.comment_id'),
            'C.comment',
            Alias::forge(Database::AUTO_PARENT_KEY, 'C.article_id'),
        ], $builder->getSubbuilder('C')->getQueryPart('select'));
        $this->assertEquals([
            Alias::forge(Database::AUTO_CHILD_KEY, 't_comment.comment_id'),
            't_comment.*',
            Alias::forge(Database::AUTO_PARENT_KEY, 't_comment.article_id'),
        ], $builder->getSubbuilder('Comment')->getQueryPart('select'));

        $builder->reset()->column([
            't_comment' => [
                't_article' => [],
            ],
        ]);
        $this->assertEquals([
            Alias::forge(Database::AUTO_PARENT_KEY, 't_article.article_id'),
        ], $builder->getSubbuilder('Article')->getQueryPart('select'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_arrayFetch($builder)
    {
        $actual = $builder->reset()->setArrayFetch(Database::METHOD_ARRAY)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->tuple();
        $this->assertEquals([0, 1, 2], array_keys($actual['comments']));

        $actual = $builder->reset()->setArrayFetch(Database::METHOD_ASSOC)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->tuple();
        $this->assertEquals([3, 2, 1], array_keys($actual['comments']));

        $actual = $builder->reset()->setArrayFetch(Database::METHOD_LISTS)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->tuple();
        $this->assertEquals([3, 2, 1], $actual['comments']);

        $actual = $builder->reset()->setArrayFetch(Database::METHOD_PAIRS)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->tuple();
        $this->assertEquals([3 => 1, 2 => 1, 1 => 1], $actual['comments']);

        $actual = $builder->reset()->setArrayFetch(null)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->array();
        $this->assertEquals([0, 1, 2], array_keys($actual[0]['comments']));

        $actual = $builder->reset()->setArrayFetch(null)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->assoc();
        $this->assertEquals([3, 2, 1], array_keys($actual[1]['comments']));

        $actual = $builder->reset()->setArrayFetch(null)->column([
            't_article' => [
                't_comment as comments-comment_id' => [],
            ],
        ])->where(['' => 1])->tuple();
        $this->assertEquals([3, 2, 1], array_keys($actual['comments']));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_asterisk($builder)
    {
        self::createTables($builder->getDatabase()->getConnection(), [
            new Table('t_ancestor',
                [
                    new Column('ancestor_id', Type::getType('integer')),
                    new Column('ancestor_name', Type::getType('string'), ['length' => 32]),
                ],
                [new Index('PRIMARY', ['ancestor_id'], true, true)]
            ),
            new Table('t_ancestor2',
                [
                    new Column('ancestor_id', Type::getType('integer')),
                    new Column('ancestor_name2', Type::getType('string'), ['length' => 32]),
                ],
                [new Index('PRIMARY', ['ancestor_id'], true, true)],
                [],
                [new ForeignKeyConstraint(['ancestor_id'], 't_ancestor', ['ancestor_id'], 'fkey_asterisk0')]
            ),
            new Table('t_parent',
                [
                    new Column('parent_id', Type::getType('integer')),
                    new Column('parent_name', Type::getType('string'), ['length' => 32]),
                    new Column('f_ancestor_id', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['parent_id'], true, true)],
                [],
                [new ForeignKeyConstraint(['f_ancestor_id'], 't_ancestor', ['ancestor_id'], 'fkey_asterisk1')]
            ),
            new Table('t_child',
                [
                    new Column('child_id', Type::getType('integer')),
                    new Column('child_name', Type::getType('string'), ['length' => 32]),
                    new Column('f_parent_id', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['child_id'], true, true)],
                [],
                [new ForeignKeyConstraint(['f_parent_id'], 't_parent', ['parent_id'], 'fkey_asterisk2')]
            ),
            new Table('t_grand',
                [
                    new Column('f_child_id', Type::getType('integer')),
                    new Column('grand_id1', Type::getType('integer')),
                    new Column('grand_id2', Type::getType('integer')),
                    new Column('grand_name', Type::getType('string'), ['length' => 32]),
                ],
                [new Index('PRIMARY', ['f_child_id', 'grand_id1', 'grand_id2'], true, true)],
                [],
                [new ForeignKeyConstraint(['f_child_id'], 't_child', ['child_id'], 'fkey_asterisk3')]
            ),
            new Table('t_two',
                [
                    new Column('id', Type::getType('integer')),
                    new Column('f_child_id1', Type::getType('integer')),
                    new Column('f_child_id2', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['id'], true, true)],
                [],
                [
                    new ForeignKeyConstraint(['f_child_id1'], 't_child', ['child_id'], 'fkey_asterisk_two1'),
                    new ForeignKeyConstraint(['f_child_id2'], 't_child', ['child_id'], 'fkey_asterisk_two2'),
                ]
            ),
        ]);

        $builder->getDatabase()->getSchema()->refresh();

        $database = $builder->getDatabase();
        $database->insert('t_ancestor', ['ancestor_id' => 1, 'ancestor_name' => 'ancestor1']);
        $database->insert('t_ancestor2', ['ancestor_id' => 1, 'ancestor_name2' => 'ancestor21']);
        $database->insert('t_parent', ['parent_id' => 1, 'parent_name' => 'parent1', 'f_ancestor_id' => 1]);
        $database->insert('t_parent', ['parent_id' => 2, 'parent_name' => 'parent2', 'f_ancestor_id' => 1]);
        $database->insert('t_child', ['child_id' => 1, 'child_name' => 'child1', 'f_parent_id' => 1]);
        $database->insert('t_child', ['child_id' => 2, 'child_name' => 'child2', 'f_parent_id' => 1]);
        $database->insert('t_child', ['child_id' => 3, 'child_name' => 'child3', 'f_parent_id' => 2]);
        $database->insert('t_child', ['child_id' => 4, 'child_name' => 'child4', 'f_parent_id' => 2]);
        $database->insert('t_grand', ['grand_id1' => 1, 'grand_id2' => 1, 'grand_name' => 'grand1-1', 'f_child_id' => 1]);
        $database->insert('t_grand', ['grand_id1' => 1, 'grand_id2' => 2, 'grand_name' => 'grand1-2', 'f_child_id' => 1]);
        $database->insert('t_grand', ['grand_id1' => 1, 'grand_id2' => 3, 'grand_name' => 'grand1-3', 'f_child_id' => 4]);
        $database->insert('t_two', ['f_child_id1' => 1, 'f_child_id2' => 2, 'id' => 1]);

        // **
        $this->assertEquals($database->selectTuple([
            't_ancestor.*' => [
                't_parent.*' => [],
            ],
        ]), $database->selectTuple('t_ancestor.**'));

        // ***
        $this->assertEquals($database->selectTuple([
            't_ancestor.*' => [
                't_parent.*' => [
                    't_child.*' => [],
                ],
            ],
        ]), $database->selectTuple('t_ancestor.***'));

        // ****,pk
        $row = $database->selectTuple([
            't_ancestor' => [
                '****',
            ],
        ]);
        $this->assertEquals($database->selectTuple([
            't_ancestor.*' => [
                't_parent.*' => [
                    't_child.*' => [
                        't_grand.*'                  => [],
                        't_two:fkey_asterisk_two1.*' => [],
                        't_two:fkey_asterisk_two2.*' => [],
                    ],
                ],
            ],
        ]), $row);
        $this->assertArrayHasKey("1\x1F1", $row['t_parent'][1]['t_child'][1]['t_grand']);
        $this->assertArrayHasKey("1\x1F2", $row['t_parent'][1]['t_child'][1]['t_grand']);
        $this->assertArrayHasKey("1\x1F3", $row['t_parent'][2]['t_child'][4]['t_grand']);

        // **,null
        $row = $database->selectTuple([
            't_ancestor' => [
                '**',
                't_parent' => null,
            ],
        ]);
        $this->assertEquals([
            'ancestor_id'   => '1',
            'ancestor_name' => 'ancestor1',
        ], $row);

        // **,join
        $row = $database->selectTuple([
            't_ancestor'  => [
                '**',
                't_parent' => null,
            ],
            't_ancestor2' => '*',
        ]);
        $this->assertEquals([
            'ancestor_id'    => '1',
            'ancestor_name'  => 'ancestor1',
            'ancestor_name2' => 'ancestor21',
        ], $row);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_notable($builder)
    {
        $builder->column([
            'test' => '*',
            ''     => [
                'string'       => 'now()',
                'expression'   => new Expression('NOW()'),
                'builder'      => $builder->getDatabase()->select('test1.id'),
                'arrayeq'      => [
                    'id' => 123,
                ],
                'arrayin'      => [
                    'id' => [1, 2, 3],
                ],
                'arraylike'    => [
                    'id:like' => 'hoge',
                ],
                'arrayholder'  => [
                    'id > ?' => 123,
                ],
                'arraycomplex' => [
                    'A' => [41],
                    'B' => 42,
                    [
                        'C' => 43,
                        'D' => 44,
                    ],
                ],
                new Expression('NOALIAS_FUNC()'),
            ],
        ]);
        $this->assertStringContainsString('now() AS string', "$builder");
        $this->assertStringContainsString('NOW() AS expression', "$builder");
        $this->assertStringContainsString('(SELECT test1.id FROM test1) AS builder', "$builder");
        $this->assertStringContainsString('(id = ?) AS arrayeq', "$builder");
        $this->assertStringContainsString('(id IN (?,?,?)) AS arrayin', "$builder");
        $this->assertStringContainsString('(id LIKE ?) AS arraylike', "$builder");
        $this->assertStringContainsString('(id > ?) AS arrayholder', "$builder");
        $this->assertStringContainsString('((A IN (?)) AND (B = ?) AND ((C = ?) OR (D = ?))) AS arraycomplex', "$builder");
        $this->assertStringContainsString(', NOALIAS_FUNC()', "$builder");
        $this->assertEquals([123, 1, 2, 3, 'hoge', 123, 41, 42, 43, 44,], $builder->getParams());

        $context = $builder->getDatabase()->context(['notableAsColumn' => true]);
        $builder->column([
            'test'         => '*',
            'string'       => 'now()',
            'expression'   => new Expression('NOW()'),
            'builder'      => $context->select('test1.id'),
            'arrayeq'      => [
                'id' => 123,
            ],
            'arrayin'      => [
                'id' => [1, 2, 3],
            ],
            'arraylike'    => [
                'id:like' => 'hoge',
            ],
            'arrayholder'  => [
                'id > ?' => 123,
            ],
            'arraycomplex' => [
                'A' => [41],
                'B' => 42,
                [
                    'C' => 43,
                    'D' => 44,
                ],
            ],
            new Expression('NOALIAS_FUNC()'),
        ]);
        $this->assertStringContainsString('now() AS string', "$builder");
        $this->assertStringContainsString('NOW() AS expression', "$builder");
        $this->assertStringContainsString('(SELECT test1.id FROM test1) AS builder', "$builder");
        $this->assertStringContainsString('(id = ?) AS arrayeq', "$builder");
        $this->assertStringContainsString('(id IN (?,?,?)) AS arrayin', "$builder");
        $this->assertStringContainsString('(id LIKE ?) AS arraylike', "$builder");
        $this->assertStringContainsString('(id > ?) AS arrayholder', "$builder");
        $this->assertStringContainsString('((A IN (?)) AND (B = ?) AND ((C = ?) OR (D = ?))) AS arraycomplex', "$builder");
        $this->assertStringContainsString(', NOALIAS_FUNC()', "$builder");
        $this->assertEquals([123, 1, 2, 3, 'hoge', 123, 41, 42, 43, 44,], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_column_scope($builder, $database)
    {
        // シンプル
        $builder->column('Article@scope1@scope2(9)');
        $this->assertQuery("SELECT Article.*, NOW() FROM t_article Article WHERE Article.article_id = ?", $builder);
        $this->assertEquals([9], $builder->getParams());

        // デフォルトの適用
        $database->t_article->addScope('dddscope', ['title'], ['article_id > ?' => 0]);
        $builder->setDefaultScope(['dddscope']);

        $builder->column('Article(9)');
        $this->assertQuery("SELECT Article.*, Article.title FROM t_article Article WHERE (Article.article_id = ?) AND (Article.article_id > ?)", $builder);
        $this->assertEquals([9, 0], $builder->getParams());

        $builder->column('Article@scope2(9)');
        $this->assertQuery("SELECT Article.*, Article.title FROM t_article Article WHERE (Article.article_id > ?) AND (Article.article_id = ?)", $builder);
        $this->assertEquals([0, 9], $builder->getParams());

        $database->t_article->addScope('dddscope');
        $builder->setDefaultScope([]);

        // JOIN
        $builder->reset()->column([
            't_article@scope1@scope2(1) as A' => [
                '*',
                '<t_comment@scope1@scope2(2) as C' => [
                    '*',
                ],
            ],
        ]);
        $this->assertQuery("SELECT A.*, NOW(), C.*, NOW() FROM t_article A LEFT JOIN t_comment C ON (C.article_id = A.article_id) AND (C.comment_id = ?) WHERE A.article_id = ?", $builder);
        $this->assertEquals([2, 1], $builder->getParams());

        // sub
        $this->assertEquals([
            'article_id' => '1',
            'title'      => 'タイトルです',
            'checks'     => '',
            'delete_at'  => null,
            'C'          => [
                2 => [
                    'comment_id' => '2',
                    'article_id' => '1',
                    'comment'    => 'コメント2です',
                ],
            ],
        ], $builder->reset()->column([
            't_article@scope2(1) as A.*' => [
                't_comment@scope2(2) as C.*' => [],
            ],
        ])->tuple());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_primary($builder)
    {
        $builder->column('foreign_p(1, 2)');
        $this->assertQuery("SELECT foreign_p.* FROM foreign_p WHERE foreign_p.id IN (?, ?)", $builder);
        $this->assertEquals([1, 2], $builder->getParams());

        $builder->reset()->column('foreign_c1 ((1, 2), (3, 4))');
        if ($builder->getDatabase()->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertQuery("SELECT foreign_c1.* FROM foreign_c1 WHERE
(foreign_c1.id, foreign_c1.seq) IN ((?, ?), (?, ?))", $builder);
            $this->assertEquals([1, 2, 3, 4], $builder->getParams());
        }
        else {
            $this->assertQuery("SELECT foreign_c1.* FROM foreign_c1 WHERE
(foreign_c1.id = ? AND foreign_c1.seq = ?) OR
(foreign_c1.id = ? AND foreign_c1.seq = ?)", $builder);
            $this->assertEquals([1, 2, 3, 4], $builder->getParams());
        }

        $this->assertException('not match primary columns', L($builder->reset())->column('foreign_c1 (1, 2)'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_group($builder)
    {
        $builder->column('t_article<article_id>');
        $this->assertQuery("SELECT t_article.* FROM t_article GROUP BY t_article.article_id", $builder);

        $builder->column(['t_article A<article_id, title>']);
        $this->assertQuery("SELECT A.* FROM t_article A GROUP BY A.article_id, A.title", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_order($builder)
    {
        $builder->column('t_article -article_id');
        $this->assertQuery("SELECT t_article.* FROM t_article ORDER BY t_article.article_id DESC", $builder);

        $builder->column(['t_article -article_id +title A' => '*']);
        $this->assertQuery("SELECT A.* FROM t_article A ORDER BY A.article_id DESC, A.title ASC", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_range($builder)
    {
        $builder->column('t_article#40-60');
        $this->assertSame(40, $builder->getQueryPart('offset'));
        $this->assertSame(20, $builder->getQueryPart('limit'));

        $builder->column('t_article#-20');
        $this->assertSame(null, $builder->getQueryPart('offset'));
        $this->assertSame(20, $builder->getQueryPart('limit'));

        $builder->column('t_article#20');
        $this->assertSame(20, $builder->getQueryPart('offset'));
        $this->assertSame(1, $builder->getQueryPart('limit'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_virtual($builder)
    {
        $builder->column([
            't_article' => [
                't' => 'title2',
            ],
        ]);
        $this->assertQuery("SELECT UPPER(t_article.title) AS t FROM t_article", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_string($builder)
    {
        $builder->column([
            'foreign_p P' => [
                'id1' => '+foreign_c1 C1.id',
                'id2' => '<foreign_c2.cid',
            ],
        ]);
        $this->assertQuery("SELECT C1.id AS id1, foreign_c2.cid AS id2 FROM foreign_p P INNER JOIN foreign_c1 C1 ON C1.id = P.id LEFT JOIN foreign_c2 ON foreign_c2.cid = P.id", $builder);

        $builder->column([
            'foreign_p P' => [
                'id'  => '+foreign_c1 C1.id',
                'seq' => '+foreign_c1 C1.seq',
            ],
        ]);
        $this->assertQuery("SELECT C1.id, C1.seq FROM foreign_p P INNER JOIN foreign_c1 C1 ON C1.id = P.id", $builder);

        $builder->reset()->column([
            'foreign_s' => [
                'name1' => '+foreign_sc sc1:fk_sc1.name',
                'name2' => '+foreign_sc sc2:fk_sc2.name',
            ],
        ]);
        $this->assertQuery("SELECT sc1.name AS name1, sc2.name AS name2 FROM foreign_s INNER JOIN foreign_sc sc1 ON sc1.s_id1 = foreign_s.id INNER JOIN foreign_sc sc2 ON sc2.s_id2 = foreign_s.id", $builder);
        $this->assertException("must be table as alias", L($builder->reset())->column([
            'foreign_s' => [
                'name1' => '+foreign_sc:fk_sc1.name',
                'name2' => '+foreign_sc:fk_sc2.name',
            ],
        ]));

        $builder->reset()->column([
            'foreign_s' => [
                'name1' => '+foreign_sc sc1:[1].name',
                'name2' => '+foreign_sc sc2:[2].name',
            ],
        ]);
        $this->assertQuery("SELECT sc1.name AS name1, sc2.name AS name2 FROM foreign_s INNER JOIN foreign_sc sc1 ON 1 INNER JOIN foreign_sc sc2 ON 2", $builder);
        $this->assertException("must be table as alias", L($builder->reset())->column([
            'foreign_s' => [
                'name1' => '+foreign_sc:[1].name',
                'name2' => '+foreign_sc:[2].name',
            ],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_descripter($builder)
    {
        $builder->column([
            'foreign_p P' => [
                '+foreign_c1 C1.id as c1id',
                '<foreign_c2.cid as c2id',
            ],
        ]);
        $this->assertQuery("SELECT C1.id AS c1id, foreign_c2.cid AS c2id FROM foreign_p P INNER JOIN foreign_c1 C1 ON C1.id = P.id LEFT JOIN foreign_c2 ON foreign_c2.cid = P.id", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_builder($builder)
    {
        $builder->column([
            'test' => [
                '+sub[aaa=1, bbb = 2]' => $builder->getDatabase()->select('test1'),
            ],
        ]);
        $this->assertQuery("SELECT * FROM test INNER JOIN (SELECT test1.* FROM test1) sub ON (aaa=1) AND (bbb = 2)", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_auto($builder)
    {
        $builder->column([
            'foreign_p P' => [
                '+foreign_c1'     => [
                    ['1=1'],
                    'id',
                ],
                '<foreign_c2.cid' => [],
            ],
        ]);
        $this->assertQuery("SELECT foreign_c2.cid, foreign_c1.id FROM foreign_p P INNER JOIN foreign_c1 ON (foreign_c1.id = P.id) AND (1=1) LEFT JOIN foreign_c2 ON foreign_c2.cid = P.id", $builder);

        $builder->column([
            'foreign_p P' => [
                '+foreign_c1 C1.id'          => ['name1' => 'name'],
                '<foreign_c2 C2.cid as ccid' => 'name as name2',
            ],
        ]);
        $this->assertQuery("SELECT C1.id, C2.cid AS ccid, C1.name AS name1, C2.name AS name2 FROM foreign_p P INNER JOIN foreign_c1 C1 ON C1.id = P.id LEFT JOIN foreign_c2 C2 ON C2.cid = P.id", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_column_join_gateway($builder, $database)
    {
        $builder->column([
            'foreign_p P' => [
                '<c1' => $database->foreign_c1()->scoping('*', '1=1'),
            ],
        ]);
        $this->assertQuery("SELECT c1.* FROM foreign_p P LEFT JOIN foreign_c1 c1 ON (c1.id = P.id) AND (1=1)", $builder);

        $builder->column([
            'foreign_p P' => [
                '<c1.id' => $database->foreign_c1('name', ['1=1', '2=2']),
            ],
        ]);
        $this->assertQuery("SELECT c1.id, c1.name FROM foreign_p P LEFT JOIN foreign_c1 c1 ON (c1.id = P.id) AND (1=1) AND (2=2)", $builder);

        $builder->column([
            'foreign_p P' => [
                '<c1.id' => $database->foreign_c1()->scoping('name', ['a' => 1, ['b' => 2, 'c' => 3]]),
            ],
        ]);
        $this->assertQuery("SELECT c1.id, c1.name FROM foreign_p P LEFT JOIN foreign_c1 c1 ON (c1.id = P.id) AND (a = ?) AND ((b = ?) OR (c = ?))", $builder);
        $this->assertEquals([1, 2, 3], $builder->getParams());

        $builder->detectAutoOrder(true);
        $builder->column([
            'foreign_p P' => [
                '<c1' => $database->foreign_c1()->scoping('*', '1=1', ['id' => 'DESC']),
            ],
        ]);
        $this->assertQuery("SELECT c1.* FROM foreign_p P LEFT JOIN foreign_c1 c1 ON (c1.id = P.id) AND (1=1) ORDER BY P.id ASC, c1.id DESC", $builder);

        $builder->column([
            'foreign_p P' => [
                '<c1{col1: col1, col2: col2}' => $database->foreign_c1()->scoping('*', '1=1', ['id' => 'DESC'], [], 'c1.id'),
                '<c2[seq: 1]'                 => $database->foreign_c2()->scoping('*', '1=1', ['cid' => 'DESC'], [], 'c1.id'),
            ],
        ]);
        $builder->orderBy(['P.id' => 'DESC']);
        $this->assertStringIgnoreBreak("SELECT * FROM foreign_p P
LEFT JOIN (SELECT c1.* FROM foreign_c1 c1 WHERE 1=1 GROUP BY c1.id ORDER BY c1.id DESC) c1 ON (c1.col1 = P.col1) AND (c1.col2 = P.col2)
LEFT JOIN (SELECT c2.* FROM foreign_c2 c2 WHERE 1=1 GROUP BY c1.id ORDER BY c2.cid DESC) c2 ON seq = '1'
ORDER BY P.id DESC
", $builder->queryInto());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_on($builder)
    {
        $this->assertQuery("SELECT test1.* FROM test1, test2 WHERE test2.key1 = test1.key2",
            $builder->reset()->column([
                'test1',
                'test2' => [
                    ['test2.key1 = test1.key2'],
                ],
            ])
        );

        $this->assertQuery("SELECT test1.* FROM test1 LEFT JOIN test2 ON (test2.key1 = test1.key2) AND (flg = 1)",
            $builder->reset()->column([
                'test1',
                '<test2' => [
                    ['test2.key1 = test1.key2', 'flg = 1'],
                ],
            ])
        );
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_foreign($builder)
    {
        $builder->getDatabase()->getSchema()->refresh();

        $this->assertQuery("SELECT P.*, C.* FROM foreign_p P LEFT JOIN foreign_c1 C ON C.id = P.id",
            $builder->column([
                'foreign_p P',
                '<foreign_c1 C',
            ])
        );

        $this->assertQuery("SELECT foreign_p.*, foreign_c1.*, foreign_c2.* FROM foreign_p INNER JOIN foreign_c1 ON foreign_c1.id = foreign_p.id INNER JOIN foreign_c2 ON foreign_c2.cid = foreign_p.id",
            $builder->column([
                'foreign_p',
                '+foreign_c1',
                '+foreign_c2',
            ])
        );

        $this->assertQuery("SELECT P.*, C1.seq, C1.name FROM foreign_p P INNER JOIN foreign_c1 C1 ON C1.id = P.id INNER JOIN foreign_c2 C2 ON (C2.cid = P.id) AND (con)",
            $builder->column([
                'foreign_p P.*' => [
                    '+foreign_c1 C1' => '!id',
                    '+foreign_c2 C2' => [
                        ['con'],
                    ],
                ],
            ])
        );

        $this->assertQuery("SELECT P.*, C.* FROM foreign_p P INNER JOIN foreign_c1 C ON C.id = P.id",
            $builder->column('foreign_p P+foreign_c1 C')
        );

        $this->assertQuery("SELECT P.*, C.* FROM foreign_p P LEFT JOIN foreign_c1 C ON C.id = P.id",
            $builder->column('foreign_p P<foreign_c1 C')
        );

        $this->assertQuery("SELECT P.*, C.* FROM foreign_p P RIGHT JOIN foreign_c1 C ON C.id = P.id",
            $builder->column('foreign_p P>foreign_c1 C')
        );

        $this->assertQuery("SELECT P.*, C.* FROM foreign_p P CROSS JOIN foreign_c1 C ON C.id = P.id",
            $builder->column('foreign_p P*foreign_c1 C')
        );
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_column_join_condition($builder)
    {
        $this->assertQuery("SELECT * FROM
test
INNER JOIN test1 T1 ON (T1.id = test.id) AND (T1.cond = test.cond)
LEFT JOIN test2 T2 ON (flg1 = 1 AND flg2 = 2) AND (T2.cond = test.cond),
foreign_p
LEFT JOIN foreign_c1 C1 ON (C1.id = foreign_p.id) AND (condition = 1)
INNER JOIN foreign_c2 ON (foreign_c2.cid = foreign_p.id) AND (condition = 2)",
            $builder->column([
                'test'      => [
                    '+test1{id: id} T1'                => [
                        ['T1.cond = test.cond'],
                    ],
                    '<test2[flg1 = 1 AND flg2 = 2] T2' => [
                        ['T2.cond = test.cond'],
                    ],
                ],
                'foreign_p' => [
                    '~foreign_c1:fk_parentchild1 C1' => [
                        ['condition = 1'],
                    ],
                    '+foreign_c2:fk_parentchild2'    => [
                        ['condition = 2'],
                    ],
                ],
            ])
        );
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_column_join_subcondition($builder, $database)
    {
        $qi = function ($str) use ($database) {
            return $database->getPlatform()->quoteSingleIdentifier($str);
        };

        $this->assertQuery("SELECT * FROM foreign_p P
INNER JOIN foreign_c1 C1 ON
(EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = P.id))
INNER JOIN foreign_c2 C2 ON
C2.cid IN (SELECT MAX(foreign_c2.cid) AS {$qi('foreign_c2.cid@max')} FROM foreign_c2 WHERE foreign_c2.cid = P.id)
WHERE
((EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = P.id)))
AND
(C2.cid IN (SELECT MAX(foreign_c2.cid) AS {$qi('foreign_c2.cid@max')} FROM foreign_c2 WHERE foreign_c2.cid = P.id))
",
            $builder->column([
                'foreign_p P' => [
                    '+foreign_c1 C1:' => [
                        [
                            $database->subexists('foreign_c1'),
                        ],
                    ],
                    '+foreign_c2 C2:' => [
                        [
                            'C2.cid' => $database->submax('foreign_c2.cid'),
                        ],
                    ],
                ],
            ])->where([
                $database->subexists('foreign_c1'),
                'C2.cid' => $database->submax('foreign_c2.cid'),
            ])
        );

        $this->assertQuery("SELECT * FROM foreign_p P
INNER JOIN foreign_c1 C1 ON
(EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = P.id))
INNER JOIN foreign_c2 C2 ON
C2.cid IN (SELECT MAX(foreign_c2.cid) AS {$qi('foreign_c2.cid@max')} FROM foreign_c2 WHERE foreign_c2.cid = P.id)
WHERE
(C2.cid IN (SELECT MAX(foreign_c2.cid) AS {$qi('foreign_c2.cid@max')} FROM foreign_c2 WHERE foreign_c2.cid = P.id))
AND
((EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = P.id)))
",
            $builder->column([
                'foreign_p P' => [
                    '+foreign_c1 C1:' => [
                        [
                            'P' => $database->subexists('foreign_c1'),
                        ],
                    ],
                    '+foreign_c2 C2:' => [
                        [
                            'C2.cid|P' => $database->submax('foreign_c2.cid'),
                        ],
                    ],
                ],
            ])->where([
                'P'        => $database->subexists('foreign_c1'),
                'C2.cid|P' => $database->submax('foreign_c2.cid'),
            ])
        );

        $this->assertQuery("SELECT * FROM foreign_s S
INNER JOIN foreign_sc SC1 ON
(EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id1 = S.id))
INNER JOIN foreign_sc SC2 ON
SC2.id IN (SELECT MAX(foreign_sc.id) AS {$qi('foreign_sc.id@max')} FROM foreign_sc WHERE foreign_sc.s_id2 = S.id)
WHERE
(SC2.id IN (SELECT MAX(foreign_sc.id) AS {$qi('foreign_sc.id@max')} FROM foreign_sc WHERE foreign_sc.s_id2 = S.id))
AND
((EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id1 = S.id)))
",
            $builder->column([
                'foreign_s S' => [
                    '+foreign_sc SC1:' => [
                        [
                            'S:fk_sc1' => $database->subexists('foreign_sc'),
                        ],
                    ],
                    '+foreign_sc SC2:' => [
                        [
                            'SC2.id|S:fk_sc2' => $database->submax('foreign_sc.id'),
                        ],
                    ],
                ],
            ])->where([
                'S:fk_sc1'        => $database->subexists('foreign_sc'),
                'SC2.id|S:fk_sc2' => $database->submax('foreign_sc.id'),
            ])
        );
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_unselect($builder)
    {
        $builder->column([
            't_article' => [
                'dummy1'             => 123,
                'dummy2'             => 456,
                'article_id',
                'id'                 => 'article_id',
                'title'              => 'UPPER(title)',
                'callback'           => function ($row) {
                    $this->fail('never call');
                },
                't_comment comments' => [
                    '*',
                ],
                'comment_count',
            ],
        ])->where(['article_id' => 1]);

        $builder->unselect(0);

        $builder->unselect(0, 'article_id', 'title', 'callback', 'comments', 'comment_count');
        $this->assertStringNotContainsString('dummy1', (string) $builder);        // by 0
        $this->assertStringNotContainsString('dummy2', (string) $builder);        // by 0 (reseq)
        $this->assertStringContainsString('article_id', (string) $builder);       // no match
        $this->assertStringContainsString('AS id', (string) $builder);            // no specified
        $this->assertStringNotContainsString('title', (string) $builder);         // match
        $this->assertStringNotContainsString('callback', (string) $builder);      // match (callbacks)
        $this->assertStringNotContainsString('comments', (string) $builder);      // match (subbuilder)
        $this->assertStringNotContainsString('comment_count', (string) $builder); // match (virtual column)
        $this->assertEquals([
            'article_id' => 1,
            'id'         => 1,
        ], $builder->tuple());

        $builder->unselect(function ($select) {
            return $select instanceof Alias && $select->getAlias() === 'id';
        });
        $this->assertStringContainsString('article_id', (string) $builder);
        $this->assertStringNotContainsString('AS id', (string) $builder);
        $this->assertEquals([
            'article_id' => 1,
        ], $builder->tuple());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres($builder)
    {
        $builder->column('test');

        $this->assertQuery('SELECT test.* FROM test WHERE id = 1', $builder->where('id = 1'));
        $this->assertQuery('SELECT test.* FROM test WHERE (id = 1) AND (seq = 1)', $builder->where(['id = 1', 'seq = 1']));
        $this->assertQuery('SELECT test.* FROM test WHERE (id = 1) OR (seq = 1)', $builder->where('id = 1', 'seq = 1'));
        $this->assertQuery('SELECT test.* FROM test WHERE ((id = 1) AND (seq = 1)) OR ((id = 2) AND (seq = 2))', $builder->where(
            ['id = 1', 'seq = 1'],
            ['id = 2', 'seq = 2']
        ));

        $this->assertQuery('SELECT test.* FROM test WHERE id = ?', $builder->where(['id' => 1]));
        $this->assertEquals([1], $builder->getParams());

        $this->assertQuery("SELECT test.* FROM test WHERE (id = ?) AND (seq = ?)", $builder->where(['id = ?' => 1, 'seq = ?' => 2]));
        $this->assertEquals([1, 2], $builder->getParams());

        $this->assertQuery("SELECT test.* FROM test WHERE (id1 = 0) AND (id2 = ?) AND (id3 IN (?,?)) AND (id4 = ?) AND (id5 IN (?,?))", $builder->where(
            [
                'id1 = 0',
                'id2'        => 1,
                'id3'        => [2, 3],
                'id4 = ?'    => 4,
                'id5 IN (?)' => [5, 6],
            ]
        ));
        $this->assertEquals([1, 2, 3, 4, 5, 6], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_notWheres($builder)
    {
        $builder->column('test');

        $this->assertQuery('SELECT test.* FROM test WHERE NOT (id = 1)', $builder->notWhere('id = 1'));
        $this->assertQuery('SELECT test.* FROM test WHERE NOT ((id = 1) AND (seq = 1))', $builder->notWhere(['id = 1', 'seq = 1']));
        $this->assertQuery('SELECT test.* FROM test WHERE NOT ((id = 1) OR (seq = 1))', $builder->notWhere('id = 1', 'seq = 1'));
        $this->assertQuery('SELECT test.* FROM test WHERE NOT (((id = 1) AND (seq = 1)) OR ((id = 2) AND (seq = 2)))', $builder->notWhere(
            ['id = 1', 'seq = 1'],
            ['id = 2', 'seq = 2']
        ));

        $this->assertQuery("SELECT test.* FROM test WHERE NOT ((and_cond = ?) AND ((0) OR ((or_cond1 = ?) AND (or_cond2 = ?)) OR ((or_cond1 = ?) AND (or_cond2 = ?))))", $builder->notWhere([
            'and_cond' => 1,
            [
                '1' => '0',
                [
                    'or_cond1' => 1,
                    'or_cond2' => 2,
                ],
                [
                    'or_cond1' => 3,
                    'or_cond2' => 4,
                ],
            ],
        ]));
        $this->assertEquals([1, 1, 2, 3, 4], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_andNotWheres($builder)
    {
        $builder->column('test');

        $builder->andNotWhere('id = 1');
        $builder->andNotWhere(['id = 1', 'seq = 1']);
        $builder->andNotWhere('id = 1', 'seq = 1');
        $this->assertQuery('SELECT test.* FROM test WHERE (NOT (id = 1)) AND (NOT ((id = 1) AND (seq = 1))) AND (NOT ((id = 1) OR (seq = 1)))', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orWheres($builder)
    {
        $builder->column('test');

        $builder->orWhere('id = 1');
        $builder->orWhere(['id = 1', 'seq = 1']);
        $builder->orWhere('id = 1', 'seq = 1');
        $this->assertQuery('SELECT test.* FROM test WHERE (id = 1) OR ((id = 1) AND (seq = 1)) OR ((id = 1) OR (seq = 1))', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orNotWheres($builder)
    {
        $builder->column('test');

        $builder->orNotWhere('id = 1');
        $builder->orNotWhere(['id = 1', 'seq = 1']);
        $builder->orNotWhere('id = 1', 'seq = 1');
        $this->assertQuery('SELECT test.* FROM test WHERE (NOT (id = 1)) OR (NOT ((id = 1) AND (seq = 1))) OR (NOT ((id = 1) OR (seq = 1)))', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_endWheres($builder)
    {
        $builder->column('test');

        $builder->where('a');
        $builder->orWhere('b');
        //$builder->endWhere();
        $builder->andWhere('c');
        $this->assertQuery('SELECT test.* FROM test WHERE (a) OR (b) AND (c)', $builder);

        $builder->column('test');

        $builder->where('a');
        $builder->orWhere('b');
        $builder->endWhere();
        $builder->andWhere('c');
        $this->assertQuery('SELECT test.* FROM test WHERE ((a) OR (b)) AND (c)', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_primary($builder)
    {
        // 単一主キー
        $builder->reset()->column('test');
        $this->assertEquals("SELECT test.* FROM test", $builder->where(['' => []])->queryInto());
        $this->assertEquals("SELECT test.* FROM test WHERE test.id = '1'", $builder->where(['' => 1])->queryInto());
        $this->assertEquals("SELECT test.* FROM test WHERE test.id = '2'", $builder->where(['' => [2]])->queryInto());
        $this->assertEquals("SELECT test.* FROM test WHERE test.id IN ('1', '2')", $builder->where(['' => [1, 2]])->queryInto());

        // 複合主キー
        $builder->reset()->column('multiprimary');
        $this->assertEquals("SELECT multiprimary.* FROM multiprimary", $builder->where(['' => []])->queryInto());
        $this->assertEquals("SELECT multiprimary.* FROM multiprimary WHERE (multiprimary.mainid = '1' AND multiprimary.subid = '1')", $builder->where(['' => [1, 1]])->queryInto());
        if ($builder->getDatabase()->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertEquals("SELECT multiprimary.* FROM multiprimary WHERE (multiprimary.mainid, multiprimary.subid) IN (('1', '1'), ('1', '2'))", $builder->where(['' => [[1, 1], [1, 2]]])->queryInto());
        }
        else {
            $this->assertEquals("SELECT multiprimary.* FROM multiprimary WHERE (multiprimary.mainid = '1' AND multiprimary.subid = '1') OR (multiprimary.mainid = '1' AND multiprimary.subid = '2')", $builder->where(['' => [[1, 1], [1, 2]]])->queryInto());
        }

        // エラー
        $this->assertException("base table not found", L($builder->reset())->where(['' => [[1]]]));
        $this->assertException("not match primary columns", L($builder->reset()->column('test'))->where(['' => [[1, 2]]]));
        $this->assertException("not match primary columns", L($builder->reset()->column('multiprimary'))->where(['' => [[1]]]));
        $this->assertException("not match primary columns", L($builder->reset()->column('multiprimary'))->where(['' => [[1, 2, 3]]]));

        // トップレベル以外無視。ただし、OR などの数値キーは OK
        $this->assertEquals("SELECT test.* FROM test WHERE (FALSE) AND (test.id = '3')", $builder->reset()->column('test')->where([
            'hoge' => [
                '' => [1, 2],
            ],
            [
                '' => 3,
            ],
        ])->queryInto());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_anytable($builder)
    {
        // クエリビルダは無視されるはずなのでその検証用
        $builder->from($builder->getDatabase()->select('t_article T'));
        $builder->addColumn('t_article A+t_comment C')->where([
            'A.article_id' => 9,
        ]);

        // *.* で JOIN されているすべてのテーブル
        $t = clone $builder;
        $t->andWhere([
            '*.*' => 'hoge',
        ]);
        $this->assertEquals([
            '0'    => 'A.article_id = ?',
            'AND1' => '(/* anywhere */ A.title collate utf8_bin LIKE ?) OR (/* anywhere */ C.comment LIKE ?)',
        ], $t->getQueryPart('where'));
        $this->assertEquals([9, '%hoge%', '%hoge%'], $t->getParams());

        // * も同じ
        $t = clone $builder;
        $t->andWhere([
            '*' => 'hoge',
        ]);
        $this->assertEquals([
            '0'    => 'A.article_id = ?',
            'AND1' => '(/* anywhere */ A.title collate utf8_bin LIKE ?) OR (/* anywhere */ C.comment LIKE ?)',
        ], $t->getQueryPart('where'));
        $this->assertEquals([9, '%hoge%', '%hoge%'], $t->getParams());

        // A.* で A だけ
        $t = clone $builder;
        $t->andWhere([
            'A.*' => 'hoge',
        ]);
        $this->assertEquals([
            '0'    => 'A.article_id = ?',
            'AND1' => '/* anywhere */ A.title collate utf8_bin LIKE ?',
        ], $t->getQueryPart('where'));
        $this->assertEquals([9, '%hoge%'], $t->getParams());

        // [[]] でも AND にはならない
        $t = clone $builder;
        $t->andWhere([
            [
                'A.*' => 'hoge',
                'C.*' => 'hoge',
            ],
        ]);
        $this->assertEquals([
            '0'    => 'A.article_id = ?',
            'AND1' => '(/* anywhere */ A.title collate utf8_bin LIKE ?) OR (/* anywhere */ C.comment LIKE ?)',
        ], $t->getQueryPart('where'));
        $this->assertEquals([9, '%hoge%', '%hoge%'], $t->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_anycolumn($builder)
    {
        // クエリビルダは無視されるはずなのでその検証用
        $builder->from($builder->getDatabase()->select('t_article T'));
        $builder->addColumn('t_article A/t_comment C')->where([
            '*.article_id' => [1, 2, 3],
            '*.title'      => 'hoge',
            '*.comment'    => 'fuga',
            'A.article_id' => 9,
        ]);

        // A は後ろにある ['A.article_id' => 0] が優先され、*.title が活きるはず
        $t = $builder;
        $this->assertEquals([
            '(A.article_id = ?) AND (A.title = ?)',
        ], $t->getQueryPart('where'));
        $this->assertEquals([9, 'hoge'], $t->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_wheres_vcolumn($builder, $database)
    {
        $database->overrideColumns([
            'foreign_p' => [
                'raw1'        => [
                    'select'   => 'UPPER(%s.name)',
                    'implicit' => true,
                ],
                'raw2'        => [
                    'select'   => $database->raw('id + 9 = ?', 10),
                    'implicit' => true,
                ],
                'count_child' => [
                    'select'   => $database->foreign_c1->as('C1')->subcount('*', ['id' => 0]),
                    'implicit' => true,
                ],
                'has_child'   => [
                    'select'   => $database->foreign_c2->as('C2')->subexists('*', ['cid' => 0]),
                    'implicit' => true,
                ],
                'children'    => [
                    'select'   => $database->subselect('foreign_c2'),
                    'implicit' => true,
                ],
                'nam'         => 'NOW()',
            ],
        ]);

        $builder->column('foreign_p P')->where([
            'P.dummy = 1',
            'P.dummy'                                                      => 1,
            'P.name = 1',
            'P.name'                                                       => 1,
            'P.raw1',
            'P.raw1:%LIKE%'                                                => 'X',
            '!P.raw1:%LIKE'                                                => 'X',
            '!P.raw1:LIKE%'                                                => '',
            'P.raw2',
            '!P.raw1'                                                      => [],
            'P.count_child'                                                => 0,
            'P.count_child > ?'                                            => 1,
            'P.count_child BETWEEN ? AND ?'                                => [7, 9],
            '!P.count_child'                                               => [],
            '!P.count_child > ?'                                           => [123],
            'P.has_child',
            'P.has_child'                                                  => 0,
            'P.has_child IN (?)'                                           => [[0, 1]],
            'P.children'                                                   => ['name' => 'hoge'],
            '? AND P.raw1 = ? AND P.count_child = ? AND P.has_child IN(?)' => [99, 'Y', 2, [7, 8, 9]],
        ]);

        $qi = function ($str) use ($database) {
            return $database->getPlatform()->quoteSingleIdentifier($str);
        };
        $this->assertStringIgnoreBreak(<<<SQL
SELECT P.* FROM foreign_p P WHERE
(P.dummy = 1) AND (P.dummy = '1') AND (P.name = 1) AND (P.name = '1')
AND (/* vcolumn raw1-2 */ UPPER(P.name))
AND (/* vcolumn raw1-k */ UPPER(P.name) LIKE '%X%')
AND (/* vcolumn raw1-k */ UPPER(P.name) LIKE '%X')
AND (/* vcolumn raw2-3 */ id + 9 = '10')
AND (/* vcolumn count_child-k */ (SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) IN ('0'))
AND (/* vcolumn count_child-k */ (SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) > '1')
AND (/* vcolumn count_child-k */ (SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) BETWEEN '7' AND '9')
AND (/* vcolumn count_child-k */ (SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) > '123')
AND (/* vcolumn has_child-4 */ (EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid = '0') AND (C2.cid = P.id))))
AND (/* vcolumn has_child-k */ (EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid = '0') AND (C2.cid = P.id))) IN ('0'))
AND (/* vcolumn has_child-k */ (EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid = '0') AND (C2.cid = P.id))) IN ('0','1'))
AND (/* vcolumn children-k */ (EXISTS (SELECT * FROM foreign_c2 WHERE (foreign_c2.cid = P.id) AND (name = 'hoge'))) IN (1))
AND ('99' AND /* vcolumn raw1-k */ UPPER(P.name) = 'Y'
AND /* vcolumn count_child-k */ (SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) = '2'
AND /* vcolumn has_child-k */ (EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid = '0') AND (C2.cid = P.id))) IN('7','8','9'))
SQL
            , $builder->queryInto());

        $database->overrideColumns([
            'foreign_p' => [
                'raw1'        => null,
                'raw2'        => null,
                'count_child' => null,
                'has_child'   => null,
                'nam'         => null,
            ],
        ]);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_injection($builder)
    {
        $builder->addColumn('t_article A/t_comment C');

        $builder->where([
            [
                ''                => 1,
                'A.comment_count' => 2,
                '*.article_id'    => 3,
            ],
            'A.article_id' => [
                '*.*'          => 'injected1!',
                '*.article_id' => 'injected2!',
                [
                    '*.*' => 'injected3!',
                ],
            ],
        ]);
        $qi = function ($str) use ($builder) {
            return $builder->getDatabase()->getPlatform()->quoteSingleIdentifier($str);
        };
        $C = $builder->getDatabase()->getCompatiblePlatform()->quoteIdentifierIfNeeded('C');
        $primary_key = Database::AUTO_PRIMARY_KEY;

        // OR でも効いている
        $this->assertStringIgnoreBreak("SELECT A.*, A.article_id AS {$primary_key}c, NULL AS $C
FROM t_article A
WHERE ((A.article_id = '1')
OR (/* vcolumn comment_count-k */ (SELECT COUNT(*) AS {$qi('*@count')} FROM t_comment WHERE t_comment.article_id = A.article_id) IN ('2'))
OR (A.article_id = '3'))
AND (FALSE)", $builder->queryInto());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_builder($builder)
    {
        // クエリビルダは無視されるはずなのでその検証用
        $builder->from($builder->getDatabase()->select('t_article T'))->innerJoinOn('foreign_p', ['1'])->addSelect('*');
        $builder->where([
            'hoge' => 1,
            'A'    => $builder->getDatabase()->select('test1.id', ['fuga' => 0]),
            $builder->getDatabase()->subexists('foreign_c1', ['hoge' => 1]),
        ]);
        $this->assertStringContainsString('(A IN (SELECT test1.id FROM test1 WHERE fuga = ?))', (string) $builder);
        $this->assertStringContainsString('((EXISTS (SELECT * FROM foreign_c1 WHERE (hoge = ?) AND (foreign_c1.id = foreign_p.id))))', (string) $builder);
        $this->assertEquals([1, 0, 1], $builder->getParams());

        // 外部キーを見てくれる
        $builder->reset()->column('foreign_p')->where([
            $builder->getDatabase()->subexists('foreign_c1', ['hoge' => 1]),
            $builder->getDatabase()->notSubexists('foreign_c2', ['fuga' => 2]),
            $builder->getDatabase()->select('test', ['piyo' => 3])->exists(),
        ]);
        $this->assertStringContainsString('((EXISTS (SELECT * FROM foreign_c1 WHERE (hoge = ?) AND (foreign_c1.id = foreign_p.id))))', (string) $builder);
        $this->assertStringContainsString('((NOT EXISTS (SELECT * FROM foreign_c2 WHERE (fuga = ?) AND (foreign_c2.cid = foreign_p.id))))', (string) $builder);
        $this->assertStringContainsString('((EXISTS (SELECT * FROM test WHERE piyo = ?)))', (string) $builder);
        $this->assertEquals([1, 2, 3], $builder->getParams());

        // 素でやると自動で判断してくれる
        $builder->reset()->column('test1')->innerJoinOn('foreign_p', '1=1')->where([
            $builder->getDatabase()->subexists('foreign_c1', ['hoge' => 1]),
        ]);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_c1 WHERE (hoge = ?) AND (foreign_c1.id = foreign_p.id)))', (string) $builder);

        // キーで明示できる
        $builder->reset()->column('test1')->innerJoinOn('foreign_p PPP', '1=1')->where([
            'single-cond',
            'PPP' => $builder->getDatabase()->subexists('foreign_c1', ['hoge' => 1]),
            'single-cond',
        ]);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_c1 WHERE (hoge = ?) AND (foreign_c1.id = PPP.id)))', (string) $builder);

        // OR も動く
        $builder->reset()->column('foreign_p PPP')->where([
            [
                $builder->getDatabase()->subexists('foreign_c1', ['hoge' => 1]),
                $builder->getDatabase()->notSubexists('foreign_c1', ['hoge' => 1]),
            ],
        ]);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_c1 WHERE (hoge = ?) AND (foreign_c1.id = PPP.id)))) OR ((NOT EXISTS (SELECT * FROM foreign_c1 WHERE (hoge = ?) AND (foreign_c1.id = PPP.id)))', (string) $builder);

        // 相互外部キー1
        $builder->reset()->column('foreign_d1')->where([
            $builder->getDatabase()->subexists('foreign_d2:fk_dd12'),
        ]);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_d2 WHERE foreign_d2.id = foreign_d1.d2_id))', (string) $builder);
        // 相互外部キー2
        $builder->reset()->column('foreign_d2')->where([
            $builder->getDatabase()->subexists('foreign_d1:fk_dd21'),
        ]);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_d1 WHERE foreign_d1.id = foreign_d2.id))', (string) $builder);
        // ダブル外部キー
        $builder->reset()->column('foreign_s')->where([
            $builder->getDatabase()->subexists('foreign_sc:fk_sc1'),
            $builder->getDatabase()->subexists('foreign_sc:fk_sc2'),
        ]);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id1 = foreign_s.id))', (string) $builder);
        $this->assertStringContainsString('(EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id2 = foreign_s.id))', (string) $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_empty($builder)
    {
        // 条件が空なら where 句は生成されない
        $builder->where([]);
        $this->assertStringNotContainsString('where', "$builder");

        // 条件があっても空配列は無視されるのでやはり生成されない
        $builder->where(['id' => []]);
        $this->assertStringNotContainsString('where', "$builder");
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_wheres_filter($builder)
    {
        // デフォルトでは false
        $this->assertFalse($builder->isEmptyCondition());

        // '!' 付きが無いなら常に false
        $this->assertFalse($builder->where(['id' => null])->isEmptyCondition());

        // '!' 付きがフィルタされなかったら false
        $this->assertFalse($builder->where(['!id' => 1])->isEmptyCondition());

        // '!' 付きがフィルタされたら true
        $this->assertTrue($builder->where(['!id' => null])->isEmptyCondition());

        // 混在が全部フィルタされたら true
        $this->assertTrue($builder->where([
            'id'  => 1,
            '!id' => null,
        ])->isEmptyCondition());

        // 混在が一部フィルタされたら false
        $this->assertFalse($builder->where([
            'id'   => 1,
            '!id1' => 1,
            '!id2' => null,
        ])->isEmptyCondition());

        // true -> true
        $this->assertEquals($builder->where([
            '!id1' => null,
            '!id2' => null,
        ])->isEmptyCondition(), $builder->where(['!id1' => null])->andWhere(['!id2' => null])->isEmptyCondition());

        // false -> false
        $this->assertEquals($builder->where([
            '!id1' => 1,
            '!id2' => 1,
        ])->isEmptyCondition(), $builder->where(['!id1' => 1])->andWhere(['!id2' => 1])->isEmptyCondition());

        $expected = $builder->where([
            '!id1' => null,
            '!id2' => 1,
        ])->isEmptyCondition();

        // true -> false
        $this->assertEquals($expected, $builder->where(['!id1' => null])->andWhere(['!id2' => 1])->isEmptyCondition());

        // false -> true
        $this->assertEquals($expected, $builder->where(['!id1' => 1])->andWhere(['!id2' => null])->isEmptyCondition());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_groupBy($builder)
    {
        $builder->column('test');

        $this->assertQuery('SELECT test.* FROM test GROUP BY id', $builder->groupBy('id'));
        $this->assertQuery('SELECT test.* FROM test GROUP BY id1, id2', $builder->groupBy('id1', 'id2'));
        $this->assertQuery('SELECT test.* FROM test GROUP BY id1, id2', $builder->groupBy(['id1', 'id2']));
        $this->assertQuery('SELECT test.* FROM test GROUP BY ttt.id1, ttt.id2', $builder->groupBy(['ttt' => ['id1', 'id2']]));
        $this->assertQuery('SELECT test.* FROM test GROUP BY ttt.id1, ttt.id2, id3', $builder->groupBy(['ttt' => ['id1', 'id2'], 'id3']));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_havings($builder)
    {
        $builder->column('test');

        $this->assertQuery('SELECT test.* FROM test HAVING (id = 1) AND (seq = 1)', $builder->having('id = 1')->andHaving('seq = 1'));

        $this->assertQuery('SELECT test.* FROM test HAVING id = 1', $builder->having('id = 1'));
        $this->assertQuery('SELECT test.* FROM test HAVING (id = 1) AND (seq = 1)', $builder->having(['id = 1', 'seq = 1']));
        $this->assertQuery('SELECT test.* FROM test HAVING (id = 1) OR (seq = 1)', $builder->having('id = 1', 'seq = 1'));
        $this->assertQuery('SELECT test.* FROM test HAVING ((id = 1) AND (seq = 1)) OR ((id = 2) AND (seq = 2))', $builder->having(
            ['id = 1', 'seq = 1'],
            ['id = 2', 'seq = 2']
        ));

        $this->assertQuery('SELECT test.* FROM test HAVING id = ?', $builder->having(['id' => 1]));
        $this->assertEquals([1], $builder->getParams());

        $this->assertQuery("SELECT test.* FROM test HAVING (id = ?) AND (seq = ?)", $builder->having(['id = ?' => 1, 'seq = ?' => 2]));
        $this->assertEquals([1, 2], $builder->getParams());

        $this->assertQuery("SELECT test.* FROM test HAVING (id1 = 0) AND (id2 = ?) AND (id3 IN (?,?)) AND (id4 = ?) AND (id5 IN (?,?))", $builder->having(
            [
                'id1 = 0',
                'id2'        => 1,
                'id3'        => [2, 3],
                'id4 = ?'    => 4,
                'id5 IN (?)' => [5, 6],
            ]
        ));
        $this->assertEquals([1, 2, 3, 4, 5, 6], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_notHavings($builder)
    {
        $builder->column('test');

        $this->assertQuery('SELECT test.* FROM test HAVING NOT (id = 1)', $builder->notHaving('id = 1'));
        $this->assertQuery('SELECT test.* FROM test HAVING NOT ((id = 1) AND (seq = 1))', $builder->notHaving(['id = 1', 'seq = 1']));
        $this->assertQuery('SELECT test.* FROM test HAVING NOT ((id = 1) OR (seq = 1))', $builder->notHaving('id = 1', 'seq = 1'));
        $this->assertQuery('SELECT test.* FROM test HAVING NOT (((id = 1) AND (seq = 1)) OR ((id = 2) AND (seq = 2)))', $builder->notHaving(
            ['id = 1', 'seq = 1'],
            ['id = 2', 'seq = 2']
        ));

        $this->assertQuery("SELECT test.* FROM test HAVING NOT ((and_cond = ?) AND ((0) OR ((or_cond1 = ?) AND (or_cond2 = ?)) OR ((or_cond1 = ?) AND (or_cond2 = ?))))", $builder->notHaving([
            'and_cond' => 1,
            [
                '1' => '0',
                [
                    'or_cond1' => 1,
                    'or_cond2' => 2,
                ],
                [
                    'or_cond1' => 3,
                    'or_cond2' => 4,
                ],
            ],
        ]));
        $this->assertEquals([1, 1, 2, 3, 4], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_andNotHavings($builder)
    {
        $builder->column('test');

        $builder->andNotHaving('id = 1');
        $builder->andNotHaving(['id = 1', 'seq = 1']);
        $builder->andNotHaving('id = 1', 'seq = 1');
        $this->assertQuery('SELECT test.* FROM test HAVING (NOT (id = 1)) AND (NOT ((id = 1) AND (seq = 1))) AND (NOT ((id = 1) OR (seq = 1)))', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orHavings($builder)
    {
        $builder->column('test');

        $builder->orHaving('id = 1');
        $builder->orHaving(['id = 1', 'seq = 1']);
        $builder->orHaving('id = 1', 'seq = 1');
        $this->assertQuery('SELECT test.* FROM test HAVING (id = 1) OR ((id = 1) AND (seq = 1)) OR ((id = 1) OR (seq = 1))', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orNotHavings($builder)
    {
        $builder->column('test');

        $builder->orNotHaving('id = 1');
        $builder->orNotHaving(['id = 1', 'seq = 1']);
        $builder->orNotHaving('id = 1', 'seq = 1');
        $this->assertQuery('SELECT test.* FROM test HAVING (NOT (id = 1)) OR (NOT ((id = 1) AND (seq = 1))) OR (NOT ((id = 1) OR (seq = 1)))', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_endHavings($builder)
    {
        $builder->column('test');

        $builder->having('a');
        $builder->orHaving('b');
        //$builder->endHaving();
        $builder->andHaving('c');
        $this->assertQuery('SELECT test.* FROM test HAVING (a) OR (b) AND (c)', $builder);

        $builder->column('test');

        $builder->having('a');
        $builder->orHaving('b');
        $builder->endHaving();
        $builder->andHaving('c');
        $this->assertQuery('SELECT test.* FROM test HAVING ((a) OR (b)) AND (c)', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orderBy($builder)
    {
        $builder->column('test');

        $this->assertQuery('SELECT test.* FROM test ORDER BY test.id ASC', $builder->orderBy(true));
        $this->assertQuery('SELECT test.* FROM test ORDER BY test.id DESC', $builder->orderBy(false));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id1 ASC, id2 DESC, id3 DESC', $builder->orderBy([['id1', 'ASC'], ['id2', false], ['id3']], false));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id ASC', $builder->orderBy('id'));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id ASC, name DESC', $builder->orderBy('+id-name'));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id DESC', $builder->orderBy('-id'));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id ASC', $builder->orderBy('id', true));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id DESC', $builder->orderBy('id', false));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id DESC', $builder->orderBy('id', 'DESC'));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id1 DESC, id2 DESC', $builder->orderBy(['id1', 'id2'], 'DESC'));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id1 DESC, id2 DESC', $builder->orderBy(['id1', 'id2'], false));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id1 ASC, id2 DESC, id3 ASC', $builder->orderBy(['id1' => 'ASC', 'id2' => 'DESC', 'id3']));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id1 ASC, id2 DESC, id3 ASC', $builder->orderBy(['id1' => true, 'id2' => false, 'id3']));
        $this->assertQuery('SELECT test.* FROM test ORDER BY id1 DESC, id2 ASC, id3 ASC', $builder->orderBy(['-id1', '+id2', 'id3']));
        $this->assertQuery('SELECT test.* FROM test ORDER BY NULL IS NULL', $builder->orderBy(new Expression('NULL IS NULL')));
        $this->assertQuery('SELECT test.* FROM test ORDER BY ? IS NULL DESC', $builder->orderBy(new Expression('? IS NULL', [1]), false));
        $this->assertQuery('SELECT test.* FROM test ORDER BY ? IS NULL ASC, ? IS NULL DESC, EXISTS (SELECT * FROM test WHERE id = ?) DESC', $builder->orderBy([
            new Expression('? IS NULL ASC', [1]),
            new Expression('? IS NULL DESC', [2]),
            [$builder->getDatabase()->selectExists('test', ['id' => 3]), false],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orderBy_php($builder)
    {
        $builder->column([
            'test' => [
                'id' => \Closure::fromCallable('intval'),
                'name',
            ],
        ])->limit(3);

        // orderBy
        $builder->orderBy([
            '' => [
                'id' => [2, 1, 3],
            ],
        ]);
        $this->assertSame([
            1 => ['id' => 2, 'name' => 'b'],
            0 => ['id' => 1, 'name' => 'a'],
            2 => ['id' => 3, 'name' => 'c'],
        ], $builder->array());

        // クロージャで行ソート(array)
        $builder->orderBy(function ($a, $b) { return $b['id'] - $a['id']; });
        $this->assertSame([
            2 => ['id' => 3, 'name' => 'c'],
            1 => ['id' => 2, 'name' => 'b'],
            0 => ['id' => 1, 'name' => 'a'],
        ], $builder->array());

        // ルールで列ソート(array)
        $builder->orderBy(['' => ['id' => [2, 1, 3]]]);
        $this->assertSame([
            1 => ['id' => 2, 'name' => 'b'],
            0 => ['id' => 1, 'name' => 'a'],
            2 => ['id' => 3, 'name' => 'c'],
        ], $builder->array());

        // ルールで列ソート(pairs)
        $builder->orderBy(['' => ['name' => ['b', 'a', 'c']]]);
        $this->assertSame([
            2 => 'b',
            1 => 'a',
            3 => 'c',
        ], $builder->pairs());

        $builder->column('test.name as name@string')->limit(3);

        // クロージャで行ソート(lists)
        $builder->orderBy(function ($a, $b) { return strcmp($b, $a); });
        $this->assertSame([
            2 => 'c',
            1 => 'b',
            0 => 'a',
        ], $builder->lists());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orderBySecure($builder)
    {
        $builder->getDatabase()->declareVirtualTable('vt_test_orderBySecure', ['misctype']);
        $builder->column([
            't_article'             => [
                '*',
                'article_id',
                'title as title9',
                'title2',
            ],
            'vt_test_orderBySecure' => [
                'pid',
                'prid' => 'pid',
            ],
            [
                'hoge' => 't_article.title',
            ],
        ])
            ->with('cte', $builder->getDatabase()->select('test1.name1 as test1name1'))
            ->innerJoinOn('cte fcte', 'TRUE')
            ->innerJoinOn('test2 T2', 'TRUE')
            ->innerJoinOn($builder->getDatabase()->select('test'), 'TRUE');

        // 't_article.article_id' は SELECT に出現するので許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('t_article.article_id');
        $this->assertStringContainsString('ORDER BY t_article.article_id ASC', "$builder");

        // 'article_id' は SELECT 句に出現しないがカラムは存在するので許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('article_id');
        $this->assertStringContainsString('ORDER BY article_id ASC', "$builder");

        // 'test2.id' も同様
        $builder->resetQueryPart('orderBy')->orderBySecure('test2.id');
        $this->assertStringContainsString('ORDER BY test2.id ASC', "$builder");

        // 仮想カラムは許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('title2');
        $this->assertStringContainsString('ORDER BY title2 ASC', "$builder");

        // 仮想テーブルは許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('pid');
        $this->assertStringContainsString('ORDER BY pid ASC', "$builder");

        // 同上。エイリアス版
        $builder->resetQueryPart('orderBy')->orderBySecure('prid');
        $this->assertStringContainsString('ORDER BY prid ASC', "$builder");

        // エイリアス名は許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('hoge');
        $this->assertStringContainsString('ORDER BY hoge ASC', "$builder");

        // インラインなエイリアスも許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('title9');
        $this->assertStringContainsString('ORDER BY title9 ASC', "$builder");

        // サブクエリな from でも解釈可能なら許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('data');
        $this->assertStringContainsString('ORDER BY data ASC', "$builder");

        // CTE でも中身がクエリビルダなら許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure('fcte.name1');
        $this->assertStringContainsString('ORDER BY fcte.name1 ASC', "$builder");

        // 同上。エイリアス版
        $builder->resetQueryPart('orderBy')->orderBySecure('test1name1');
        $this->assertStringContainsString('ORDER BY test1name1 ASC', "$builder");

        // なんだかよくわからないテーブルは許容されないはず
        $builder->resetQueryPart('orderBy')->orderBySecure('t_unknown.id');
        $this->assertStringNotContainsString('ORDER BY ', "$builder");

        // なんだかよくわからないカラムは許容されないはず
        $builder->resetQueryPart('orderBy')->orderBySecure('t_article.unknown');
        $this->assertStringNotContainsString('ORDER BY ', "$builder");

        // 'NOW()' は許容されないはず
        $builder->resetQueryPart('orderBy')->orderBySecure('NOW()');
        $this->assertStringNotContainsString('ORDER BY ', "$builder");

        // しかし Expression 化すれば許容されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure(new Expression('NOW()'));
        $this->assertStringContainsString('ORDER BY NOW()', "$builder");

        // 配列は全て実行されるが不正なものは除外されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure(['t_article.article_id', 'invalid', 'test2.id' => 'ASC'], false);
        $this->assertStringContainsString('ORDER BY t_article.article_id DESC, test2.id ASC', "$builder");

        // 明らかな攻撃クエリ
        $builder->resetQueryPart('orderBy')->orderBySecure(';DELETE FROM tablename -- .id');
        $this->assertStringNotContainsString('ORDER BY ', "$builder");

        // 順序に変な文字を与えても ASC 化されるはず
        $builder->resetQueryPart('orderBy')->orderBySecure(['t_article.article_id', 'invalid', 'test2.id' => 'invalid1'], 'invalid2');
        $this->assertStringContainsString('ORDER BY t_article.article_id ASC, test2.id ASC', "$builder");

        // +-プレフィックス
        $builder->resetQueryPart('orderBy')->orderBySecure(['-t_article.article_id', '+test2.id']);
        $this->assertStringContainsString('ORDER BY t_article.article_id DESC, test2.id ASC', "$builder");

        // 複合+-プレフィックス
        $builder->resetQueryPart('orderBy')->orderBySecure(['-t_article.article_id', '+test2.id-test2.name2']);
        $this->assertStringContainsString('ORDER BY t_article.article_id DESC, test2.id ASC, test2.name2 DESC', "$builder");

        // 複合+-プレフィックスは1つでもダメなら丸ごと除外される
        $builder->resetQueryPart('orderBy')->orderBySecure(['+test2.invalid-test2.name2']);
        $this->assertStringNotContainsString('ORDER BY ', "$builder");
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orderByPrimary($builder)
    {
        $builder->column('noprimary.id');
        $this->assertQuery('SELECT noprimary.id FROM noprimary', $builder->orderByPrimary());

        $builder->column('v_blog.*');
        $this->assertQuery('SELECT v_blog.* FROM v_blog', $builder->orderByPrimary());

        $builder->reset()->column('test1.id');
        $this->assertQuery('SELECT test1.id FROM test1 ORDER BY test1.id ASC', $builder->orderByPrimary());
        $this->assertQuery('SELECT test1.id FROM test1 ORDER BY test1.id DESC', $builder->orderByPrimary(false));

        $builder->reset()->column('test1.id')->orderBy('hoge');
        $this->assertQuery('SELECT test1.id FROM test1 ORDER BY hoge ASC, test1.id ASC', $builder->orderByPrimary(true, true));
        $this->assertQuery('SELECT test1.id FROM test1 ORDER BY hoge ASC, test1.id ASC, test1.id DESC', $builder->orderByPrimary(false, true));

        $builder->reset();
        $this->assertException(new \UnexpectedValueException('query builder is not set'), L($builder)->orderByPrimary());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orderByRandom($builder)
    {
        /// 方言があるのでクエリレベルではなく結果レベルでテストしている

        $sorted = $builder->getDatabase()->select('test.id')->orderByPrimary()->lists();
        $select = $builder->getDatabase()->select('test.id')->orderByRandom();
        $this->assertNotEquals($select->lists(), $sorted);

        // mysql だけは seed が活きる
        if ($builder->getDatabase()->getCompatiblePlatform()->getName() === 'mysql') {
            $select = $builder->getDatabase()->select('test.id')->orderByRandom(123);
            $this->assertEquals($select->lists(), $select->lists());
        }

        // ついでに [0~1.0) を担保
        $random = $builder->getDatabase()->getCompatiblePlatform()->getRandomExpression(null);
        $actual = $builder->getDatabase()->fetchTuple("SELECT $random as r1, $random as r2");
        $this->assertNotEquals($actual['r1'], $actual['r2']);
        $this->assertGreaterThanOrEqual(0.0, $actual['r1']);
        $this->assertLessThan(1.0, $actual['r1']);
        $this->assertGreaterThanOrEqual(0.0, $actual['r2']);
        $this->assertLessThan(1.0, $actual['r2']);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_orderByNulls($builder)
    {
        $builder->column('nullable.cint');

        $builder->setNullsOrder('min');
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 0 ELSE 1 END ASC, cint ASC', (string) $builder->orderBy('cint', true));
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 0 ELSE 1 END DESC, cint DESC', (string) $builder->orderBy('cint', false));
        $this->assertEquals([null, null, null, null, null, -4, -2, 0, 2, 4], $builder->orderBy('cint', true)->lists());
        $this->assertEquals([4, 2, 0, -2, -4, null, null, null, null, null], $builder->orderBy('cint', false)->lists());

        $builder->setNullsOrder('max');
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 1 ELSE 0 END ASC, cint ASC', (string) $builder->orderBy('cint', true));
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 1 ELSE 0 END DESC, cint DESC', (string) $builder->orderBy('cint', false));
        $this->assertEquals([-4, -2, 0, 2, 4, null, null, null, null, null], $builder->orderBy('cint', true)->lists());
        $this->assertEquals([null, null, null, null, null, 4, 2, 0, -2, -4], $builder->orderBy('cint', false)->lists());

        $builder->setNullsOrder('first');
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 1 ELSE 0 END DESC, cint ASC', (string) $builder->orderBy('cint', true));
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 1 ELSE 0 END DESC, cint DESC', (string) $builder->orderBy('cint', false));
        $this->assertEquals([null, null, null, null, null, -4, -2, 0, 2, 4], $builder->orderBy('cint', true)->lists());
        $this->assertEquals([null, null, null, null, null, 4, 2, 0, -2, -4], $builder->orderBy('cint', false)->lists());

        $builder->setNullsOrder('last');
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 1 ELSE 0 END ASC, cint ASC', (string) $builder->orderBy('cint', true));
        $this->assertStringContainsString('ORDER BY CASE WHEN cint IS NULL THEN 1 ELSE 0 END ASC, cint DESC', (string) $builder->orderBy('cint', false));
        $this->assertEquals([-4, -2, 0, 2, 4, null, null, null, null, null], $builder->orderBy('cint', true)->lists());
        $this->assertEquals([4, 2, 0, -2, -4, null, null, null, null, null], $builder->orderBy('cint', false)->lists());

        $this->assertStringContainsString('ORDER BY CASE WHEN NOW() IS NULL THEN 1 ELSE 0 END ASC, NOW() ASC', (string) $builder->orderBy(new Expression('NOW()'), true));
        $this->assertStringContainsString('ORDER BY CASE WHEN NOW() IS NULL THEN 1 ELSE 0 END ASC, NOW() DESC', (string) $builder->orderBy(new Expression('NOW()'), false));

        $builder->setNullsOrder(null);
        $this->assertStringContainsString('CASE WHEN cint IS NULL THEN 0 ELSE 1 END ASC, cint ASC', (string) $builder->orderBy('cint', true, 'min'));
        $this->assertStringContainsString('CASE WHEN cint IS NULL THEN 1 ELSE 0 END DESC, cint DESC', (string) $builder->orderBy(['cint' => [false, 'first']]));
        $this->assertEquals([null, null, null, null, null, -4, -2, 0, 2, 4], $builder->orderBy('cint', true, 'min')->lists());
        $this->assertEquals([null, null, null, null, null, 4, 2, 0, -2, -4], $builder->orderBy('cint', false, 'first')->lists());

        $builder->setNullsOrder('hoge');
        $this->assertException('hoge is not supported', L($builder->orderBy('hoge'))->getQuery());
        $this->assertException('is not support parametable query', L($builder->orderBy(new Expression('? + ?', [1, 2])))->getQuery());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_comment($builder)
    {
        $builder->column('test');

        // 文字列は追加
        $builder->comment('sql comment1');
        $builder->comment('sql comment2');
        $this->assertQuery("
-- sql comment1 
-- sql comment2 
SELECT test.* FROM test", $builder);

        // 配列は置換
        $builder->comment(['sql comment3', 'sql comment4']);
        $this->assertQuery("
-- sql comment3 
-- sql comment4 
SELECT test.* FROM test", $builder);

        // 裏仕様で配列はネストもできる（インデントされる）
        $builder->comment(['scalar comment', 'parent comment' => ['child comment1', 'child comment2'], ['array comment1', 'array comment2']]);
        $this->assertQuery("
-- scalar comment 
-- parent comment 
   -- child comment1 
   -- child comment2 
   -- array comment1 
   -- array comment2 
SELECT test.* FROM test", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_limit($builder)
    {
        $builder->column('test');

        $builder->limit(9);
        $this->assertSame(null, $builder->getQueryPart('offset'));
        $this->assertSame(9, $builder->getQueryPart('limit'));

        $builder->limit(9, 1);
        $this->assertSame(1, $builder->getQueryPart('offset'));
        $this->assertSame(9, $builder->getQueryPart('limit'));

        $builder->limit([2 => 8]);
        $this->assertSame(2, $builder->getQueryPart('offset'));
        $this->assertSame(8, $builder->getQueryPart('limit'));

        $builder->limit([3, 7]);
        $this->assertSame(3, $builder->getQueryPart('offset'));
        $this->assertSame(7, $builder->getQueryPart('limit'));

        $builder->limit(0, 0);
        $this->assertSame(0, $builder->getQueryPart('offset'));
        $this->assertSame(0, $builder->getQueryPart('limit'));

        $this->assertException(new \InvalidArgumentException('1 or 2'), L($builder)->limit([3, 7, 9]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_page($builder)
    {
        $builder->column('test');

        // limit が設定されていなければいかなる page を与えても null のまま
        $builder->page(0);
        $this->assertSame(null, $builder->getQueryPart('offset'));
        $this->assertSame(null, $builder->getQueryPart('limit'));

        // 同上
        $builder->page(5);
        $this->assertSame(null, $builder->getQueryPart('offset'));
        $this->assertSame(null, $builder->getQueryPart('limit'));

        // limit:20 を設定して・・・
        $builder->limit(20);

        // page:0 なら 0～20になる
        $builder->page(0);
        $this->assertSame(0, $builder->getQueryPart('offset'));
        $this->assertSame(20, $builder->getQueryPart('limit'));

        // page:5 なら 100～20になる
        $builder->page(5);
        $this->assertSame(100, $builder->getQueryPart('offset'));
        $this->assertSame(20, $builder->getQueryPart('limit'));

        // 設定されていなくても 第2引数で与えることができる
        $builder->page(0, 10);
        $this->assertSame(0, $builder->getQueryPart('offset'));
        $this->assertSame(10, $builder->getQueryPart('limit'));

        // 同上
        $builder->page(5, 10);
        $this->assertSame(50, $builder->getQueryPart('offset'));
        $this->assertSame(10, $builder->getQueryPart('limit'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_union($builder)
    {
        $original = clone $builder;
        $us = $builder->getDatabase()->getCompatiblePlatform()->supportsUnionParentheses() ? '(' : '';
        $ue = $builder->getDatabase()->getCompatiblePlatform()->supportsUnionParentheses() ? ')' : '';

        $builder->reset();
        $this->assertQuery("{$us}SELECT 1{$ue}", $builder->union('SELECT 1'));
        $this->assertQuery("{$us}SELECT 1{$ue} UNION {$us}SELECT 2{$ue}", $builder->union('SELECT 2'));
        $this->assertQuery("{$us}SELECT 1{$ue} UNION {$us}SELECT 2{$ue} UNION ALL {$us}SELECT 3{$ue}", $builder->unionAll('SELECT 3'));

        $builder->reset()->union('SELECT 1')->union($original->column('test.id')->where(['id' => 1]));
        $this->assertQuery("{$us}SELECT 1{$ue} UNION {$us}SELECT test.id FROM test WHERE id = ?{$ue}", $builder);
        $this->assertEquals([1], $builder->getParams());

        $builder->column([[new Expression('RAND(?)', 100)]])->where(['uid' => 'inner'])->innerJoinOn('test1', new Expression('uid1 = ?', 10), '__dbml_union_table')->innerJoinOn('test2', new Expression('uid2 = ?', 20), 'test2')->orderBy('uuid')->limit(10, 1);

        $offset_limit = function ($offset, $limit, $order) use ($builder) {
            $offset_limit = $builder->getDatabase()->getPlatform()->modifyLimitQuery('', $limit, $offset);
            return trim($order ? $offset_limit : strtr($offset_limit, ['ORDER BY (SELECT 0)' => '']));
        };

        $this->assertQuery("SELECT RAND(?) FROM ({$us}SELECT 1{$ue} UNION {$us}SELECT test.id FROM test WHERE id = ?{$ue}) __dbml_union_table INNER JOIN test1 ON uid1 = ? INNER JOIN test2 ON uid2 = ? WHERE uid = ? ORDER BY uuid ASC {$offset_limit(1, 10, false)}", $builder);
        $this->assertEquals([100, 1, 10, 20, 'inner'], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_vtable($builder, $database)
    {
        $this->assertEquals([
            ['article_id' => '1', 'comment_id' => '1'],
            ['article_id' => '1', 'comment_id' => '2'],
            ['article_id' => '1', 'comment_id' => '3'],
        ], $database->selectArray('v_article_comment'));

        $this->assertEquals([
            ['article_id' => '1', 'comment_id' => '2'],
        ], $database->selectArray('v_article_comment', [
            'C.comment_id' => 2,
        ]));

        $this->assertEquals([
            ['article_id' => '1', 'comment_id' => '2', 'id' => 3],
        ], $database->selectArray([
            'v_article_comment' => [
                'id' => $database->raw('(t_article.article_id + C.comment_id)'),
            ],
        ], [
            'C.comment_id' => 2,
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_vcolumn_lazy($builder, $database)
    {
        $database->overrideColumns([
            'foreign_p' => [
                'count_child' => function (Database $database) {
                    return $database->foreign_c1->as('C1')->subcount('*', ['id' => 0]);
                },
                'has_child'   => [
                    'select' => function () use ($database) {
                        return $database->foreign_c2->as('C2')->subexists('*', ['cid' => 0]);
                    },
                    'lazy'   => true,
                ],
            ],
        ]);

        $builder->column([
            'foreign_p P' => [
                'alias1' => 'count_child',
                'alias2' => 'has_child',
            ],
        ])->where([
            '1',
            'P.count_child' => 0,
            'P.has_child'   => 1,
        ]);

        $qi = function ($str) use ($database) {
            return $database->getPlatform()->quoteSingleIdentifier($str);
        };
        $exists = $database->getCompatiblePlatform()->convertSelectExistsQuery("EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid = '0') AND (C2.cid = P.id))");
        $this->assertStringIgnoreBreak(<<<SQL
SELECT
(SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) AS alias1,
$exists AS alias2
FROM foreign_p P
WHERE (1)
AND (/* vcolumn count_child-k */ (SELECT COUNT(*) AS {$qi("*@count")} FROM foreign_c1 C1 WHERE (C1.id = '0') AND (C1.id = P.id)) IN ('0'))
AND (/* vcolumn has_child-k */ (EXISTS (SELECT * FROM foreign_c2 C2 WHERE (C2.cid = '0') AND (C2.cid = P.id))) IN ('1'))
SQL
            , $builder->queryInto());

        $database->overrideColumns([
            'foreign_p' => [
                'count_child' => null,
                'has_child'   => null,
            ],
        ]);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_existize($builder)
    {
        /// sql server の方言があるのでクエリレベルではなく結果レベルでテストしている

        $this->assertEquals('1', $builder->reset()->column('test')->existize()->value());
        $this->assertEquals('0', $builder->reset()->column('test')->existize(false)->value());

        $this->assertEquals('0', $builder->reset()->column('test')->where('id = -1')->existize()->value());
        $this->assertEquals('1', $builder->reset()->column('test')->where('id = -1')->existize(false)->value());

        if ($builder->getDatabase()->getPlatform() instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            $this->assertQuery('SELECT EXISTS (SELECT * FROM test WHERE id = 1 /* lock for write */)', $builder->reset()->column('test')->where('id = 1')->existize(true, true));
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_countize($builder)
    {
        // 素のクエリは count クエリになる
        $counter = $builder->reset()->column(['test' => 'test_id'])->countize('*');
        $this->assertQuery('SELECT COUNT(*) AS __dbml_auto_cnt FROM test', $counter);

        // limit は無視される
        $counter = $builder->reset()->column(['test' => 'test_id'])->limit(100, 50)->countize('*');
        $this->assertQuery('SELECT COUNT(*) AS __dbml_auto_cnt FROM test', $counter);

        // group by はサブクエリになる
        $counter = $builder->reset()->column(['test' => 'test_id'])->groupBy('test_id')->countize(1);
        $this->assertQuery('SELECT COUNT(1) AS __dbml_auto_cnt FROM (SELECT 1 FROM test GROUP BY test_id) __dbml_auto_table', $counter);

        // union も OK
        $us = $builder->getDatabase()->getCompatiblePlatform()->supportsUnionParentheses() ? '(' : '';
        $ue = $builder->getDatabase()->getCompatiblePlatform()->supportsUnionParentheses() ? ')' : '';
        $original = clone $builder;
        $counter = $builder->reset()->union($original->reset()->column('test1.id'))->union($original->reset()->column('test2.id'))->countize();
        $this->assertQuery("SELECT COUNT(*) AS __dbml_auto_cnt FROM ({$us}SELECT test2.id FROM test2{$ue} UNION {$us}SELECT test2.id FROM test2{$ue}) __dbml_union_table", $counter);

        // limit は無視される
        $counter = $builder->reset()->column(['test' => 'test_id'])->limit(100, 50)->groupBy('test_id')->countize(1);
        $this->assertQuery('SELECT COUNT(1) AS __dbml_auto_cnt FROM (SELECT 1 FROM test GROUP BY test_id) __dbml_auto_table', $counter);

        // order by も無視される
        $counter = $builder->reset()->column(['test' => 'test_id'])->limit(100, 50)->orderBy('test_id')->groupBy('test_id')->countize(1);
        $this->assertQuery('SELECT COUNT(1) AS __dbml_auto_cnt FROM (SELECT 1 FROM test GROUP BY test_id) __dbml_auto_table', $counter);

        // bind 値は引き継がれる
        $counter = $builder->reset()->column(['test' => 'test_id'])->where(['pid' => 1])->groupBy('test_id')->countize('*');
        $this->assertQuery('SELECT COUNT(*) AS __dbml_auto_cnt FROM (SELECT 1 FROM test WHERE pid = ? GROUP BY test_id) __dbml_auto_table', $counter);
        $this->assertEquals([1], $counter->getParams());

        // が、select 句の bind 値は引き継がれない
        $counter = $builder->reset()->column(['test' => new Expression('? as ccc', 99)])->where(['pid' => 1])->countize('*');
        $this->assertQuery('SELECT COUNT(*) AS __dbml_auto_cnt FROM test WHERE pid = ?', $counter);
        $this->assertEquals([1], $counter->getParams());

        // group by があっても同様
        $counter = $builder->reset()->column(['test' => new Expression('? as ccc', 99)])->where(['pid' => 1])->groupBy('test_id')->countize('*');
        $this->assertQuery('SELECT COUNT(*) AS __dbml_auto_cnt FROM (SELECT 1 FROM test WHERE pid = ? GROUP BY test_id) __dbml_auto_table', $counter);
        $this->assertEquals([1], $counter->getParams());

        // ただし、having がある場合はかなり特殊な動きになる
        $counter = $builder->reset()->column(['test' => new Expression('? as ccc', 99)])->where(['pid' => 1])->groupBy('test_id')->having(['aggrc' => 2])->countize('*');
        $this->assertQuery('SELECT COUNT(*) AS __dbml_auto_cnt FROM (SELECT ? as ccc FROM test WHERE pid = ? GROUP BY test_id HAVING aggrc = ?) __dbml_auto_table', $counter);
        $this->assertEquals([99, 1, 2], $counter->getParams());

        // 上記全てを複合しためちゃくちゃ複雑な count(クエリとしての意味はない。というか無茶苦茶なので mysql でしかテストできない)
        $db = $builder->getDatabase();
        if ($db->getPlatform() instanceof MySQLPlatform) {
            $db->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
            $db->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
            $db->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
            $db->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1name11']);
            $db->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'c1name12']);
            $db->insert('foreign_c1', ['id' => 2, 'seq' => 1, 'name' => 'c1name21']);
            $db->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'c2name11']);
            $db->insert('foreign_c2', ['cid' => 1, 'seq' => 2, 'name' => 'c2name12']);
            $db->insert('foreign_c2', ['cid' => 1, 'seq' => 3, 'name' => 'c2name13']);
            $db->insert('foreign_c2', ['cid' => 2, 'seq' => 1, 'name' => 'c2name21']);
            $db->insert('foreign_c2', ['cid' => 2, 'seq' => 2, 'name' => 'c2name22']);
            // join と exists(param 付き)を含むクエリ(where:P.id=1 だが c1 と join してるので2行)
            $query1 = $db->select([
                'foreign_p P1' => [
                    '+foreign_c1 C11',
                    'c2' => $db->subexists('foreign_c2 C21', ['name <> ?' => 'hoge']),
                ],
            ], ['P1.id' => 1]);
            // join と group by を含むクエリ(where はないが P.id で group するので3行)
            $query2 = $db->select([
                'foreign_p P2' => [
                    '+foreign_c1 C12',
                    'c2' => $db->subexists('foreign_c2 C22', ['name <> ?' => 'fuga']),
                ],
            ], [], [], [], 'P2.id');
            // group by と having を含むクエリ(P.id で group した上で having で絞るので1行)
            $query3 = $db->select([
                'foreign_p P3' => [
                    '+foreign_c1 C12',
                    'c2' => $db->select(['foreign_c2 C23' => 'COUNT(*)']),
                ],
            ], [], [], [], 'P3.id', ['c2 >= ?' => 3]);
            // さらに上記を union したものでテスト(All なので計6行)
            $counter = $db->unionAll([$query1, $query2, $query3])->countize();
            $this->assertEquals(6, $counter->value());
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_paginate($builder)
    {
        $paginator = $builder->column('paging')->paginate(2, 3);
        $this->assertEquals([
            //1ページが飛んでいることをわかりやすくするためのコメントアウト
            //array('id' => '1', 'name' => 'a'),
            //array('id' => '2', 'name' => 'b'),
            //array('id' => '3', 'name' => 'c'),
            ['id' => '4', 'name' => 'd'],
            ['id' => '5', 'name' => 'e'],
            ['id' => '6', 'name' => 'f'],
        ], $paginator->getItems());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_sequence($builder)
    {
        $sequencer = $builder->column('paging')->sequence(['id' => 3], 3);
        $this->assertEquals([
            //1ページが飛んでいることをわかりやすくするためのコメントアウト
            //array('id' => '1', 'name' => 'a'),
            //array('id' => '2', 'name' => 'b'),
            //array('id' => '3', 'name' => 'c'),
            ['id' => '4', 'name' => 'd'],
            ['id' => '5', 'name' => 'e'],
            ['id' => '6', 'name' => 'f'],
        ], $sequencer->getItems());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_chunk($builder)
    {
        // スキーマ収集で無駄なクエリが投がるのであらかじめ取得しておく
        foreach ($builder->getDatabase()->getSchema()->getTableNames() as $table) {
            $builder->getDatabase()->getSchema()->getTable($table);
        }

        $logs = $builder->getDatabase()->preview(function ($a) use ($builder) {
            $builder->reset()->column('test');
            $this->assertEquals($builder->array(), iterator_to_array($builder->chunk(3), false));
            $this->assertEquals($builder->orderBy(['id' => false])->array(), iterator_to_array($builder->chunk(3, '-id'), false));
        });
        $this->assertCount(14, $logs);

        $logs = $builder->getDatabase()->preview(function ($a) use ($builder) {
            $builder->reset()->column('multiprimary')->orderBy('subid');
            $this->assertEquals($builder->array(), iterator_to_array($builder->chunk(3, 'subid'), false));
            $this->assertEquals($builder->orderBy(['subid' => false])->array(), iterator_to_array($builder->chunk(3, '-subid'), false));
        });
        $this->assertCount(14, $logs);

        try {
            iterator_to_array($builder->reset()->column('noauto')->chunk(10), false);
            $this->fail('exception not thrown.');
        }
        catch (\Exception $ex) {
            $this->assertStringContainsString('not autoincrement column', $ex->getMessage());
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_neighbor($builder)
    {
        $builder->column('test.id, name');

        // 単純
        $this->assertEquals([
            -1 => ['id' => '4', 'name' => 'd'],
            1  => ['id' => '6', 'name' => 'f'],
        ], $builder->neighbor(['id' => 5]));

        // 前後2行
        $this->assertEquals([
            -2 => ['id' => '3', 'name' => 'c'],
            -1 => ['id' => '4', 'name' => 'd'],
            1  => ['id' => '6', 'name' => 'f'],
            2  => ['id' => '7', 'name' => 'g'],
        ], $builder->neighbor(['id' => 5], 2));

        // 後ろがない
        $this->assertEquals([
            -1 => ['id' => '9', 'name' => 'i'],
        ], $builder->neighbor(['id' => 10], 1));

        // 複数指定は行値式になる
        if ($builder->getDatabase()->getPlatform() instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $builder->getDatabase()->getCompatiblePlatform()->supportsRowConstructor()) {
            $builder->column('multiprimary.mainid, subid');
            $this->assertEquals([
                -1 => ['mainid' => '1', 'subid' => '4'],
                1  => ['mainid' => '2', 'subid' => '6'],
            ], $builder->neighbor(['mainid' => 1, 'subid' => 5], 1));
        }

        // テーブル記法なので JOIN も使えるはず
        $builder->column([
            't_comment' => [
                'comment_id',
                'comment',
                '+t_article' => [
                    'article_id',
                    'title',
                ],
            ],
        ]);
        $this->assertEquals([
            -1 => [
                'comment_id' => '1',
                'article_id' => '1',
                'comment'    => 'コメント1です',
                'title'      => 'タイトルです',
            ],
            1  => [
                'comment_id' => '3',
                'article_id' => '1',
                'comment'    => 'コメント3です',
                'title'      => 'タイトルです',
            ],
        ], $builder->neighbor(['comment_id' => 2]));

        // 例外
        $this->assertException('$predicates is empty', L($builder)->neighbor([]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_rowcount($builder)
    {
        $builder->column('test')->limit(5, 5);

        $this->assertNull($builder->getRowCount());
        $builder->array();
        $this->assertNull($builder->getRowCount());

        $builder->setRowCountable(true);
        $this->assertException('yet', L($builder)->getRowCount());

        $builder->array();
        $this->assertEquals(10, $builder->getRowCount());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_getSubbuilder($builder)
    {
        $builder->column(
            [
                't_article' => [
                    't_comment c1' => ['*'],
                    't_comment c2' => ['*'],
                ],
            ]
        );

        // 未指定は配列で返すはず
        $this->assertCount(2, $builder->getSubbuilder());

        // c1 を取って DESCすると・・・
        $builder->getSubbuilder('c1')->orderBy('c1.comment_id', 'desc');
        $row = $builder->limit(1)->tuple();
        // c1 のみ逆順で返るはず
        $this->assertEquals(array_keys($row['c1']), array_reverse(array_keys($row['c2'])));

        // 存在しないのは例外が飛ぶはず
        $this->assertException(new \InvalidArgumentException('not defined'), L($builder)->getSubbuilder('hoge'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_resetQueryPart($builder)
    {
        $builder->column('test.id');
        $builder->where(['id' => [1, 2, 3]]);
        $builder->orderBy(['id' => 'ASC']);
        $builder->groupBy('id');

        $this->assertQuery('SELECT test.id FROM test GROUP BY id ORDER BY id ASC', $builder->resetQueryPart('where'));
        $this->assertQuery('SELECT * FROM test GROUP BY id ORDER BY id ASC', $builder->resetQueryPart(['select']));
        $this->assertQuery('SELECT * FROM test', $builder->resetQueryPart(['orderBy', 'groupBy']));

        $this->assertException('is undefined', L($builder)->resetQueryPart('hogera'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_reset($builder)
    {
        $original = clone $builder;
        $builder->column([
            't_article' => [
                'phpe'       => function () { },
                't_comment'  => ['*'],
                '+t_comment' => ['*'],
            ],
        ]);
        $builder->orderBy(['' => function () { }]);
        $builder->hint('hint');
        $builder->lockForUpdate('SKIP');
        $builder->addSelectOption('SOP');

        // reset すれば全部戻るか？ …のためのテストだけど、ここで変更メソッドを呼ばないと検出できないので気休め
        // 例えば「ビルダにメンバが増えて reset を修正しなければならないが忘れた」が検出できない
        $this->assertEquals((string) $original, (string) $builder->reset());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_subquery($builder)
    {
        $builder->column(
            [
                'test1' => ['id', 'name1'],
                ''      => [
                    'children{id}' => $builder->getDatabase()->subselectArray('test2'),
                ],
            ]
        )->limit(2);

        // Array は行ごとに children が返ってくるはず
        $this->assertEquals([
            [
                'id'       => '1',
                'name1'    => 'a',
                'children' => [
                    [
                        'id'    => '1',
                        'name2' => 'A',
                    ],
                ],
            ],
            [
                'id'       => '2',
                'name1'    => 'b',
                'children' => [
                    [
                        'id'    => '2',
                        'name2' => 'B',
                    ],
                ],
            ],
        ], $builder->array());

        // Tuple は単一配列で返ってくるはず
        $this->assertEquals([
            'id'       => '1',
            'name1'    => 'a',
            'children' => [
                ['id' => '1', 'name2' => 'A'],
            ],
        ], $builder->limit(1)->tuple());

        // フェッチしないようにすると・・・
        $builder->where('1=0');

        // Array は空配列
        $this->assertEquals([], $builder->array());
        // Tuple は false 値
        $this->assertEquals(false, $builder->tuple());

        // from が無い subselect 指定は無効なはず
        $this->assertException(new \UnexpectedValueException('has not foreign key'), L($builder)->column([
            [
                'hoge' => $builder->getDatabase()->subselectArray('t_dummy'),
            ],
        ]));

        // prepared statement は使用できないはず
        $this->assertException(new \UnexpectedValueException('not support prepared statement'), L($builder)->column([
            'test1' => ['id', 'name1'],
            ''      => [
                'children{id}' => $builder->getDatabase()->subselectArray('test2')->prepare(),
            ],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_subquery_fk($builder)
    {
        $builder->reset()->column([
            'foreign_d1' => [
                'foreign_d2' => $builder->getDatabase()->subselectArray('foreign_d2:fk_dd12'),
            ],
        ]);
        $this->assertEquals([
            Alias::forge(Database::AUTO_PRIMARY_KEY . 'foreign_d2', 'foreign_d1.d2_id'),
            Alias::forge('foreign_d2', 'NULL'),
        ], $builder->getQueryPart('select'));

        $builder->reset()->column([
            'foreign_d1' => [
                'foreign_d2' => $builder->getDatabase()->subselectArray('foreign_d2:fk_dd21'),
            ],
        ]);
        $this->assertEquals([
            Alias::forge(Database::AUTO_PRIMARY_KEY . 'foreign_d2', 'foreign_d1.id'),
            Alias::forge('foreign_d2', 'NULL'),
        ], $builder->getQueryPart('select'));

        $this->assertException('ambiguous foreign keys', function () use ($builder) {
            $builder->reset()->column([
                'foreign_d1' => [
                    'foreign_d2' => $builder->getDatabase()->subselectArray('foreign_d2'),
                ],
            ]);
        });
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_subquery_refparent($builder, $database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1name11']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'c1name12']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'c2name11']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 2, 'name' => 'c2name12']);

        $builder->column([
            'foreign_p P' => [
                'pid'           => 'id',
                'foreign_c1 C1' => [
                    '*',
                    '..pid',
                    'ppname1' => '..name',
                ],
                'C2'            => $database->subselectTuple([
                    'foreign_c2' => [
                        '*',
                        '..pid',
                        'ppname2' => '..name',
                    ],
                ], ['seq' => 1]),
            ],
        ]);

        $rows = $builder->postselect([
            ['pid' => 1, 'name' => 'hoge', Database::AUTO_PRIMARY_KEY . 'c1' => 1, Database::AUTO_PRIMARY_KEY . 'c2' => 1],
        ]);
        $this->assertEquals([
            [
                'pid'  => 1,
                'name' => 'hoge',
                'C1'   => [
                    1 => ['id' => 1, 'seq' => 1, 'name' => 'c1name11', 'pid' => 1, 'ppname1' => 'hoge'],
                    2 => ['id' => 1, 'seq' => 2, 'name' => 'c1name12', 'pid' => 1, 'ppname1' => 'hoge'],
                ],
                'C2'   => ['cid' => 1, 'seq' => 1, 'name' => 'c2name11', 'pid' => 1, 'ppname2' => 'hoge'],
            ],
        ], $rows);

        $this->assertException('reference undefined parent column', L($builder)->postselect([
            ['xxxpid' => 1, Database::AUTO_PRIMARY_KEY . 'c1' => 1, Database::AUTO_PRIMARY_KEY . 'c2' => 1],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_subquery_noparent($builder, $database)
    {
        $database->insert('foreign_s', [
            'id'   => 1,
            'name' => 'p',
        ]);
        $database->insert('foreign_sc', [
            'id'    => 1,
            's_id1' => 1,
            's_id2' => null,
            'name'  => 'c',
        ]);
        $builder->column([
            'foreign_sc' => [
                'parent1' => $database->subselectTuple('foreign_s:fk_sc1'),
                'parent2' => $database->subselectTuple('foreign_s:fk_sc2'),
            ],
        ]);

        $this->assertEquals([
            'id'      => 1,
            's_id1'   => 1,
            's_id2'   => null,
            'name'    => 'c',
            'parent1' => [
                'id'   => 1,
                'name' => 'p',
            ],
            'parent2' => false,
        ], $builder->where(['' => 1])->tuple());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_subquery_limit($builder)
    {
        $expected = [
            [
                'mainid' => '1',
                'subid'  => '3',
                'name'   => 'c',
            ],
            [
                'mainid' => '1',
                'subid'  => '4',
                'name'   => 'd',
            ],
        ];

        foreach (QueryBuilder::LAZY_MODES as $mode => $opt) {
            $actual = $builder->getDatabase()->selectTuple([
                'multiprimary' => [
                    'sub{mainid}' => $builder->setLazyMode($mode)->column('multiprimary')->limit(2, 2)->array(),
                ],
            ], ['mainid' => '1', 'subid' => '1']);
            if ($opt['generated']) {
                $this->assertInstanceOf(\Generator::class, $actual['sub']);
            }
            $this->assertEquals($expected, arrayval($actual['sub']), $mode);
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_inheritLockMode($builder, $database)
    {
        $platform = $database->getPlatform();
        $readlock = trim($platform->getReadLockSQL());
        $readlock = $readlock ?: trim($platform->appendLockHint('', LockMode::PESSIMISTIC_READ));
        $writelock = trim($platform->getWriteLockSQL());
        $writelock = $writelock ?: trim($platform->appendLockHint('', LockMode::PESSIMISTIC_WRITE));

        $parent = $database->createQueryBuilder();
        $stringify = function (QueryBuilder $parent) use ($database) {
            return implode("\n", $database->preview(function () use ($parent) { $parent->tuple(); }));
        };

        // 親で lockInShare すれば伝播する
        $parent->reset()->lockInShare()->column([
            'test.*' => [
                'ddd{id}' => $builder->reset()->column('test1')->setLazyMode()->array(),
            ],
        ])->limit(1);
        $this->assertEquals(2, substr_count($stringify($parent), $readlock));

        // 親で lockInShare しても子で明示的にしてれば伝播しない
        $parent->reset()->lockInShare()->column([
            'test.*' => [
                'ddd{id}' => $builder->reset()->column('test1')->setLazyMode()->lockForUpdate()->array(),
            ],
        ])->limit(1);
        $this->assertEquals(1, substr_count($stringify($parent), $readlock));
        $this->assertEquals(1, substr_count($stringify($parent), $writelock));

        // そもそも propagateLockMode しなければ伝播しない
        // そもそも InheritLockMode しなければ伝播しない
        $parent->reset()->lockForUpdate()->column([
            'test.*' => [
                'ddd{id}' => $builder->setPropagateLockMode(false)->reset()->column('test2')->setLazyMode()->array(),
            ],
        ])->limit(1);
        $this->assertEquals(0, substr_count($stringify($parent), $readlock));
        $this->assertEquals(1, substr_count($stringify($parent), $writelock));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_gateway($builder, $database)
    {
        $builder->column([
            'foreign_p P' => [
                'C1' => $database->foreign_c1('*'),
            ],
        ]);
        $this->assertArrayHasKey('C1', $builder->getSubbuilder());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_lazymode($builder, $database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1name11']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'c1name12']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'c2name11']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 2, 'name' => 'c2name12']);

        $columns = [
            'foreign_p P' => [
                'C1'            => $database->foreign_c1('*'),
                'foreign_c2 C2' => ['*'],
            ],
        ];
        $expected = [
            'id'   => 1,
            'name' => 'name1',
            'C1'   => [
                1 => ['id' => 1, 'seq' => 1, 'name' => 'c1name11'],
                2 => ['id' => 1, 'seq' => 2, 'name' => 'c1name12'],
            ],
            'C2'   => [
                1 => ['cid' => 1, 'seq' => 1, 'name' => 'c2name11'],
                2 => ['cid' => 1, 'seq' => 2, 'name' => 'c2name12'],
            ],
        ];

        foreach (QueryBuilder::LAZY_MODES as $mode => $opt) {
            $actual = $builder->setDefaultLazyMode($mode)->column($columns)->where(['id' => 1])->tuple();
            if ($opt['generated']) {
                $this->assertInstanceOf(\Generator::class, $actual['C1']);
                $this->assertInstanceOf(\Generator::class, $actual['C2']);
            }
            else {
                $this->assertIsArray($actual['C1']);
                $this->assertIsArray($actual['C2']);
            }
            $this->assertEquals($expected, arrayval($actual));
        }

        $this->assertException('$mode is must be', L($builder)->setLazyMode('hoge'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_postselect($builder)
    {
        $builder->column([
            'test.*' => [
                'func'   => function () { return function ($arg) { return $this['id'] * $arg; }; },
                'this'   => function () { return function () { return $this; }; },
                'static' => function () { return static function () { return $this; }; },
            ],
        ]);
        $actual = $builder->limit(1)->tuple();
        $this->assertEquals(10, $actual['func'](10));
        $this->assertInstanceOf(\ArrayObject::class, $actual['this']());
        $this->assertException('Using $this when not in object context', $actual['static']);

        $actual = $builder->limit(1)->cast()->tuple();
        $this->assertEquals(10, $actual->func(10));
        $this->assertInstanceOf(Entity::class, $actual->this());
        $this->assertException('Using $this when not in object context', $actual->static);

        $builder->column([
            'test' => [
                'subcol{id}' => $builder->getDatabase()->subselectArray('test2.name2'),
            ],
        ]);
        $actual = $builder->postselect([
            ['id' => 1, 'name' => 'a', Database::AUTO_PRIMARY_KEY . 'subcol' => 1],
            ['id' => 2, 'name' => 'b', Database::AUTO_PRIMARY_KEY . 'subcol' => 2],
        ], true);
        $this->assertEquals([
            [
                'id'     => 1,
                'name'   => 'a',
                'subcol' => [
                    ['name2' => 'A'],
                ],
            ],
            [
                'id'     => 2,
                'name'   => 'b',
                'subcol' => [
                    ['name2' => 'B'],
                ],
            ],
        ], $actual);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_before_after($builder)
    {
        $builder->column([
            'test.*' => [
                'func' => function ($row) { return strtoupper($row['name']); },
            ],
        ]);
        $self = $this;
        $builder->before(function ($rows) use ($self) {
            $self->assertInstanceOf(QueryBuilder::class, $this);
            $self->assertCount(2, $rows);
            $self->assertNull($rows[0]['func']);
            return $rows;
        });
        $builder->after(function ($rows) use ($self) {
            $self->assertInstanceOf(QueryBuilder::class, $this);
            $self->assertCount(2, $rows);
            $self->assertEquals('A', $rows[0]['func']);
            unset($rows[1]);
            return $rows;
        });

        $this->assertEquals([
            [
                'id'   => 1,
                'name' => 'a',
                'data' => '',
                'func' => 'A',
            ],
        ], $builder->limit(2)->array());

        $this->assertEquals([
            [
                'id'   => 1,
                'name' => 'a',
                'data' => '',
                'func' => 'A',
            ],
            [
                'id'   => 2,
                'name' => 'b',
                'data' => '',
                'func' => 'B',
            ],
        ], $builder->after(null)->limit(2)->array());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_addSelectOption($builder)
    {
        $builder->addSelectOption(null);
        $builder->addSelectOption(SelectOption::SQL_CACHE);
        $builder->addSelect('t');
        $builder->addSelectOption(SelectOption::SQL_NO_CACHE);
        $builder->addSelect('u');
        $this->assertQuery('SELECT SQL_CACHE SQL_NO_CACHE t, u', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_join($builder)
    {
        $subuilder = clone $builder;
        $subuilder->reset()->column('test1')->where(['id' => 1]);

        $builder->select('*')->from('t_table1')->innerJoinOn(['AT' => $subuilder], ['hoge = fuga']);
        $this->assertQuery('SELECT * FROM t_table1 INNER JOIN (SELECT test1.* FROM test1 WHERE id = ?) AT ON hoge = fuga', $builder);
        $this->assertEquals([1], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_join_on($builder)
    {
        $subuilder = clone $builder;
        $subuilder->column('test2')->where('1=1');

        $builder->column(['test1', '+t' => $subuilder]);
        $this->assertQuery('SELECT test1.* FROM test1 INNER JOIN (SELECT test2.* FROM test2 WHERE 1=1) t ON 1', $builder);

        $subuilder->on([
            '2=2',
            'id' => 3,
        ]);
        $builder->column(['test1', '+t' => $subuilder]);
        $this->assertQuery('SELECT test1.* FROM test1 INNER JOIN (SELECT test2.* FROM test2 WHERE 1=1) t ON (2=2) AND (id = ?)', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_join_subquery($builder)
    {
        $db = $builder->getDatabase();
        $builder->column([
            'test.*'       => [
                '+sub[hoge=1 OR fuga=2]' => $db->select('test', ['sub1.id = test.id']),
            ],
            '+sss[hoge=1]' => $db->select('test', ['sub1.id = test.id']),
        ]);
        $this->assertQuery("SELECT test.* FROM test INNER JOIN (SELECT test.* FROM test WHERE sub1.id = test.id) sub ON hoge=1 OR fuga=2 INNER JOIN (SELECT test.* FROM test WHERE sub1.id = test.id) sss ON hoge=1", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_join_implicit($builder)
    {
        $builder->column([
            'foreign_p P.*'    => [],
            '~foreign_c1 C1.*' => [],
            '~foreign_c2 C2.*' => [],
        ]);
        $this->assertQuery("SELECT P.*, C1.*, C2.* FROM foreign_p P LEFT JOIN foreign_c1 C1 ON C1.id = P.id LEFT JOIN foreign_c2 C2 ON C2.cid = P.id", $builder);

        $this->assertQuery("SELECT C1.*, C2.* FROM foreign_c1 C1 INNER JOIN foreign_c2 C2 ON 1", $builder->column([
            '+foreign_c1 C1.*' => [],
            '+foreign_c2 C2.*' => [],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_innerJoinOn_table($builder)
    {
        $builder->resetQueryPart();
        $builder->select('*')->from('t_table1')->innerJoinOn('t_join1', ['t_join1.hoge = t_table1.fuga']);
        $this->assertQuery('SELECT * FROM t_table1 INNER JOIN t_join1 ON t_join1.hoge = t_table1.fuga', $builder);

        $builder->resetQueryPart();
        $builder->select('*')->from('t_table1')->innerJoinOn('t_join1', 'TRUE')->innerJoinOn('t_join2', 'TRUE');
        $this->assertQuery('SELECT * FROM t_table1 INNER JOIN t_join1 ON TRUE INNER JOIN t_join2 ON TRUE', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_innerJoinOn_alias($builder)
    {
        $builder->resetQueryPart();
        $builder->select('*')->from('t_table1')->innerJoinOn('t_join1 as alias', ['alias.hoge = t_table1.fuga']);
        $this->assertQuery('SELECT * FROM t_table1 INNER JOIN t_join1 alias ON alias.hoge = t_table1.fuga', $builder);

        $builder->resetQueryPart();
        $builder->select('*')->from('t_table1')->innerJoinOn('t_join1 alias', ['A.hoge = B.fuga']);
        $this->assertQuery('SELECT * FROM t_table1 INNER JOIN t_join1 alias ON A.hoge = B.fuga', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_innerJoinOn_object($builder)
    {
        $builder->reset()->select('*')->from('test1')->innerJoinOn('test2', new Expression('test2.hoge_flg'));
        $this->assertQuery("SELECT * FROM test1 INNER JOIN test2 ON test2.hoge_flg", $builder);
        $this->assertEquals([], $builder->getParams());

        $s = $builder->getDatabase()->select('test2', ['id' => 1])->exists();
        $builder->reset()->select('*')->from('test1')->innerJoinOn('test2', $s);
        $this->assertQuery("SELECT * FROM test1 INNER JOIN test2 ON ($s)", $builder);
        $this->assertEquals([1], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_innerJoinOn_exec($builder)
    {
        $builder->select('*')->from('test1')->innerJoinOn('test2', ['name2' => new Expression('UPPER(name1)')]);

        $rows = $builder->getDatabase()->fetchArray($builder);

        // 10件のはず
        $this->assertCount(10, $rows);

        // name1 と name2 の違いは大文字小文字だけのはず
        foreach ($rows as $row) {
            $this->assertEquals(strtoupper($row['name1']), $row['name2']);
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_autoJoinOn($builder)
    {
        // 親~子は常に LEFT
        $builder->column([
            'foreign_s S.*' => [
                '~foreign_sc:fk_sc1 SC1',
                '~foreign_sc:fk_sc2 SC2',
            ],
        ]);
        $this->assertQuery('SELECT S.* FROM foreign_s S LEFT JOIN foreign_sc SC1 ON SC1.s_id1 = S.id LEFT JOIN foreign_sc SC2 ON SC2.s_id2 = S.id', $builder);

        // 子~親（nullable）は LEFT
        $builder->column([
            'foreign_sc SC2.*' => [
                '~foreign_s:fk_sc2 S',
            ],
        ]);
        $this->assertQuery('SELECT SC2.* FROM foreign_sc SC2 LEFT JOIN foreign_s S ON S.id = SC2.s_id2', $builder);

        // 子~親（notnull）は INNER
        $builder->column([
            'foreign_sc SC1.*' => [
                '~foreign_s:fk_sc1 S',
            ],
        ]);
        $this->assertQuery('SELECT SC1.* FROM foreign_sc SC1 INNER JOIN foreign_s S ON S.id = SC1.s_id1', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_leftJoinOn($builder)
    {
        $builder->resetQueryPart();
        $builder->select('*')->from('t_table1')->leftJoinOn('t_join1', 'hoge = 1');
        $this->assertQuery('SELECT * FROM t_table1 LEFT JOIN t_join1 ON hoge = 1', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_rightJoinOn($builder)
    {
        $builder->resetQueryPart();
        $builder->select('*')->from('t_table1')->rightJoinOn('t_join1', 'hoge = 1');
        $this->assertQuery('SELECT * FROM t_table1 RIGHT JOIN t_join1 ON hoge = 1', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_joinForeign($builder)
    {
        $fk1 = new ForeignKeyConstraint(['gg_id'], 'gg1', ['gg_id'], 'fk_gg12');
        $fk2 = new ForeignKeyConstraint(['gg_id'], 'gg2', ['gg_id'], 'fk_gg21');
        $smanager = $builder->getDatabase()->getConnection()->createSchemaManager();
        try_return([$smanager, 'dropForeignKey'], $fk1, 'gg2');
        try_return([$smanager, 'dropForeignKey'], $fk2, 'gg1');
        self::createTables($builder->getDatabase()->getConnection(), [
            new Table('ppp',
                [
                    new Column('id', Type::getType('integer')),
                    new Column('seq', Type::getType('integer')),
                ],
                [
                    new Index('PRIMARY', ['id', 'seq'], true, true),
                    new Index('UINDEX', ['id'], true, false),
                ]
            ),
            new Table('ccc',
                [
                    new Column('id', Type::getType('integer')),
                    new Column('seq', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['id', 'seq'], true, true)],
                [],
                [new ForeignKeyConstraint(['id', 'seq'], 'ppp', ['id', 'seq'], 'fkey1')]
            ),
            new Table('fff',
                [
                    new Column('fff_id', Type::getType('integer')),
                    new Column('fff_seq', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['fff_id', 'fff_seq'], true, true)],
                [],
                [new ForeignKeyConstraint(['fff_id', 'fff_seq'], 'ppp', ['id', 'seq'], 'fkey2')]
            ),
            new Table('mmm',
                [
                    new Column('mmm_id1', Type::getType('integer')),
                    new Column('mmm_id2', Type::getType('integer')),
                    new Column('seq', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['mmm_id1', 'mmm_id2'], true, true)],
                [],
                [
                    new ForeignKeyConstraint(['mmm_id1', 'seq'], 'ppp', ['id', 'seq'], 'fkey3'),
                    new ForeignKeyConstraint(['mmm_id2', 'seq'], 'ppp', ['id', 'seq'], 'fkey4'),
                ]
            ),
            new Table('gg1',
                [
                    new Column('gg_id', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['gg_id'], true, true)]
            ),
            new Table('gg2',
                [
                    new Column('gg_id', Type::getType('integer')),
                ],
                [new Index('PRIMARY', ['gg_id'], true, true)]
            ),
        ]);
        $builder->getDatabase()->getConnection()->createSchemaManager()->createForeignKey($fk2, 'gg1');
        $builder->getDatabase()->getConnection()->createSchemaManager()->createForeignKey($fk1, 'gg2');

        $builder->getDatabase()->getSchema()->refresh();

        // シンプルな ON
        $builder->reset()->column('ppp P')->autoJoinForeign('ccc C');
        $this->assertQuery('SELECT P.* FROM ppp P LEFT JOIN ccc C ON (C.id = P.id) AND (C.seq = P.seq)', $builder);

        // 結合順を入れ替えてもそれは変わらないはず
        $builder->reset()->column('ccc C')->innerJoinForeign('ppp P');
        $this->assertQuery('SELECT C.* FROM ccc C INNER JOIN ppp P ON (P.id = C.id) AND (P.seq = C.seq)', $builder);

        // カラムが違うなら ON が使われるはず
        $builder->reset()->column('ppp P')->leftJoinForeign('fff F');
        $this->assertQuery('SELECT P.* FROM ppp P LEFT JOIN fff F ON (F.fff_id = P.id) AND (F.fff_seq = P.seq)', $builder);

        // 結合順を入れ替えてもそれは変わらないはず
        $builder->reset()->column('fff F')->rightJoinForeign('ppp P');
        $this->assertQuery('SELECT F.* FROM fff F RIGHT JOIN ppp P ON (P.id = F.fff_id) AND (P.seq = F.fff_seq)', $builder);

        // joinForeignOn で外部キー＋ONされるはず
        $builder->reset()->column('ccc C')->autoJoinForeignOn('ppp P', '1=1');
        $this->assertQuery('SELECT C.* FROM ccc C INNER JOIN ppp P ON (P.id = C.id) AND (P.seq = C.seq) AND (1=1)', $builder);

        // ccc, ppp, fff という順番でも ppp<->fff が結合されるはず
        $builder->reset()->column('ccc C')->leftJoinForeignOn('ppp P', '1=1')->rightJoinForeignOn('fff F', '2=2');
        $this->assertQuery('SELECT C.* FROM ccc C LEFT JOIN ppp P ON (P.id = C.id) AND (P.seq = C.seq) AND (1=1) RIGHT JOIN fff F ON (F.fff_id = P.id) AND (F.fff_seq = P.seq) AND (2=2)', $builder);

        // テーブル・外部キーの指定で結合が変わるはず
        $builder->reset()->column('ccc C')->joinOn('fff F', 'TRUE')->innerJoinForeign('ppp P');
        $this->assertQuery('SELECT C.* FROM ccc C INNER JOIN fff F ON TRUE INNER JOIN ppp P ON (P.id = F.fff_id) AND (P.seq = F.fff_seq)', $builder);
        $builder->reset()->column('ccc C')->joinOn('fff F', 'TRUE')->innerJoinForeign('ppp P', null, 'C');
        $this->assertQuery('SELECT C.* FROM ccc C INNER JOIN fff F ON TRUE INNER JOIN ppp P ON (P.id = C.id) AND (P.seq = C.seq)', $builder);
        $builder->reset()->column('ccc C')->leftJoinOn('fff F', 'TRUE')->innerJoinForeign('ppp P', 'fkey1');
        $this->assertQuery('SELECT C.* FROM ccc C LEFT JOIN fff F ON TRUE INNER JOIN ppp P ON (P.id = C.id) AND (P.seq = C.seq)', $builder);
        $builder->reset()->column('ccc C')->rightJoinOn('fff F', 'TRUE')->innerJoinForeign('ppp P', 'fkey2');
        $this->assertQuery('SELECT C.* FROM ccc C RIGHT JOIN fff F ON TRUE INNER JOIN ppp P ON (P.id = F.fff_id) AND (P.seq = F.fff_seq)', $builder);

        // 相互参照の外部キーでも名前を指定すれば結合されるはず
        $builder->reset()->column('gg1')->innerJoinForeign('gg2 P', 'fk_gg12');
        $this->assertQuery('SELECT gg1.* FROM gg1 INNER JOIN gg2 P ON P.gg_id = gg1.gg_id', $builder);

        // ccc と fff に外部キー関係はないので例外が投がるはず
        $builder->reset()->column('ccc C');
        $this->assertException(new \UnexpectedValueException("'aaa' is not exists"), L($builder)->innerJoinForeign('fff F', 'aaa'));

        // 同じテーブルへの複数の外部キーは曖昧なので例外が投がるはず
        $builder->reset()->column('ppp P');
        $this->assertException(new \UnexpectedValueException('ambiguous foreign key'), L($builder)->innerJoinForeign('mmm'));

        // 名前未指定の相互参照の外部キーは曖昧なので例外が投がるはず
        $builder->reset()->column('gg1');
        $this->assertException(new \UnexpectedValueException('ambiguous foreign key'), L($builder)->innerJoinForeign('gg2'));

        // 存在しない外部キー名は例外が投がるはず
        $builder->reset()->column('gg1');
        $this->assertException(new \UnexpectedValueException('foreign key \'notfkey\' is not exists'), L($builder)->innerJoinForeign('gg2', 'notfkey'));
    }

    function test_joinIndirect()
    {
        $schmer = self::getDummyDatabase()->getConnection()->createSchemaManager();
        try_return([$schmer, 'dropTable'], 't_root');
        $schmer->createTable(new Table(
            't_root',
            [
                new Column('root_id', Type::getType('integer')),
                new Column('seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['root_id', 'seq'], true, true)]
        ));
        try_return([$schmer, 'dropTable'], 't_inner1');
        $schmer->createTable(new Table(
            't_inner1',
            [
                new Column('inner1_id', Type::getType('integer')),
                new Column('root1_id', Type::getType('integer')),
                new Column('root1_seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['inner1_id'], true, true)],
            [],
            [new ForeignKeyConstraint(['root1_id', 'root1_seq'], 't_root', ['root_id', 'seq'], 'fk_inner1')]
        ));
        try_return([$schmer, 'dropTable'], 't_inner2');
        $schmer->createTable(new Table(
            't_inner2',
            [
                new Column('inner2_id', Type::getType('integer')),
                new Column('root2_id', Type::getType('integer')),
                new Column('root2_seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['inner2_id'], true, true)],
            [],
            [new ForeignKeyConstraint(['root2_id', 'root2_seq'], 't_root', ['root_id', 'seq'], 'fk_inner2')]
        ));
        try_return([$schmer, 'dropTable'], 't_leaf');
        $schmer->createTable(new Table(
            't_leaf',
            [
                new Column('leaf_id', Type::getType('integer')),
                new Column('leaf_inner1_id', Type::getType('integer')),
                new Column('leaf_inner2_id', Type::getType('integer')),
                new Column('leaf_root_id', Type::getType('integer')),
                new Column('leaf_root_seq', Type::getType('integer')),
            ],
            [new Index('PRIMARY', ['leaf_id'], true, true)],
            [],
            [
                new ForeignKeyConstraint(['leaf_inner1_id', 'leaf_root_id', 'leaf_root_seq'], 't_inner1', ['inner1_id', 'root1_id', 'root1_seq'], 'fk_leaf1'),
                new ForeignKeyConstraint(['leaf_inner2_id', 'leaf_root_id', 'leaf_root_seq'], 't_inner2', ['inner2_id', 'root2_id', 'root2_seq'], 'fk_leaf2'),
            ]
        ));

        self::getDummyDatabase()->getSchema()->refresh();

        $this->assertStringIgnoreBreak('
SELECT t_leaf.*
FROM t_leaf
INNER JOIN t_root ON (t_root.root_id = t_leaf.leaf_root_id) AND (t_root.seq = t_leaf.leaf_root_seq)
', self::getDummyDatabase()->select([
            't_leaf.*' => ['+t_root' => []],
        ]));

        $this->assertStringIgnoreBreak('
SELECT t_root.*
FROM t_root
INNER JOIN t_leaf ON (t_leaf.leaf_root_id = t_root.root_id) AND (t_leaf.leaf_root_seq = t_root.seq)
', self::getDummyDatabase()->select([
            't_root.*' => ['+t_leaf' => []],
        ]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_wrap($builder, $database)
    {
        if (self::supportSyntax($database, 'SELECT EXISTS(SELECT 1)')) {
            $builder->select('*')->from('test1')->wrap('SELECT EXISTS');
            $this->assertQuery('SELECT EXISTS (SELECT * FROM test1)', $builder);
            $builder->array();
        }

        $builder->detectAutoOrder(false);
        $builder->reset()->select('*')->from('test1')->wrap('HOGE', 'FUGA');
        $this->assertQuery('HOGE (SELECT * FROM test1) FUGA', $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_exists($builder, $database)
    {
        if (self::supportSyntax($database, 'SELECT EXISTS(SELECT * FROM test)')) {
            $this->assertEquals(1, $builder->getDatabase()->fetchValue("SELECT " . $builder->reset()->column('test1')->exists()));
            $this->assertEquals(0, $builder->getDatabase()->fetchValue("SELECT " . $builder->reset()->column('noauto')->exists()));
        }
        if (self::supportSyntax($database, 'SELECT NOT EXISTS(SELECT 1)')) {
            $this->assertEquals(0, $builder->getDatabase()->fetchValue("SELECT " . $builder->reset()->column('test1')->notExists()));
            $this->assertEquals(1, $builder->getDatabase()->fetchValue("SELECT " . $builder->reset()->column('noauto')->notExists()));
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setSubmethod($builder)
    {
        $this->assertQuery('', $builder->reset()->column('foreign_p P')->getSubmethod());
        $this->assertQuery('max', $builder->reset()->column('foreign_p P')->setSubmethod('max')->getSubmethod());
        $this->assertQuery('', $builder->setSubmethod(null)->getSubmethod());

        $builder->reset();
        $this->assertException('submethod is invalid', L($builder)->setSubmethod(123));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setSubwhere_fkey($builder)
    {
        $builder->reset()->column('foreign_p P')->setSubmethod(true)->setSubwhere('foreign_c1', 'C');
        $this->assertQuery('EXISTS (SELECT * FROM foreign_p P WHERE P.id = C.id)', $builder);

        $builder->reset()->column(['foreign_p P' => []])->setSubmethod('query')->setSubwhere('foreign_c1', 'C');
        $this->assertQuery('SELECT P.id FROM foreign_p P WHERE P.id = C.id', $builder);

        $builder->reset()->column('foreign_p P')->setSubwhere('foreign_c1', 'C');
        $this->assertQuery('NOT EXISTS (SELECT * FROM foreign_p P WHERE P.id = C.id)', $builder->notExists());
        $builder->reset()->column('foreign_p P')->setSubwhere('foreign_c2');
        $this->assertQuery('NOT EXISTS (SELECT * FROM foreign_p P WHERE P.id = foreign_c2.cid)', $builder->notExists());

        // 省略した場合は例外は飛ばないが、明示的に指定してかつ存在しない場合は例外が飛ぶ
        $this->assertFalse($builder->reset()->column('test1 T1')->setSubwhere('test2', 'T1'));
        $this->assertException(new \UnexpectedValueException('has not foreign key'), function () use ($builder) {
            $builder->reset()->column('test1 T1')->setSubwhere('test2', 'T1', 'hoge');
        });

        // 相互外部キーは引数に依らず例外が飛ぶ
        $this->assertException(new \UnexpectedValueException('ambiguous foreign key'), function () use ($builder) {
            $builder->reset()->column('foreign_d1 D1')->setSubwhere('foreign_d2', 'D2');
        });

        // 外部キーを明示すればOK
        $builder->reset()->column('foreign_d1 D1')->setSubwhere('foreign_d2', 'D2', 'fk_dd12');
        $this->assertQuery('EXISTS (SELECT * FROM foreign_d1 D1 WHERE D1.d2_id = D2.id)', $builder->exists());
        $builder->reset()->column('foreign_d1 D1')->setSubwhere('foreign_d2', 'D2', 'fk_dd21');
        $this->assertQuery('EXISTS (SELECT * FROM foreign_d1 D1 WHERE D1.id = D2.id)', $builder->exists());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setSubwhere_cond($builder)
    {
        $builder->reset()->column('test1{id1: id2} T1')->setSubwhere('test2', 'T2');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1 WHERE T1.id1 = T2.id2)', $builder->notExists());
        $builder->reset()->column('test1[id1=id2] T1')->setSubwhere('test2', 'T2');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1 WHERE id1=id2)', $builder->notExists());

        // 省略した場合は例外は飛ばないが、明示的に指定してかつ存在しない場合は例外が飛ぶ
        $this->assertFalse($builder->reset()->column('test1 T1')->setSubwhere('test2', 'T1'));
        $this->assertException(new \UnexpectedValueException('has not foreign key'), function () use ($builder) {
            $builder->reset()->column('test1 T1')->setSubwhere('test2', 'T1', 'hoge');
        });
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setSubwhere_same($builder)
    {
        $builder->column('test1{id1: id2} T1');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1)', $builder->notExists());

        $builder->setSubwhere('test2', 'T2');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1 WHERE T1.id1 = T2.id2)', $builder->notExists());
        $builder->setSubwhere('test2', 'T2');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1 WHERE T1.id1 = T2.id2)', $builder->notExists());

        $builder->reset()->column('test1{id1: id2} T1');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1)', $builder->notExists());

        $builder->setSubwhere('test2', 'T2');
        $this->assertQuery('NOT EXISTS (SELECT * FROM test1 T1 WHERE T1.id1 = T2.id2)', $builder->notExists());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_operatize($builder, $database)
    {
        $builder->reset()->operatize('=', 99);
        $this->assertInstanceOf(Operator::class, $builder->getQueryPart('operator'));
        $builder->operatize(null);
        $this->assertNull($builder->getQueryPart('operator'));
        $builder->reset()->operatize('=', 99)->reset();
        $this->assertNull($builder->getQueryPart('operator'));

        $builder->reset()->column('foreign_p P')->where([
            'id' => 1,
            $database->subcount('foreign_c1')->operatize('= 10'),
            $database->submax('foreign_c2.cid')->operatize('>= ?', 20),
            $database->subsum('foreign_c2.seq')->operatize('BETWEEN', [30, 40]),
        ]);
        $qi = function ($str) use ($database) {
            return $database->getPlatform()->quoteSingleIdentifier($str);
        };
        $this->assertQuery("SELECT P.* FROM foreign_p P WHERE (id = ?)
AND ((SELECT COUNT(*) AS {$qi('*@count')} FROM foreign_c1 WHERE foreign_c1.id = P.id) = 10)
AND ((SELECT MAX(foreign_c2.cid) AS {$qi('foreign_c2.cid@max')} FROM foreign_c2 WHERE foreign_c2.cid = P.id) >= ?)
AND ((SELECT SUM(foreign_c2.seq) AS {$qi('foreign_c2.seq@sum')} FROM foreign_c2 WHERE foreign_c2.cid = P.id) BETWEEN ? AND ?)"
            , $builder);
        $this->assertEquals([1, 20, 30, 40], $builder->getParams());

    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_aggregate($builder)
    {
        $builder->reset()->column('test.id')->aggregate(['min', 'max']);
        $selects = $builder->getQueryPart('select');
        $this->assertEquals('MIN(test.id) AS test.id@min', $selects[0]);
        $this->assertEquals('MAX(test.id) AS test.id@max', $selects[1]);

        $builder->reset()->column('test')->aggregate([
            'year_2016' => 'SUM(YEAR(login_at) = "2016")',
            'year_2017' => new Expression('SUM(YEAR(login_at) = ?)', '2017'),
            'year_2018' => ['SUM(YEAR(login_at) = ?)' => '2018'],
        ]);
        $selects = $builder->getQueryPart('select');
        $this->assertEquals('SUM(YEAR(login_at) = "2016") AS year_2016', $selects[0]);
        $this->assertEquals('SUM(YEAR(login_at) = ?) AS year_2017', $selects[1]);
        $this->assertEquals('SUM(YEAR(login_at) = ?) AS year_2018', $selects[2]);
        $this->assertEquals([2017, 2018], $builder->getParams());

        $builder->reset()->column('test')->aggregate([
            'SUM(YEAR(login_at) = ?)' => [2016, 2017, 'x' => 2018],
        ]);
        $selects = $builder->getQueryPart('select');
        $this->assertEquals('SUM(YEAR(login_at) = ?) AS  2016', $selects[0]);
        $this->assertEquals('SUM(YEAR(login_at) = ?) AS  2017', $selects[1]);
        $this->assertEquals('SUM(YEAR(login_at) = ?) AS x', $selects[2]);
        $this->assertEquals([2016, 2017, 2018], $builder->getParams());

        $this->assertException(new \InvalidArgumentException('is empty'), L($builder->column('test'))->aggregate([]));
        $this->assertException(new \InvalidArgumentException('length is over 1'), L($builder->column('test.id,name'))->aggregate('count', 1));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_hint($builder)
    {
        $builder->reset()->column('test1, test2 U')->hint('thisishint1')->hint('thisishint2', 'U');
        $this->assertEquals('SELECT test1.*, U.* FROM test1 thisishint1, test2 U thisishint2', (string) $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_lockInShare($builder)
    {
        $platform = $builder->getDatabase()->getPlatform();
        $lock = trim($platform->getReadLockSQL());
        $lock = $lock ?: trim($platform->appendLockHint('', LockMode::PESSIMISTIC_READ));
        $builder->select('*')->from('test1')->lockInShare();
        $this->assertStringContainsString(' ' . $lock, (string) $builder);
        $builder->array();
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_lockForUpdate($builder)
    {
        $platform = $builder->getDatabase()->getPlatform();
        $lock = trim($platform->getWriteLockSQL());
        $lock = $lock ?: trim($platform->appendLockHint('', LockMode::PESSIMISTIC_WRITE));
        $builder->select('*')->from('test1')->lockForUpdate();
        $this->assertStringContainsString(' ' . $lock, (string) $builder);
        $builder->array();

        if ($builder->getDatabase()->getCompatiblePlatform()->appendLockSuffix('dummy', 'forupdate', '') !== 'dummy') {
            $builder->select('*')->from('test1')->lockForUpdate('SKIP LOCKED');
            $this->assertStringContainsString(' ' . $lock . ' SKIP LOCKED', (string) $builder);
        }
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_unlock($builder)
    {
        $builder->select('*')->from('test1')->lockForUpdate('hoge')->unlock();
        $this->assertQuery('SELECT * FROM test1', $builder);
        $builder->array();
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setDefaultOrder($builder)
    {
        $builder->detectAutoOrder(true);

        $builder->reset()->setDefaultOrder(true);
        $this->assertQuery("SELECT test1.id FROM test1 ORDER BY test1.id ASC", $builder->column('test1.id'));

        $builder->reset()->setDefaultOrder(false);
        $this->assertQuery("SELECT test1.id FROM test1 ORDER BY test1.id DESC", $builder->column('test1.id'));

        $builder->reset()->setDefaultOrder('NOW()');
        $this->assertQuery("SELECT test1.id FROM test1 ORDER BY NOW() ASC", $builder->column('test1.id'));

        $builder->reset()->setDefaultOrder(new Expression('NOW() DESC'));
        $this->assertQuery("SELECT test1.id FROM test1 ORDER BY NOW() DESC", $builder->column('test1.id'));

        $builder->detectAutoOrder(false);
        $this->assertQuery("SELECT test1.id FROM test1", $builder->column('test1.id'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setAutoOrder($builder)
    {
        $builder->setAutoOrder(false);
        $builder->detectAutoOrder(true);
        $this->assertQuery("SELECT test1.id FROM test1", $builder->column('test1.id'));

        $builder->setAutoOrder(true);
        $builder->detectAutoOrder(true);
        $this->assertQuery("SELECT test1.id FROM test1 ORDER BY test1.id ASC", $builder->column('test1.id'));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_setAutoTablePrefix($builder)
    {
        $q1 = $builder->getDatabase()->getPlatform()->quoteSingleIdentifier('test1.id');
        $q2 = $builder->getDatabase()->getPlatform()->quoteSingleIdentifier('test1.aid');
        $builder->column(['test1' => ['id', 'aid' => 'id']]);

        $builder->setAutoTablePrefix(true);
        $this->assertQuery("SELECT test1.id AS $q1, test1.id AS $q2 FROM test1", $builder);

        $builder->setAutoTablePrefix(false);
        $this->assertQuery("SELECT test1.id, test1.id AS aid FROM test1", $builder);
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_cache($builder)
    {
        $builder->cache(10)->cache(false);
        $this->assertEquals(0, $builder->getCacheTtl());

        $builder->cache(10)->column(['test' => 'name'])->where(['id' => [1, 2]])->orderBy(['name' => false]);

        $array = $builder->array();
        $builder->getDatabase()->update('test', ['name' => 'Z1'], ['id' => 1]);

        // キャッシュが効く
        $this->assertEquals($array, $builder->array());
        // orThrow を付けても大丈夫
        $this->assertEquals($array, $builder->arrayOrThrow());
        // 異なるメソッドでも効く
        $this->assertEquals($array, array_values($builder->assoc()));
        // クエリが異なれば効かない
        $this->assertNotEquals($array, (clone $builder)->orWhere(['id' => 3])->array());

        // ロック中は・・・
        $builder->lockForUpdate();

        $array = $builder->array();
        $builder->getDatabase()->update('test', ['name' => 'Z2'], ['id' => 2]);

        // 一切効かない
        $this->assertNotEquals($array, $builder->array());
        $this->assertNotEquals($array, $builder->arrayOrThrow());
        $this->assertNotEquals($array, array_values($builder->assoc()));
        $this->assertNotEquals($array, (clone $builder)->orWhere(['id' => 3])->array());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     * @param Database $database
     */
    function test_cache_sub($builder, $database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 1, 'name' => 'c1name11']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 2, 'name' => 'c1name12']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 1, 'name' => 'c2name11']);
        $database->insert('foreign_c2', ['cid' => 1, 'seq' => 2, 'name' => 'c2name12']);

        $builder->column([
            'foreign_p' => [
                '*',
                'foreign_c1 c1' => ['*'],
                'foreign_c2 c2' => ['*'],
            ],
        ])->where(['foreign_p.id' => 1])->cache(10);

        $array = $builder->array();
        $database->destroy('foreign_p', ['id' => 1]);

        $this->assertEquals($array, $builder->array());
        $this->assertEquals($array, $builder->arrayOrThrow());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_parameter($builder)
    {
        // 追加順は関係なく区毎の順番で返ってくるはず
        $builder->where(['?' => 4]);
        $builder->select(new Expression('?', 1), new Expression('?', 2));
        $builder->having(['?' => 5]);
        $builder->from(new Expression('?', 3));
        $this->assertEquals([1, 2, 3, 4, 5], $builder->getParams());
        $this->assertEquals([1, 2], $builder->getParams('select'));
        $this->assertEquals([3], $builder->getParams('from'));
        $this->assertEquals([4], $builder->getParams('where'));
        $this->assertEquals([5], $builder->getParams('having'));

        // 順番が明示されていれば resetQueryPart で吹き飛ぶはず
        $builder->resetQueryPart('select');
        $builder->resetQueryPart('where');
        $this->assertEquals([3, 5], $builder->getParams());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_getSql_select($builder)
    {
        // limit や groupBy や exists すると自動オーダーは無効になるはず
        $this->assertQuery('SELECT test1.* FROM test1', $builder->reset()->column('test1'));
        $this->assertStringNotContainsString('ORDER BY id', (string) $builder->reset()->column('test1')->limit(1));
        $this->assertQuery('SELECT test1.id FROM test1 GROUP BY id', $builder->reset()->column('test1.id')->groupBy('id'));
        $this->assertStringNotContainsString('ORDER BY id', (string) $builder->reset()->column('test1')->exists());

        // 識別子は可能な限りクオートされるはず
        $NULL = $builder->getDatabase()->getCompatiblePlatform()->getWrappedPlatform()->quoteSingleIdentifier('NULL');
        $this->assertQuery("SELECT test.id AS $NULL FROM test", $builder->reset()->column(['test' => ['NULL' => 'id']]));
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_queryInto($builder)
    {
        $builder->column('test T')->where(['T.id' => '123']);
        $this->assertEquals("SELECT T.* FROM test T WHERE T.id = '123'", $builder->queryInto());
    }

    /**
     * @dataProvider provideQueryBuilder
     * @param QueryBuilder $builder
     */
    function test_injectChildColumn($builder)
    {
        $builder->setInjectChildColumn(true);

        $builder->addColumn([
            'foreign_p P' => [
                'foreign_c1 C1'         => ['*'],
                'foreign_c2[seq: 1] C2' => [
                    'seq',
                    'now' => new Expression('NOW(?)', 1),
                ],
            ],
        ]);
        $query = $builder->queryInto();
        $this->assertStringContainsString('-- (SELECT C1.* FROM foreign_c1 C1 WHERE C1.id IN ([parent.P.id])) AS C1', $query);
        $this->assertStringContainsString("-- (SELECT C2.seq, NOW('1') AS now FROM foreign_c2 C2 WHERE (C2.seq = '1') AND (C2.cid IN ([parent.P.id]))) AS C2", $query);

        $builder->getDatabase()->insert('g_ancestor', [
            'ancestor_id'   => 1,
            'ancestor_name' => 'name1',
        ]);
        $builder->getDatabase()->insert('g_parent', [
            'parent_id'   => 1,
            'parent_name' => 'name1',
            'ancestor_id' => 1,
        ]);
        $builder->getDatabase()->insert('g_child', [
            'child_id'   => 1,
            'child_name' => 'name1',
            'parent_id'  => 1,
        ]);

        $builder->reset()->addColumn([
            'g_ancestor A' => [
                'a' => $builder->getDatabase()->subexists('g_parent'),
                'P' => $builder->getDatabase()->subselectTuple([
                    'g_parent' => [
                        'a' => $builder->getDatabase()->subexists('g_child'),
                        'C' => $builder->getDatabase()->subselectTuple([
                            'g_child' => [
                                'a' => $builder->getDatabase()->subexists('g_parent'),
                            ],
                        ], ['child_id' => [1, 2, 3]]),
                    ],
                ], ['parent_id' => [1, 2, 3], $builder->getDatabase()->subexists('g_child')]),
            ],
        ]);
        $this->assertEquals([
            'a' => '1',
            'P' => [
                'a' => '1',
                'C' => [
                    'a' => '1',
                ],
            ],
        ], $builder->tuple());
    }

    public static function assertQuery($expected, $actual, $message = '')
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        // 何回か文字列化しても気持ち的に大丈夫なことを担保（実際は結構乱れたりする）
        $dummy = (string) $actual;
        self::assertStringIgnoreBreak($expected, (string) $actual, $message);
    }
}
