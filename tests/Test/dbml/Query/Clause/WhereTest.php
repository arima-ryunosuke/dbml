<?php

namespace ryunosuke\Test\dbml\Query\Clause;

use ryunosuke\dbml\Query\Clause\Where;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\Test\Database;

class WhereTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_ignore($database)
    {
        $params = [];
        $filtered = null;
        $where = Where::build($database, [
            '!rid11'   => 11,
            '!rid12'   => 12,
            '!id11'    => null,
            '!id12'    => '',
            '!id13'    => [],
            [
                '!rid21' => 21,
                '!rid22' => 22,
                '!id21'  => null,
                '!id22'  => '',
                '!id23'  => [],
                [
                    '!rid31' => 31,
                    '!rid32' => 32,
                    '!id31'  => null,
                    '!id32'  => '',
                    '!id33'  => [],
                ],
            ],
            '!id9:!IN' => null,
            '!ids:[~]' => [null, null],
            '!str'     => Operator::likeIn(''), // 意図的
            '!query'   => $database->select('test'),
            '!exists'  => $database->select('test', [
                '!piyo:[~]' => [null, null],
            ]),
        ], $params, 'OR', $filtered);
        // '!' 付きで空値はシカトされている
        $this->assertEquals([
            'rid11 = ?',
            'rid12 = ?',
            '(rid21 = ?) OR (rid22 = ?) OR ((rid31 = ?) AND (rid32 = ?))',
            'str LIKE ?',
            'query IN (SELECT test.* FROM test)',
        ], $where);
        // '!' 付きで空値はバインドされない
        $this->assertEquals([11, 12, 21, 22, 31, 32, '%%'], $params);
        // フィルタ結果が格納される
        $this->assertEquals(false, $filtered);

        $params = [];
        $filtered = null;
        $where = Where::build($database, [
            '!null'  => null,
            '!blank' => '',
            '!empty' => [],
            [
                '!null'  => null,
                '!blank' => '',
                '!empty' => [],
                [
                    '!null'  => null,
                    '!blank' => '',
                    '!empty' => [],
                ],
            ],
        ], $params, 'OR', $filtered);
        $this->assertEquals([], $where);
        $this->assertEquals([], $params);
        $this->assertEquals(true, $filtered);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_closure($database)
    {
        $wheres = Where::build($database, [
            // 数値キーで配列を返す
            function () { return ['A', 'B']; },
            // 数値キーで連想配列を返す
            function () { return ['a' => 'A', 'b' => 'B']; },
            // 数値キーで非配列を返す
            function () { return 'this is cond.'; },
            // 数値キーでクエリビルダを返す
            function (Database $db) { return $db->select('test1')->exists(); },
            // 数値キーで空値を返す
            function () { return null; },
            function () { return ''; },
            function () { return []; },
            // 文字キーで配列を返す
            'columnA'  => function () { return ['Y', 'Z']; },
            // 文字キーで連想配列を返す
            'columnH'  => function () { return ['y' => 'Y', 'z' => 'Z']; },
            // 文字キーで非配列を返す
            'columnC'  => function () { return 'this is cond.'; },
            // 文字キーで空値を返す
            'empty1'   => function () { return null; },
            'empty2'   => function () { return ''; },
            'empty3'   => function () { return []; },
            // !文字キーで空値を返す
            '!iempty1' => function () { return null; },
            '!iempty2' => function () { return ''; },
            '!iempty3' => function () { return []; },
            // 文字キーでクエリビルダを返す
            'subquery' => function (Database $db) { return $db->select('test2'); },
        ], $params);
        $this->assertEquals([
            '(A) OR (B)',
            '(a = ?) OR (b = ?)',
            'this is cond.',
            '(EXISTS (SELECT * FROM test1))',
            'columnA IN (?,?)',
            'columnH IN (?,?)',
            'columnC = ?',
            'empty1 IS NULL',
            'empty2 = ?',
            'FALSE',
            'subquery IN (SELECT test2.* FROM test2)',
        ], $wheres);
        $this->assertEquals([
            'A',
            'B',
            'Y',
            'Z',
            'Y',
            'Z',
            'this is cond.',
            '',
        ], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_flipflop($database)
    {
        $nesting = [
            'condA' => 1,
            [
                'condB1' => 21,
                'condB2' => 22,
            ],
            'condC' => 3,
            [
                'condD1' => 41,
                [
                    'condD21' => 421,
                    'condD22' => 422,
                ],
                'condD3' => 42,
            ],
            'AND'   => [
                'condE1' => 51,
                [
                    'condE21' => 521,
                    'condE22' => 522,
                ],
                'condE3' => 52,
            ],
        ];

        $this->assertEquals([
            'condA = ?',
            '(condB1 = ?) OR (condB2 = ?)',
            'condC = ?',
            '(condD1 = ?) OR ((condD21 = ?) AND (condD22 = ?)) OR (condD3 = ?)',
            '(condE1 = ?) AND ((condE21 = ?) OR (condE22 = ?)) AND (condE3 = ?)',
        ], Where::build($database, $nesting));

        $this->assertExpression(Where::and($nesting)($database), '(condA = ?) AND ((condB1 = ?) OR (condB2 = ?)) AND (condC = ?) AND ((condD1 = ?) OR ((condD21 = ?) AND (condD22 = ?)) OR (condD3 = ?)) AND ((condE1 = ?) AND ((condE21 = ?) OR (condE22 = ?)) AND (condE3 = ?))', [
            1,
            21,
            22,
            3,
            41,
            421,
            422,
            42,
            51,
            521,
            522,
            52,
        ]);

        $this->assertExpression(Where::or($nesting)($database), '(condA = ?) OR ((condB1 = ?) AND (condB2 = ?)) OR (condC = ?) OR ((condD1 = ?) AND ((condD21 = ?) OR (condD22 = ?)) AND (condD3 = ?)) OR ((condE1 = ?) AND ((condE21 = ?) OR (condE22 = ?)) AND (condE3 = ?))', [
            1,
            21,
            22,
            3,
            41,
            421,
            422,
            42,
            51,
            521,
            522,
            52,
        ]);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_not($database)
    {
        $params = [];
        $where = Where::build($database, [
            'cond1' => 1,
            // NOT はコンテキストを変えないのでこれは AND
            'NOT'   => [
                'cond2' => 2,
                'cond3' => 3,
                // NOT はコンテキストを変えないのでこれは OR
                [
                    'cond4' => 4,
                    'cond5' => 5,
                ],
                // NOT はコンテキストを変えないのでこれは AND
                'NOT'   => [
                    'cond6' => 6,
                    // NOT はコンテキストを変えないのでこれは OR
                    [
                        'cond7' => 7,
                        'cond8' => 8,
                    ],
                ],
            ],
        ], $params);
        $this->assertEquals([
            'cond1 = ?',
            'NOT ((cond2 = ?) AND (cond3 = ?) AND ((cond4 = ?) OR (cond5 = ?)) AND (NOT ((cond6 = ?) AND ((cond7 = ?) OR (cond8 = ?)))))',
        ], $where);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_notallohashwhere($database)
    {
        $params = [];
        $where = Where::build($database, [
            'id' => ['evil1' => 'evil1'],
            [
                'opt1' => ['evil2' => 'evil2'],
                'opt2' => ['evil3', 'evil4'],
            ],
        ], $params);
        $this->assertEquals(['id IN (?)', '(opt1 IN (?)) OR (opt2 IN (?,?))'], $where);
        $this->assertEquals(['evil1', 'evil2', 'evil3', 'evil4'], $params);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_rowconstructor($database)
    {
        $params = [];
        $where = Where::build($database, [
            '(mainid, subid)' => [],
        ], $params);
        $this->assertEquals(['FALSE'], $where);
        $this->assertEquals([], $params);

        $params = [];
        $where = Where::build($database, [
            '(mainid, subid)' => $database->select('multiprimary S.mainid,subid', ['name' => ['a', 'c']]),
        ], $params);
        $this->assertEquals(['(mainid, subid) IN (SELECT S.mainid, S.subid FROM multiprimary S WHERE name IN (?,?))'], $where);
        $this->assertEquals(['a', 'c'], $params);

        $params = [];
        $where = Where::build($database, [
            '(mainid, subid)' => [[1, 2], [3, 4]],
        ], $params);
        $this->assertEquals(['(mainid, subid) IN ((?,?),(?,?))'], $where);
        $this->assertEquals([1, 2, 3, 4], $params);

        // 行値式を解す DB では実際に投げて確認する
        if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertEquals([], $database->selectArray('multiprimary M', [
                '(mainid, subid)' => [],
            ]));
            $this->assertEquals([
                [
                    'mainid' => '1',
                    'subid'  => '1',
                    'name'   => 'a',
                ],
                [
                    'mainid' => '1',
                    'subid'  => '2',
                    'name'   => 'b',
                ],
            ], $database->selectArray('multiprimary M', [
                '(mainid, subid)' => [[1, 1], [1, 2]],
            ]));
        }
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_build_queryable_array($database)
    {
        $params = [];
        $where = Where::build($database, [
            '? and ? and ? and ? and ?' => [
                null,
                $database->selectCount('test1', ['id' => 0, 'name1' => 'hoge']),
                $database->selectExists('test2', ['id' => 1, 'name2' => 'fuga']),
                'dummy',
                $database->raw('(select 1)'),
            ],
        ], $params);

        $count = $database->getPlatform()->quoteIdentifier('*@count');
        $this->assertEquals([
            implode(" and ", [
                "?",
                "(SELECT COUNT(*) AS $count FROM test1 WHERE (id = ?) AND (name1 = ?))",
                "(EXISTS (SELECT * FROM test2 WHERE (id = ?) AND (name2 = ?)))",
                "?",
                "(select 1)",
            ]),
        ], $where);
        $this->assertEquals([null, 0, 'hoge', 1, 'fuga', 'dummy'], $params);
        $this->assertStringIgnoreBreak("NULL and
(SELECT COUNT(*) AS $count FROM test1 WHERE (id = '0') AND (name1 = 'hoge')) and
(EXISTS (SELECT * FROM test2 WHERE (id = '1') AND (name2 = 'fuga'))) and
'dummy' and
(select 1)", $database->queryInto($where[0], $params));

        $params = [];
        $where = Where::build($database, [
            '?' => [$database->selectExists('test1', ['id' => 1]), 0],
        ], $params);

        $this->assertEquals(['(EXISTS (SELECT * FROM test1 WHERE id = ?)) IN (?)'], $where);
        $this->assertEquals([1, 0], $params);
        $this->assertStringIgnoreBreak("(EXISTS (SELECT * FROM test1 WHERE id = '1')) IN ('0')", $database->queryInto($where[0], $params));

        $params = [];
        $where = Where::build($database, [
            '?' => [$database->selectExists('test1', ['id' => 1]), 2, 3],
        ], $params);

        $this->assertEquals(['(EXISTS (SELECT * FROM test1 WHERE id = ?)) IN (?,?)'], $where);
        $this->assertEquals([1, 2, 3], $params);
        $this->assertStringIgnoreBreak("(EXISTS (SELECT * FROM test1 WHERE id = '1')) IN ('2','3')", $database->queryInto($where[0], $params));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_andor($database)
    {
        $this->assertExpression(Where::and([])($database), '', []);
        $this->assertExpression(Where::and(['hoge' => null])($database), 'hoge IS NULL', []);
        $this->assertExpression(Where::and(['hoge' => []])($database), 'FALSE', []);
        $this->assertExpression(Where::and(['hoge = 1'])($database), 'hoge = 1', []);
        $this->assertExpression(Where::and(['hoge' => 1])($database), 'hoge = ?', [1]);
        $this->assertExpression(Where::and(['hoge = ?' => 1])($database), 'hoge = ?', [1]);
        $this->assertExpression(Where::and(['hoge = ?' => [1]])($database), 'hoge = ?', [1]);
        $this->assertExpression(Where::and(['hoge' => [1]])($database), 'hoge IN (?)', [1]);
        $this->assertExpression(Where::and(['hoge' => [1, 2]])($database), 'hoge IN (?,?)', [1, 2]);
        $this->assertExpression(Where::and(['hoge IN (?)' => [1, 2]])($database), 'hoge IN (?,?)', [1, 2]);
        $this->assertExpression(Where::and(['hoge IN(?) OR fuga IN(?)' => [[1], [2, 3]]])($database), 'hoge IN(?) OR fuga IN(?,?)', [1, 2, 3]);
        $this->assertExpression(Where::and(['hoge = ? OR fuga = ?' => [1, 2]])($database), 'hoge = ? OR fuga = ?', [1, 2]);
        $this->assertExpression(Where::and(['hoge = ? OR fuga = ?' => [[1], [2]]])($database), 'hoge = ? OR fuga = ?', [1, 2]);
        $this->assertExpression(Where::and(['hoge = ? OR fuga IN (?)' => [1, [2, 3]]])($database), 'hoge = ? OR fuga IN (?,?)', [1, 2, 3]);
        $this->assertExpression(Where::and(['hoge = ? OR fuga IN (?)' => [[1], [2, 3]]])($database), 'hoge = ? OR fuga IN (?,?)', [1, 2, 3]);
        $this->assertExpression(Where::and([
            'hoge IN(?) OR fuga IN(?)' => new \ArrayObject([
                new \ArrayObject([1]),
                new \ArrayObject([2, 3]),
            ]),
        ])($database), 'hoge IN(?) OR fuga IN(?,?)', [1, 2, 3]);

        $this->assertExpression(Where::and([
            'cond' => 1,
            ':id',
            'condition',
        ])($database), '(cond = ?) AND (id = :id) AND (condition)', [1]);
        $this->assertExpression(Where::or([
            'cond' => 1,
            ':id',
            'condition',
        ])($database), '(cond = ?) OR (id = :id) OR (condition)', [1]);

        $this->assertExpression(Where::and([
            // 含まれない
            '!a1' => null,
            '!a2' => [],
            // 含まれる
            '!b1' => 1,
            '!b2' => [1],
        ])($database), '(b1 = ?) AND (b2 IN (?))', [1, 1]);
        $this->assertExpression(Where::or([
            // 含まれない
            '!a1' => null,
            '!a2' => [],
            // 含まれる
            '!b1' => 1,
            '!b2' => [1],
        ])($database), '(b1 = ?) OR (b2 IN (?))', [1, 1]);

        $this->assertExpression(Where::and([
            'id3:IN'       => ['x', 'y'],
            'id4:%LIKEIN%' => ['x', 'y'],
            'id5:!IN'      => ['x1', 'y1'],
            'id6:!'        => ['x2', 'y2'],
        ])($database), '(id3 IN (?,?)) AND (id4 LIKE ? OR id4 LIKE ?) AND (NOT (id5 IN (?,?))) AND (NOT (id6 IN (?,?)))', ['x', 'y', '%x%', '%y%', 'x1', 'y1', 'x2', 'y2']);

        $this->assertExpression(Where::and([['scalar', 'value']])($database), '(scalar) OR (value)', []);
        $this->assertExpression(Where::and([[], 'C' => []])($database), 'FALSE', []);
        $this->assertExpression(Where::and([new Expression('FUNC(99)')])($database), 'FUNC(99)', []);
        $this->assertExpression(Where::and([new Expression('FUNC(?)', [99])])($database), 'FUNC(?)', [99]);
        $this->assertExpression(Where::and(['col' => Operator::is(1, 2)])($database), 'col IN (?,?)', [1, 2]);
        $this->assertExpression(Where::and(['col' => Operator::is(null)])($database), 'col IS NULL', []);
        $this->assertExpression(Where::and([$database->select('test.hoge')])($database), '(SELECT test.hoge FROM test)', []);
        $this->assertExpression(Where::and(['id = ?' => $database->select('test.id')])($database), 'id = (SELECT test.id FROM test)', []);
        $this->assertExpression(Where::and(['id IN(?)' => $database->select('test.id')])($database), 'id IN((SELECT test.id FROM test))', []);
        $this->assertExpression(Where::and(['id' => $database->select('test.id')])($database), 'id IN (SELECT test.id FROM test)', []);

        that(Where::class)::build($database, ['hoge = ?' => [[1, 2], 3]])->wasThrown(new \InvalidArgumentException('notfound search string'));

        that(Where::class)::build($database, ['col:OP' => Operator::is(null)])->wasThrown(new \UnexpectedValueException('both specified'));
    }

    public static function assertExpression(Queryable $expr, $expectedQuery, array $expectedparams)
    {
        self::assertEquals($expectedQuery, $expr->getQuery());
        self::assertEquals($expectedparams, $expr->getParams());
    }
}
