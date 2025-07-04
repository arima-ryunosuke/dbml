<?php

namespace ryunosuke\Test\dbml\Query;

use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\dbml\Query\TableDescriptor;
use ryunosuke\Test\Database;

class TableDescriptorTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function assertDescriptor($actual, $expected)
    {
        $expected += [
            'table'     => null,
            'alias'     => null,
            'accessor'  => null,
            'joinsign'  => '',
            'jointype'  => null,
            'jointable' => [],
            'scope'     => [],
            'condition' => [],
            'group'     => [],
            'having'    => [],
            'fkeyname'  => null,
            'column'    => [],
            'remaining' => '',
        ];
        $values = [];
        foreach ($expected as $name => $value) {
            $values[$name] = $actual->$name;
        }
        $expected['accessor'] = $expected['alias'] ?: $expected['table'];
        $this->assertEquals($expected, $values);
    }

    function test_split()
    {
        that(TableDescriptor::class)::_split('t_table', ['*'])->is([
            't_table' => ['*'],
        ]);

        that(TableDescriptor::class)::_split('t_parent.id, name', ['*'])->is([
            't_parent' => ['id', 'name'],
        ]);

        that(TableDescriptor::class)::_split('t_parent P.id, P.name', ['*'])->is([
            't_parent P' => ['id', 'name'],
        ]);

        that(TableDescriptor::class)::_split('t_parent, t_child.id', ['*'])->is([
            't_parent' => ['*'],
            't_child'  => ['id'],
        ]);

        that(TableDescriptor::class)::_split('t_parent:fkey.* + t_child.id', [])->is([
            't_parent:fkey' => ['*'],
            '+t_child'      => ['id'],
        ]);

        that(TableDescriptor::class)::_split('t_parent(P.flg=1) P.* + t_child.id', [])->is([
            't_parent(P.flg=1) P' => ['*'],
            '+t_child'            => ['id'],
        ]);

        that(TableDescriptor::class)::_split('+t_article.**', [])->is([
            '+t_article' => ['**'],
        ]);

        that(TableDescriptor::class)::_split('t_parent/t_child.id', [])->is([
            't_parent' => [
                't_child' => ['id'],
            ],
        ]);

        that(TableDescriptor::class)::_split('schema.table.column', ['*'])->wasThrown('not supports specify other schema');
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_join($database)
    {
        $_join = function (...$args) {
            $_join = new \ReflectionMethod(TableDescriptor::class, '_join');
            $_join->setAccessible(true);
            return $_join->invokeArgs(null, $args);
        };

        $this->assertDescriptor($_join($database, '<test T', ['*'], 'multifkey2'), [
            "table"     => "test",
            "alias"     => "T",
            "key"       => "<test T",
            "accessor"  => "T",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            'fkeyname'  => null,
            "column"    => ["*"],
            "remaining" => "",
        ]);
        $this->assertDescriptor($_join($database, '<test T:fk', ['*'], 'multifkey2'), [
            "table"     => "test",
            "alias"     => "T",
            "key"       => "<test:fk T",
            "accessor"  => "T",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            'fkeyname'  => "fk",
            "column"    => ["*"],
            "remaining" => "",
        ]);
        $this->assertDescriptor($_join($database, '<test T', ['*'], 'undefined'), [
            "table"     => "test",
            "alias"     => "T",
            "key"       => "<test T",
            "accessor"  => "T",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            'fkeyname'  => null,
            "column"    => ["*"],
            "remaining" => "",
        ]);
        $this->assertDescriptor($_join($database, '<undefined T', ['*'], 'undefined'), [
            "table"     => "undefined",
            "alias"     => "T",
            "key"       => "<undefined T",
            "accessor"  => "T",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            'fkeyname'  => null,
            "column"    => ["*"],
            "remaining" => "",
        ]);
        $this->assertDescriptor($_join($database, '<id', ['*'], 'test'), [
            "table"     => "id",
            "alias"     => null,
            "key"       => "<id",
            "accessor"  => "id",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            'fkeyname'  => null,
            "column"    => ["*"],
            "remaining" => "",
        ]);

        $this->assertDescriptor($_join($database, '<fcol1', [], 'multifkey2'), [
            "table"     => "multiunique",
            "alias"     => "multifkey2_multiunique_fcol1",
            "key"       => "<multiunique:fk_multifkeys1 multifkey2_multiunique_fcol1",
            "accessor"  => "multifkey2_multiunique_fcol1",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            "fkeyname"  => "fk_multifkeys1",
            "column"    => [],
            "remaining" => "",
        ]);
        $this->assertDescriptor($_join($database, '<fcol1 F1.col', [], 'multifkey2'), [
            "table"     => "multiunique",
            "alias"     => "F1",
            "key"       => "<multiunique:fk_multifkeys1 F1",
            "accessor"  => "F1",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            "fkeyname"  => "fk_multifkeys1",
            "column"    => ["col"],
            "remaining" => "",
        ]);
        $this->assertDescriptor($_join($database, '<fcol1["cond"] F1.col', [], 'multifkey2'), [
            "table"     => "multiunique",
            "alias"     => "F1",
            "key"       => "<multiunique:fk_multifkeys1[\"cond\"] F1",
            "accessor"  => "F1",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            "fkeyname"  => "fk_multifkeys1",
            "column"    => ["col"],
            "condition" => ["cond"],
            "remaining" => "",
        ]);

        $expected = [
            "table"     => "multiunique",
            "alias"     => "T",
            //"key"       => "<multiunique@@scope1@scope2(1, 2):fk_multifkeys1[on1 = 1]<id, cid>+aid-did#10-20 T",
            "accessor"  => "T",
            "joinsign"  => "<",
            "jointype"  => "LEFT",
            "fkeyname"  => "fk_multifkeys1",
            "column"    => [],
            "condition" => ["on1 = 1"],
            "remaining" => "",
            "scope"     => [
                ""       => [],
                "scope1" => [],
                "scope2" => [1, 2],
            ],
            "group"     => ["id", "cid"],
        ];
        $this->assertDescriptor($_join($database, '<fcol1<id, cid>@@scope1@scope2(1, 2)[on1 = 1]+aid-did#10-20 AS T', [], 'multifkey2'), $expected);
        $this->assertDescriptor($_join($database, '<fcol1[on1 = 1]@@scope1@scope2(1, 2)<id, cid>+aid-did#10-20 AS T', [], 'multifkey2'), $expected);
        $this->assertDescriptor($_join($database, '<fcol1@[on1 = 1]@scope1<id, cid>@scope2(1, 2)+aid-did#10-20 AS T', [], 'multifkey2'), $expected);
        $this->assertDescriptor($_join($database, '<fcol1+aid-did@@scope2(1, 2)@scope1<id, cid>#10-20[on1 = 1] AS T', [], 'multifkey2'), $expected);
        $this->assertDescriptor($_join($database, '<fcol1#10-20+aid-did@@scope1<id, cid>[on1 = 1]@scope2(1, 2) AS T', [], 'multifkey2'), $expected);

        that(TableDescriptor::class)::_join($database, '<fcol9', ['*'], 'multifkey2')->wasThrown('foreign key !== 1');
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_forge($database)
    {
        $of = function ($v) { return $v->descriptor; };
        $this->assertEquals(['test'], array_map($of, TableDescriptor::forge($database, 'test')));
        $this->assertEquals(['test1', 'test2'], array_map($of, TableDescriptor::forge($database, 'test1,test2')));
        $this->assertEquals(['test1', '+test2'], array_map($of, TableDescriptor::forge($database, 'test1+test2')));
        $this->assertEquals(['test1', '<test2'], array_map($of, TableDescriptor::forge($database, 'test1<test2')));
        $this->assertEquals(['test1', '>test2'], array_map($of, TableDescriptor::forge($database, 'test1>test2')));
        $this->assertEquals(['test1<test2>'], array_map($of, TableDescriptor::forge($database, 'test1<test2>')));
        $this->assertEquals(['test1', '<test2', '>test3'], array_map($of, TableDescriptor::forge($database, 'test1<test2>test3')));
        $this->assertEquals(['test1', '+test2'], array_map($of, TableDescriptor::forge($database, [
            'test1'  => [],
            '+test2' => [],
        ])));
        $this->assertEquals(['test1', 'test2'], array_map($of, TableDescriptor::forge($database, [
            'test1' => [],
            TableDescriptor::forge($database, 'test2')[0],
        ])));
        $this->assertEquals(['test', null], array_map($of, TableDescriptor::forge($database, [
            null,
            'test',
            ['c',],
        ])));

        $this->assertEquals(['/*+comment*/test'], array_map($of, TableDescriptor::forge($database, '/*+comment*/test')));

        $nest = TableDescriptor::forge($database, 'test1/*test1 1*//test2/*test2 2*/', []);
        $this->assertEquals(['test1/*test1 1*/'], array_map($of, $nest));
        $this->assertEquals('test2/*test2 2*/', array_key_first($nest[0]->column));
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___construct($database)
    {
        // 素
        $this->assertDescriptor(new TableDescriptor($database, 'test', []), [
            'descriptor' => 'test',
            'table'      => 'test',
            'alias'      => null,
            'joinsign'   => '',
            'jointype'   => null,
            'jointable'  => [],
            'scope'      => [],
            'condition'  => [],
            'group'      => [],
            'order'      => [],
            'fkeyname'   => null,
            'column'     => [],
            'key'        => 'test',
        ]);

        // スコープ
        $this->assertDescriptor(new TableDescriptor($database, 'test@scope@scope1()@scope2(1, "2,3")', []), [
            'table' => 'test',
            'alias' => null,
            'scope' => [
                'scope'  => [],
                'scope1' => [],
                'scope2' => ['1', '2,3'],
            ],
            'key'   => 'test@scope@scope1()@scope2(1, "2,3")',
        ]);

        // CONDITION
        $this->assertDescriptor(new TableDescriptor($database, 'test[on1=1, on2 = 2]', []), [
            'table'     => 'test',
            'condition' => ['on1=1', 'on2 = 2'],
            'key'       => 'test[on1=1, on2 = 2]',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test[on1=1, on2 = 2] T', []), [
            'table'     => 'test',
            'alias'     => 'T',
            'condition' => ['on1=1', 'on2 = 2'],
            'key'       => 'test[on1=1, on2 = 2] T',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test[on1=1] T', [['on2 = 2']]), [
            'table'     => 'test',
            'alias'     => 'T',
            'condition' => ['on1=1', 'on2 = 2'],
            'key'       => 'test[on1=1] T',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test{id1, id2}', []), [
            'table'     => 'test',
            'condition' => [(object) ['id1' => 'id1', 'id2' => 'id2']],
            'key'       => 'test{id1, id2}',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test{tA: tB,uA: uB}', []), [
            'table'     => 'test',
            'condition' => [(object) ['tA' => 'tB', 'uA' => 'uB']],
            'key'       => 'test{tA: tB,uA: uB}',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test{id1, id2, tA: tB, uA: uB}', []), [
            'table'     => 'test',
            'condition' => [(object) ['id1' => 'id1', 'id2' => 'id2', 'tA' => 'tB', 'uA' => 'uB']],
            'key'       => 'test{id1, id2, tA: tB, uA: uB}',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test[cond1, cond2]', []), [
            'table'     => 'test',
            'condition' => ['cond1', 'cond2'],
            'key'       => 'test[cond1, cond2]',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test[cond1, cond2] T', []), [
            'table'     => 'test',
            'alias'     => 'T',
            'condition' => ['cond1', 'cond2'],
            'key'       => 'test[cond1, cond2] T',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'test[cond1, cond2]{id1: id2} T', []), [
            'table'     => 'test',
            'alias'     => 'T',
            'condition' => ['cond1', 'cond2', (object) ['id1' => 'id2']],
            'key'       => 'test[cond1, cond2]{id1: id2} T',
        ]);

        // FOREIGN
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1:fk_parentchild1', []), [
            'table'      => 'foreign_c1',
            'fkeyname'   => 'fk_parentchild1',
            'fkeysuffix' => ':fk_parentchild1',
            'key'        => 'foreign_c1:fk_parentchild1',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1:fk_parentchild1 T', []), [
            'table'      => 'foreign_c1',
            'alias'      => 'T',
            'fkeyname'   => 'fk_parentchild1',
            'fkeysuffix' => ':fk_parentchild1',
            'key'        => 'foreign_c1:fk_parentchild1 T',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1: T', []), [
            'table'      => 'foreign_c1',
            'alias'      => 'T',
            'fkeyname'   => '',
            'fkeysuffix' => '',
            'key'        => 'foreign_c1: T',
        ]);

        // PRIMARY
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_p(1, 2) T', []), [
            'table'     => 'foreign_p',
            'alias'     => 'T',
            'condition' => [
                new Expression('T.id IN (?, ?)', [1, 2]),
            ],
            'key'       => 'foreign_p(1, 2) T',
        ]);
        if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1((1, 2), (3, 4))', []), [
                'table'     => 'foreign_c1',
                'condition' => [
                    new Expression('(foreign_c1.id, foreign_c1.seq) IN ((?, ?), (?, ?))', [1, 2, 3, 4]),
                ],
                'key'       => 'foreign_c1((1, 2), (3, 4))',
            ]);
        }
        else {
            $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1((1, 2), (3, 4))', []), [
                'table'     => 'foreign_c1',
                'condition' => [
                    new Expression('(foreign_c1.id = ? AND foreign_c1.seq = ?) OR (foreign_c1.id = ? AND foreign_c1.seq = ?)', [1, 2, 3, 4]),
                ],
                'key'       => 'foreign_c1((1, 2), (3, 4))',
            ]);
        }

        // GROUP
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1<id, cid>', []), [
            'table' => 'foreign_c1',
            'group' => ['id', 'cid'],
            'key'   => 'foreign_c1<id, cid>',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1<id, cid:"COUNT(*) > 1", "MIN(subid) <= ?":2>', []), [
            'table'  => 'foreign_c1',
            'group'  => ['id'],
            'having' => ['cid' => 'COUNT(*) > 1', 'MIN(subid) <= ?' => '2'],
            'key'    => 'foreign_c1<id, cid:"COUNT(*) > 1", "MIN(subid) <= ?":2>',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1<id, cid:"COUNT(*) > 1", "MIN(subid) <= ?":2, "":"AVG(subid) > 3">', []), [
            'table'  => 'foreign_c1',
            'group'  => ['id'],
            'having' => ['cid' => 'COUNT(*) > 1', 'MIN(subid) <= ?' => '2', 'AVG(subid) > 3'],
            'key'    => 'foreign_c1<id, cid:"COUNT(*) > 1", "MIN(subid) <= ?":2, "":"AVG(subid) > 3">',
        ]);

        // ORDER
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1+aid-did', []), [
            'table' => 'foreign_c1',
            'order' => ['aid' => 'ASC', 'did' => 'DESC'],
            'key'   => 'foreign_c1+aid-did',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1-did+aid AS FC', []), [
            'table' => 'foreign_c1',
            'alias' => 'FC',
            'order' => ['did' => 'DESC', 'aid' => 'ASC'],
            'key'   => 'foreign_c1-did+aid FC',
        ]);

        // RANGE
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1#10', []), [
            'table'  => 'foreign_c1',
            'offset' => 10,
            'limit'  => 1,
            'key'    => 'foreign_c1#10',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1#-20', []), [
            'table'  => 'foreign_c1',
            'offset' => null,
            'limit'  => 20,
            'key'    => 'foreign_c1#-20',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 'foreign_c1#10-20', []), [
            'table'  => 'foreign_c1',
            'offset' => 10,
            'limit'  => 10,
            'key'    => 'foreign_c1#10-20',
        ]);

        // 複合
        $expected = [
            'table'     => 't_article',
            'alias'     => 'T',
            'accessor'  => 'T',
            'joinsign'  => '',
            'jointype'  => null,
            'jointable' => [],
            'scope'     => [
                ''       => [],
                'scope1' => [],
                'scope2' => [1, 2],
            ],
            'condition' => [
                new Expression('T.article_id = ?', [1]),
                'on1 = 1',
            ],
            'fkeyname'  => 'fkeyname',
            'group'     => ['id', 'cid'],
            'order'     => ['aid' => 'ASC', 'did' => 'DESC'],
            'offset'    => 10,
            'limit'     => 10,
            'column'    => ['id as ID'],
            'key'       => 't_article(1)@@scope1@scope2(1, 2):fkeyname[on1 = 1]<id, cid>+aid-did#10-20 T',
        ];
        $this->assertDescriptor(new TableDescriptor($database, 't_article(1)<id, cid>@@scope1@scope2(1, 2):fkeyname[on1 = 1]+aid-did#10-20 AS T.id as ID', []), $expected);
        $this->assertDescriptor(new TableDescriptor($database, 't_article(1)#10-20@@scope1@scope2(1, 2)[on1 = 1]+aid-did<id, cid>:fkeyname AS T.id as ID', []), $expected);
        $this->assertDescriptor(new TableDescriptor($database, 't_article(1):fkeyname@@scope1@scope2<id, cid>(1, 2)+aid-did#10-20[on1 = 1] AS T.id as ID', []), $expected);
        $this->assertDescriptor(new TableDescriptor($database, 't_article(1):fkeyname[on1 = 1]+aid-did#10-20<id, cid>@@scope1@scope2(1, 2) AS T.id as ID', []), $expected);
        $this->assertDescriptor(new TableDescriptor($database, 't_article(1)+aid-did#10-20[on1 = 1]@@scope1@scope2(1, 2):fkeyname<id, cid> AS T.id as ID', []), $expected);
        $this->assertDescriptor(new TableDescriptor($database, 't_article(1)[on1 = 1]+aid-did<id, cid>#10-20:fkeyname@@scope1@scope2(1, 2) AS T.id as ID', []), $expected);

        $expected['comment'] = ' this is comment ';
        $this->assertDescriptor(new TableDescriptor($database, '/* this is comment */t_article(1)[on1 = 1]+aid-did<id, cid>#10-20:fkeyname@@scope1@scope2(1, 2) AS T.id as ID', []), $expected);

        // JOIN
        $td = new TableDescriptor($database, '+t_table T', [
            'alias' => '+t_join.id',
        ]);
        $this->assertDescriptor($td->jointable[0], [
            'descriptor' => [],
            'table'      => 't_join',
            'alias'      => null,
            'joinsign'   => '+',
            'jointype'   => 'INNER',
            'jointable'  => [],
            'scope'      => [],
            'condition'  => [],
            'fkeyname'   => null,
            'column'     => ['id'],
            'key'        => '+t_join',
        ]);
        $td = new TableDescriptor($database, '+t_table T', [
            '+t_join.id' => [],
        ]);
        $this->assertDescriptor($td->jointable[0], [
            'descriptor' => [],
            'table'      => 't_join',
            'alias'      => null,
            'joinsign'   => '+',
            'jointype'   => 'INNER',
            'jointable'  => [],
            'scope'      => [],
            'condition'  => [],
            'fkeyname'   => null,
            'column'     => ['id'],
            'key'        => '+t_join',
        ]);
        $td = new TableDescriptor($database, '+t_table T', [
            '+TS.id' => $database->test,
        ]);
        $this->assertDescriptor($td->jointable[0], [
            'table'     => 'TS',
            'alias'     => null,
            'joinsign'  => '+',
            'jointype'  => 'INNER',
            'jointable' => [],
            'scope'     => [],
            'condition' => [],
            'fkeyname'  => null,
            'column'    => ['id'],
            'key'       => '+test TS',
        ]);
        $this->assertEquals('TS', $td->jointable[0]->descriptor->alias());
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___construct_asterisk($database)
    {
        $this->assertDescriptor(new TableDescriptor($database, 't_article.**', []), [
            'table'  => 't_article',
            'column' => [
                '*',
                'Comment' => ['*'],
            ],
        ]);
        $this->assertDescriptor(new TableDescriptor($database, 't_article.**', ['t_comment' => null]), [
            'table'  => 't_article',
            'column' => [
                '*',
                't_comment' => null,
            ],
        ]);

        $this->assertDescriptor(new TableDescriptor($database, 'horizontal1.**', []), [
            'table'  => 'horizontal1',
            'column' => [
                '*',
            ],
        ]);

        $this->assertDescriptor(new TableDescriptor($database, 'foreign_s.**', []), [
            'table'  => 'foreign_s',
            'column' => [
                '*',
                'foreign_sc:fk_sc2' => ['*'],
                'foreign_sc:fk_sc1' => ['*'],
            ],
        ]);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___construct_misc($database)
    {
        // misc
        $this->assertDescriptor(new TableDescriptor($database, '/*+comment*/+test T', ['id']), [
            'comment'  => '+comment',
            'table'    => 'test',
            'alias'    => 'T',
            'accessor' => 'T',
            'joinsign' => '+',
            'jointype' => 'INNER',
            'fkeyname' => null,
            'column'   => ['id'],
            'key'      => '+test T',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, '+test T', ['id']), [
            'table'    => 'test',
            'alias'    => 'T',
            'accessor' => 'T',
            'joinsign' => '+',
            'jointype' => 'INNER',
            'fkeyname' => null,
            'column'   => ['id'],
            'key'      => '+test T',
        ]);
        $this->assertDescriptor(new TableDescriptor($database, '+Article', []), [
            'table'    => 't_article',
            'alias'    => 'Article',
            'accessor' => 'Article',
            'joinsign' => '+',
            'jointype' => 'INNER',
            'fkeyname' => null,
            'column'   => [],
            'key'      => '+t_article Article',
        ]);

        $this->assertDescriptor(new TableDescriptor($database->context(['notableAsColumn' => true]), 'notfoundtable', ['col']), [
            'table'    => null,
            'alias'    => null,
            'accessor' => null,
            'joinsign' => null,
            'jointype' => null,
            'fkeyname' => null,
            'column'   => ['notfoundtable' => ['col']],
            'key'      => '',
        ]);

        // qb
        $td = new TableDescriptor($database, 'test', $database->select('t_child'));
        $this->assertInstanceOf(SelectBuilder::class, $td->table);
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___get($database)
    {
        $td = new TableDescriptor($database, '+test T', ['id']);
        $this->assertSame('T', $td->accessor);
        $this->assertSame(null, $td->fkeyname);
        $this->assertSame([], $td->condition);

        that($td)->hogera->wasThrown('is undefined');
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test___set($database)
    {
        $td = new TableDescriptor($database, '+test T', ['id']);
        $td->table = 'test2';
        $this->assertSame('test2', $td->table);

        $this->expectExceptionMessage('is undefined');
        $td->hogera = null;
    }

    /**
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_bind($database)
    {
        $td = new TableDescriptor($database, 'test[id:?, "name like ?"] T', []);
        $this->assertDescriptor($td->bind($database, [1, $database->quote('hoge')]), [
            'table'     => 'test',
            'alias'     => 'T',
            'accessor'  => 'T',
            'condition' => [
                'id' => '1',
                0    => "name like 'hoge'",
            ],
        ]);

        $td = new TableDescriptor($database, 'test[id:[?, ?, ?]] T', []);
        $this->assertDescriptor($td->bind($database, [1, 2, 3]), [
            'table'     => 'test',
            'alias'     => 'T',
            'accessor'  => 'T',
            'condition' => [
                'id' => ['1', '2', '3'],
            ],
        ]);

        $td = new TableDescriptor($database, 'test(?) T', []);
        $this->assertDescriptor($td->bind($database, [1]), [
            'table'     => 'test',
            'alias'     => 'T',
            'accessor'  => 'T',
            'condition' => [
                new Expression('T.id = ?', 1),
            ],
        ]);

        $td = new TableDescriptor($database, 'test[?] T', []);
        $this->assertDescriptor($td->bind($database, [$E = $database->select('test', ['id' => 1])->existize()]), [
            'table'     => 'test',
            'alias'     => 'T',
            'accessor'  => 'T',
            'condition' => [
                $E,
            ],
        ]);

        $td = new TableDescriptor($database, 'test[id:?] T', []);
        that($td)->bind($database, [])->wasThrown('short');
        that($td)->bind($database, [1, 2])->wasThrown('long');
    }
}
