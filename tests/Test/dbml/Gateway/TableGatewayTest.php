<?php

namespace ryunosuke\Test\dbml\Gateway;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use ryunosuke\dbml\Entity\Entity;
use ryunosuke\dbml\Exception\NonSelectedException;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\Test\Database;
use ryunosuke\Test\Entity\Article;
use ryunosuke\Test\Platforms\SqlitePlatform;
use function ryunosuke\dbml\csv_import;
use function ryunosuke\dbml\json_import;

class TableGatewayTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    public static function provideGateway()
    {
        return array_map(function ($v) {
            return [
                new TableGateway($v[0], 'test'),
                $v[0],
            ];
        }, parent::provideDatabase());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test___getset($gateway, $database)
    {
        $gateway->where(['id' => 2])['name'] = "hogera";
        $this->assertEquals("hogera", $gateway->where(['id' => 2])['name']);
        $this->assertNotEquals("hogera", $gateway->where(['id' => 3])['name']);

        $gateway->where(['id' => 2])['name'] = $database->getCompatiblePlatform()->getConcatExpression('name', "'XXX'");
        $this->assertEquals("hogeraXXX", $gateway->where(['id' => 2])['name']);

        $this->assertEquals(false, $gateway->where(['id' => 999])['name']);

        $this->assertNull($gateway->hogera);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test___toString($gateway)
    {
        $this->assertEquals("SELECT test.* FROM test WHERE test.id = '2'", (string) $gateway->column('*')->where(['id' => 2]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test___invoke($gateway)
    {
        /// 基本的には magic join. ただし数値のときは find になる

        $this->assertEquals([
            'id'   => '2',
            'name' => 'b',
            'data' => '',
        ], $gateway(2)->tuple());

        $this->assertEquals([
            'name' => 'c',
        ], ($gateway->column('name')(3))->tuple());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_offsetExists($gateway)
    {
        $this->assertTrue(isset($gateway['id']));
        $this->assertFalse(isset($gateway['undefined']));

        $this->assertTrue(isset($gateway[1]));
        $this->assertFalse(isset($gateway[999]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_offsetGet($gateway)
    {
        $this->assertEquals('a', $gateway->pk(1)['name']);

        $this->assertEquals([
            'id'   => '1',
            'name' => 'a',
            'data' => '',
        ], $gateway[1]->tuple());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_offsetGet_asterisk($gateway, $database)
    {
        $this->assertEquals([
            'id'   => '1',
            'name' => 'a',
            'data' => '',
        ], $gateway[1]['*']);

        $this->assertEquals([
            'article_id' => '1',
            'title'      => 'タイトルです',
            'checks'     => '',
            'Comment'    => [
                1 => [
                    'comment_id' => '1',
                    'article_id' => '1',
                    'comment'    => 'コメント1です',
                ],
                2 => [
                    'comment_id' => '2',
                    'article_id' => '1',
                    'comment'    => 'コメント2です',
                ],
                3 => [
                    'comment_id' => '3',
                    'article_id' => '1',
                    'comment'    => 'コメント3です',
                ],
            ],
        ], $database->t_article[1]['**']);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_offsetGet_desccriptor($gateway, $database)
    {
        $gw = $database->t_comment['(1)@scope1@scope2(9)[flag1 = 1, flag2: 2]-comment_id AS C.comment_id']('comment');
        $this->assertStringIgnoreBreak("SELECT NOW(), C.comment_id, C.comment
FROM t_comment C
WHERE (C.comment_id = '9') AND (C.comment_id = '1') AND (flag1 = 1) AND (C.flag2 = '2')
ORDER BY C.comment_id DESC", "$gw");

        $gw = $database->t_article->as('A')->t_comment['(2)@scope1@scope2(9):fk_articlecomment AS C.comment_id']('comment', '(flag=1)');
        $this->assertStringIgnoreBreak("
SELECT NOW(), C.comment_id, C.comment
FROM t_article A
LEFT JOIN t_comment C
ON (C.article_id = A.article_id)
AND (C.comment_id = '9')
AND (C.comment_id = '2')
AND ((flag=1))", "$gw");

        // [] は省略できる
        $this->assertEquals("SELECT * FROM t_article WHERE t_article.id = '1'", (string) $database->t_article['id: 1']);
        $this->assertEquals("SELECT * FROM t_article WHERE id = 1", (string) $database->t_article['id = 1']);

        // #offset-limit（方言が有るので実際に取得する）
        $this->assertEquals(['b', 'c'], $database->test['#1-3']->lists('name'));
        $this->assertEquals(['a', 'b'], $database->test['#-2']->lists('name'));
        $this->assertEquals(['c'], $database->test['#2']->lists('name'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_offsetSet($gateway, $database)
    {
        $gateway->pk(1)['name'] = 'change!';
        $this->assertEquals('change!', $gateway->pk(1)['name']);

        $gateway[] = [
            'name' => 'new',
            'data' => '',
        ];
        $this->assertEquals([
            'id'   => '11',
            'name' => 'new',
            'data' => '',
        ], $gateway[$database->getLastInsertId('test', 'id')]->tuple());

        $gateway[99] = [
            'name' => 'new',
            'data' => '',
        ];
        $this->assertEquals([
            'id'   => '99',
            'name' => 'new',
            'data' => '',
        ], $gateway[99]->tuple());

        $gateway[99] = [
            'name' => 'newnew',
            'data' => '',
        ];
        $this->assertEquals([
            'id'   => '99',
            'name' => 'newnew',
            'data' => '',
        ], $gateway[99]->tuple());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_offsetUnset($gateway)
    {
        unset($gateway[1]);
        $this->assertFalse($gateway[1]->tuple());

        $this->assertException('not supported', function () use ($gateway) {
            unset($gateway['undefined']);
        });
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_alias_and_as($gateway)
    {
        $this->assertEquals('test U', $gateway->tableName() . ' ' . $gateway->as('U')->alias());
        $this->assertEquals('SELECT * FROM test', (string) $gateway->select());
        $this->assertEquals('SELECT * FROM test T', (string) $gateway->alias('T')->select());
        $this->assertEquals('SELECT * FROM test U', (string) $gateway->as('U')->select());
        // 戻っているはず
        $this->assertEquals('SELECT * FROM test', (string) $gateway->select());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_clone($gateway)
    {
        $clone = $gateway->clone(false);

        // 初回は $force フラグにかかわらずクローンされるはず
        $this->assertNotSame($gateway, $clone);

        // 以後は同じオブジェクトを返すはず
        $this->assertSame($clone, $clone->clone());
        $this->assertSame($clone, $clone->clone()->clone());

        // ただし $force = true にすると新しく生成されるはず
        $this->assertNotSame($clone, $clone->clone(true));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_scope($gateway, $database)
    {
        $gateway->addScope('', 'NOW()');
        $gateway->addScope('hoge', 'id', 'id=1', 'id', 1, 'id', 'id="a"');
        $gateway->addScope('fuga', 'name', 'name="a"', 'name', 2, 'name', 'name="a"');
        $gateway->addScope('piyo', function ($column, $notid = -1) {
            return [
                'column' => $column,
                'where'  => ['id <> ?' => $notid],
            ];
        });
        $gateway->addScope('this', function ($column, $notid = -1) {
            /** @var TableGateway $this */
            return $this->column([
                'calias' => $column,
            ])->where([
                'id <> ?' => $notid,
            ])->limit([1 => 10]);
        });

        // 何もしなくてもデフォルトスコープが適用されるはず
        $this->assertEquals('SELECT NOW() FROM test T', (string) $gateway->as('T')->select());
        // noscope するとデフォルトスコープも外れるはず
        $this->assertEquals('SELECT * FROM test T', (string) $gateway->as('T')->noscope()->select());
        // unscope で個別にはがせるはず
        $this->assertEquals('SELECT * FROM test T', (string) $gateway->as('T')->scope('hoge')->unscope('hoge')->unscope('')->select());

        // SQLServer は LIMIT 句が特殊で文字列的なテストが出来ない
        if (!$database->getCompatiblePlatform()->getWrappedPlatform() instanceof SQLServerPlatform) {
            // hoge スコープが適用されるはず
            $this->assertEquals('SELECT NOW(), T.id FROM test T WHERE id=1 GROUP BY id HAVING id="a" ORDER BY id ASC LIMIT 1', (string) $gateway->as('T')->scope('hoge')->select());
            // hoge,fuga スコープの両方がその順番で適用されるはず
            $this->assertEquals('SELECT NOW(), T.id, T.name FROM test T WHERE (id=1) AND (name="a") GROUP BY id, name HAVING (id="a") AND (name="a") ORDER BY id ASC, name ASC LIMIT 2', (string) $gateway->as('T')->scope('hoge fuga')->select());
        }

        // パラメータが適用されるはず
        $select = $gateway->as('T')->scope('piyo', 'col1', 1)->select('col2', ['name' => 'a']);
        $this->assertEquals('SELECT NOW(), T.col1, T.col2 FROM test T WHERE (T.id <> ?) AND (T.name = ?)', $select);
        $this->assertEquals([1, 'a'], $select->getParams());
        // デフォルト引数が適用されるはず
        $select = $gateway->as('T')->scope('piyo', 'col1')->select('col2', ['name' => 'a']);
        $this->assertEquals('SELECT NOW(), T.col1, T.col2 FROM test T WHERE (T.id <> ?) AND (T.name = ?)', $select);
        $this->assertEquals([-1, 'a'], $select->getParams());

        // スコーピングが適用されるはず
        if (!$database->getCompatiblePlatform()->getWrappedPlatform() instanceof SQLServerPlatform) {
            $select = $gateway->as('T')->scope('this', 'col1')->select('col2', ['name' => 'a']);
            $this->assertEquals('SELECT NOW(), T.col1 AS calias, T.col2 FROM test T WHERE (T.id <> ?) AND (T.name = ?) LIMIT 10 OFFSET 1', $select);
            $this->assertEquals([-1, 'a'], $select->getParams());
        }

        // 本体には一切影響がないはず
        $this->assertEquals(['' => []], self::forcedRead($gateway, 'activeScopes'));

        // スコープはインスタンス間で共用されるはず
        $gw = $gateway->as('GW');
        $gw->addScope('common', 'NOW()');
        $this->assertEquals(['NOW()'], $gateway->getScopeParts('common')['column']);

        // 存在しないスコープは例外が飛ぶはず
        $this->assertException('undefined', L($gateway)->scope('hogera'));
        $this->assertException('undefined', L($gateway)->unscope('hogera'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_mixScope($gateway, $database)
    {
        // 単純スコープ
        $gateway->addScope('a', 'NOW()');
        $gateway->addScope('b', 'name', 'name="a"', [], [1, 2]);
        $gateway->addScope('c', 'id', 'name="x"', 'id DESC', 999);
        // デフォ引数なしスコープ
        $gateway->addScope('d', function ($id) {
            return [
                'where' => ['id' => $id],
                'limit' => [3 => 4],
            ];
        });
        // デフォ引数ありスコープ
        $gateway->addScope('d0', function ($id = 0) {
            return [
                'where' => ['id' => $id],
                'limit' => [3 => 4],
            ];
        });
        // 可変引数スコープ
        $gateway->addScope('v', function (...$args) {
            return [
                'where' => ['id' => $args],
            ];
        });

        $gateway->mixScope('x', 'a b d');
        $gateway->mixScope('x1', [
            'a',
            'b',
            'd0' => [],
        ]);
        $gateway->mixScope('x2', [
            'a',
            'b',
            'd0' => [2],
        ]);
        $gateway->mixScope('x3', 'a b d', ['aliasId' => 'id'], ['id' => -1]);
        $gateway->mixScope('x4', 'a b d', function ($id) {
            return [
                'column' => ['aliasId' => 'id'],
                'where'  => ['id' => $id],
            ];
        });
        $gateway->mixScope('dv', [
            'd',
            'v',
        ]);
        $gateway->mixScope('dv1', [
            'd' => 1,
            'v',
        ]);
        $gateway->mixScope('mixmix', [
            'x2',
            'c',
        ]);

        // どこにも現れていないがデフォルト引数があるのでそれが使われる
        $this->assertEquals([
            'column'  => ['NOW()', 'name'],
            'where'   => ['name="a"', 'id' => 0],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('x1'));

        // デフォルト引数よりスコープパラメータの方が強い
        $this->assertEquals([
            'column'  => ['NOW()', 'name'],
            'where'   => ['name="a"', 'id' => 1],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('x1', 1));

        // プリセットパラメータが使用される
        $this->assertEquals([
            'column'  => ['NOW()', 'name'],
            'where'   => ['name="a"', 'id' => 2],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('x2'));

        // プリセットパラメータは上書きできない。与えた 999 は次のスコープパラメータとして使用される
        $this->assertEquals([
            'column'  => ['NOW()', 'name'],
            'where'   => ['name="a"', 'id' => 2],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('x2', 999));

        // 合成と同時に新しいスコープを当てたもの（合成のネストできるかのテストで値に特に意味はない）
        $this->assertEquals([
            'column'  => ['NOW()', 'name', 'aliasId' => 'id'],
            'where'   => ['name="a"', 'id' => [1, -1]],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('x3', 1));

        // 上記のクロージャ版
        $this->assertEquals([
            'column'  => ['NOW()', 'name', 'aliasId' => 'id'],
            'where'   => ['name="a"', 'id' => [1, -2]],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('x4', 1, -2));

        // 可変引数の合成スコープ dv
        $this->assertEquals([
            'column'  => [],
            'where'   => ['id' => [1, 2, 3, 4]],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('dv', 1, 2, 3, 4));

        // 可変引数の合成スコープ dv1
        $this->assertEquals([
            'column'  => [],
            'where'   => ['id' => [1, 2, 3, 4]],
            'orderBy' => [],
            'limit'   => [3 => 4],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('dv1', 2, 3, 4));

        // 合成スコープを合成した合成スコープ（合成のネストできるかのテストで値に特に意味はない）
        $this->assertEquals([
            'column'  => ['NOW()', 'name', 'id'],
            'where'   => ['name="a"', 'id' => 2, 'name="x"'],
            'orderBy' => ['id DESC'],
            'limit'   => [999],
            'groupBy' => [],
            'having'  => [],
        ], $gateway->getScopeParts('mixmix'));

        // これはエラーになる（c の引数がどこにも現れていない）
        $this->assertException(new \ArgumentCountError(), L($gateway->scope('x'))->select());
        // 登録されていないスコープはエラー
        $this->assertException('scope is undefined', L($gateway)->mixScope('new', 'undefined'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_bindScope($gateway)
    {
        // 引数付きスコープ
        $gateway->addScope('abc', function ($a, $b, $c) {
            return [
                'column' => [$a, $b, $c],
            ];
        });

        $gateway->setOption('overrideBindScope', true);

        $gateway->bindScope('abc', [2 => 'C1']);
        $this->assertEquals(['a', 'b', 'C1'], $gateway->getScopeParts('abc', 'a', 'b')['column']);
        $gateway->bindScope('abc', [1 => 'B', 2 => 'C2']);
        $this->assertEquals(['a', 'B', 'C2'], $gateway->getScopeParts('abc', 'a')['column']);
        $gateway->bindScope('abc', [0 => 'X', 1 => 'Y', 2 => 'Z']);
        $this->assertEquals(['a', 'b', 'c'], $gateway->getScopeParts('abc', 'a', 'b', 'c')['column']);
        $this->assertEquals(['X', 'Y', 'Z'], $gateway->getScopeParts('abc')['column']);

        $gateway->addScope('xyz', 'now()');
        $this->assertException('scope must be closure', L($gateway)->bindScope('xyz', []));
        $this->assertException('scope is undefined', L($gateway)->bindScope('new', []));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_scopes($gateway)
    {
        $gateway->addScope('scope1', function ($val) {
            return [
                'where' => [
                    'id' => $val,
                ],
            ];
        });
        $gateway->addScope('scope2', function ($val) {
            return [
                'where' => [
                    'name' => $val,
                ],
            ];
        });
        $gateway->addScope('scope3', ['id2' => 'id']);

        // 複数のスコープを同時に当てる
        $params = $gateway->scope([
            'scope1' => 1,
            'scope2' => [['hoge', 'fuga']],
            'scope3',
        ])->getScopeParams();
        $this->assertEquals('1', $params['where']['test.id']);
        $this->assertEquals(['hoge', 'fuga'], $params['where']['test.name']);
        $this->assertEquals('id', $params['column']['test']['id2']);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_scope_and_empty($gateway, $database)
    {
        $gateway->addScope('', [
            'upper' => 'UPPER(name)',
        ]);

        $this->assertEquals([
            'id'     => 2,
            'upper'  => 'B',
            'idname' => '2b',
        ], $gateway->tuple([
            'id',
            'idname' => $database->getCompatiblePlatform()->getConcatExpression('id', 'name'),
        ], ['id' => 2]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_scope_closure($gateway)
    {
        $gateway->addScope('this', function () {
            if ($this instanceof TableGateway) {
                return [
                    'where' => [
                        'class' => get_class($this),
                        'alias' => $this->alias(),
                    ],
                ];
            }
        });

        // scope クロージャ内の $this はそれ自身になる
        $params = $gateway->as('hogera')->scope('this')->getScopeParams();
        $this->assertEquals(TableGateway::class, $params['where']['hogera.class']);
        $this->assertEquals('hogera', $params['where']['hogera.alias']);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_definedScope($gateway)
    {
        $gateway->addScope('hoge', 'hoge');
        $gateway->addScope('fuga', 'fuga');
        $gateway->addScope('piyo', 'piyo');

        $this->assertEquals(['piyo', 'hoge'], $gateway->definedScope(['piyo', 'undef', 'hoge']));
        $this->assertEquals([], $gateway->definedScope(['undef1', 'undef2', 'undef3']));
        $this->assertEquals('hoge', $gateway->definedScope('hoge'));
        $this->assertEquals(null, $gateway->definedScope('undef1'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_getScopes($gateway)
    {
        $gateway->addScope('hoge', 'hoge');
        $gateway->addScope('fuga', 'fuga');
        $gateway->addScope('piyo', 'piyo');

        $this->assertEquals(['', 'hoge', 'fuga', 'piyo'], array_keys($gateway->getScopes()));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_getScopeParts($gateway)
    {
        $gateway->addScope('hoge', function ($hoge) {
            return [
                'where' => ['hoge' => $hoge],
            ];
        });
        $gateway->addScope('empty', function () {
            return [
                'where' => ['empty' => 99],
            ];
        });

        // 当てていない状態
        $this->assertEquals(['hoge' => 1], $gateway->getScopeParts('hoge', 1)['where']);
        // 当てている状態で未指定
        $this->assertEquals(['hoge' => 2], $gateway->scope('hoge', 2)->getScopeParts('hoge')['where']);
        // 当てている状態で指定
        $this->assertEquals(['hoge' => 4], $gateway->scope('hoge', 3)->getScopeParts('hoge', 4)['where']);
        // 引数なしクロージャスコープに影響はない
        $this->assertEquals(['empty' => 99], $gateway->getScopeParts('empty')['where']);
        $this->assertEquals(['empty' => 99], $gateway->scope('empty')->getScopeParts('empty')['where']);

        $this->assertException('undefined', L($gateway)->getScopeParts('notfound'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_scoping($gateway)
    {
        $select = $gateway->scoping('NOW(1)', '1=1')->scoping('NOW(2)', '2=2')->scoping('NOW(3)', '3=3')->select();
        $this->assertEquals('SELECT NOW(1), NOW(2), NOW(3) FROM test WHERE (1=1) AND (2=2) AND (3=3)', (string) $select);

        $select = $gateway->where(['' => [1, 2]])->select();
        $this->assertEquals('SELECT * FROM test WHERE test.id IN (?, ?)', (string) $select);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_scoping_k($gateway, $database)
    {
        if ($database->getCompatiblePlatform()->getWrappedPlatform() instanceof SQLServerPlatform) {
            return;
        }

        $select = $gateway->as('T')
            ->orderBy(['name' => 'DESC'])
            ->where('1=1')
            ->column(['c' => new Expression('NOW(?)', 9)])
            ->where(['c3' => 3])
            ->column('NOW(1)')
            ->orderBy(['id' => [true, 'min']])
            ->column(['now' => 'NOW(2)'])
            ->where([['c1' => 1, 'c2' => 2]])
            ->limit(2)
            ->select([]);

        $this->assertEquals('SELECT NOW(?) AS c, NOW(1), NOW(2) AS now FROM test T WHERE (1=1) AND (T.c3 = ?) AND ((T.c1 = ?) OR (T.c2 = ?)) ORDER BY T.name DESC, CASE WHEN T.id IS NULL THEN 0 ELSE 1 END ASC, T.id ASC LIMIT 2', (string) $select);
        $this->assertEquals([9, 3, 1, 2], $select->getParams());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_select($gateway, $database)
    {
        $select = $gateway->select('id', ['!id' => '']);
        $this->assertEquals('SELECT test.id FROM test', (string) $select);
        $this->assertEquals([], $select->getParams());

        $select = $gateway->select('id', ['!id' => 1]);
        $this->assertEquals('SELECT test.id FROM test WHERE test.id = ?', (string) $select);
        $this->assertEquals([1], $select->getParams());

        $select = $gateway->select('id', ['id' => 1]);
        $this->assertIsArray($select->tuple());

        $Article = new TableGateway($database, 't_article', 'Article');
        $select = $Article->select('*', ['article_id' => 1]);
        $this->assertInstanceOf(Entity::class, $select->tuple());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_iterate($gateway, $database)
    {
        $this->assertEquals([
            2 => [
                'id'   => '2',
                'name' => 'b',
                'data' => '',
            ],
            3 => [
                'id'   => '3',
                'name' => 'c',
                'data' => '',
            ],
        ], iterator_to_array($gateway->where(['id' => [2, 3]])->setDefaultIteration('assoc')));
        // 本体に影響はない
        $this->assertEquals([
            [
                'id'   => '2',
                'name' => 'b',
                'data' => '',
            ],
            [
                'id'   => '3',
                'name' => 'c',
                'data' => '',
            ],
        ], iterator_to_array($gateway->where(['id' => [2, 3]])));

        $t_article = new TableGateway($database, 't_article');

        $select = $t_article->scoping('*', ['article_id' => 1]);
        $this->assertInstanceOf(\IteratorAggregate::class, $select);
        $row = iterator_to_array($select)[0];
        $this->assertEquals([
            'article_id' => '1',
            'title'      => 'タイトルです',
            'checks'     => '',
        ], $row);

        $Article = new TableGateway($database, 't_article', 'Article');
        $select = $Article->scoping('*', ['article_id' => 1]);
        $this->assertInstanceOf(\IteratorAggregate::class, $select);
        $row = iterator_to_array($select)[0];
        $this->assertInstanceOf(Article::class, $row);
        $this->assertEquals([
            'article_id' => '1',
            'title'      => 'タイトルです',
            'checks'     => '',
        ], json_decode(json_encode($row), true));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_yield($gateway, $database)
    {
        // 正しく移譲できていることが担保できれば結果はどうでもいい
        $this->assertEquals($gateway->array(), iterator_to_array($gateway->yieldArray()));
        $this->assertEquals($gateway->assoc(), iterator_to_array($gateway->yieldAssoc()));
        $this->assertEquals($gateway->lists(), iterator_to_array($gateway->yieldLists()));
        $this->assertEquals($gateway->pairs(), iterator_to_array($gateway->yieldPairs()));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_export($gateway, $database)
    {
        // 正しく移譲できていることが担保できれば結果はどうでもいい
        $gateway = $gateway->column(['id'])->where(['id' => [1, 2, 3]]);
        $file = sys_get_temp_dir() . '/exportfile.txt';
        $expected = $gateway->array(['name']);

        $gateway->exportArray(['file' => $file], ['name']);
        $this->assertEquals($expected, include $file);

        $gateway->exportCsv(['file' => $file], ['name']);
        $this->assertEquals($expected, csv_import(file_get_contents($file), ['headers' => ['id', 'name']]));

        $gateway->exportJson(['file' => $file], ['name']);
        $this->assertEquals($expected, json_import(file_get_contents($file)));

        $this->assertException("export type 'undefined'", L($gateway)->exportUndefined([]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_neighbor($gateway, $database)
    {
        // 正しく移譲できていることを担保
        $this->assertEquals([
            -1 => ['id' => '4', 'name' => 'd'],
            1  => ['id' => '6', 'name' => 'f'],
        ], $gateway->column(['id', 'name'])->neighbor(['id' => 5]));

        // EntityGateway ならエンティティで返ってくる
        $Test = (new TableGateway($database, 'test', 'Test'));
        $this->assertEquals([
            -1 => $Test->find(4, ['id', 'name']),
            1  => $Test->find(6, ['id', 'name']),
        ], $Test->column(['id', 'name'])->neighbor(['id' => 5]));

        // gateway 版に限り $predicates は省略できる
        $this->assertEquals([
            -1 => ['id' => '4'],
            1  => ['id' => '6'],
        ], $database->multiunique->column(['id'])->uk('e')->neighbor());

        // 上記の UK 版や複数版など
        if ($database->getCompatiblePlatform()->supportsRowConstructor()) {
            $this->assertEquals([
                -1 => ['id' => '4'],
                1  => ['id' => '6'],
            ], $database->multiunique->column(['id'])->uk('e')->neighbor());
            $this->assertEquals([
                -1 => ['mainid' => '1', 'subid' => '4'],
                1  => ['mainid' => '2', 'subid' => '6'],
            ], $database->multiprimary->column(['mainid', 'subid'])->pk([1, 5])->neighbor());
            $this->assertEquals([
                -1 => ['id' => '4'],
                1  => ['id' => '6'],
            ], $database->multiunique->column(['id'])->uk(['e,e', 500])->neighbor());
        }
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_preparing($gateway, $database)
    {
        $platform = $database->getPlatform();
        $cplatform = $database->getCompatiblePlatform();
        $queryInto = function (Statement $stmt, $params) use ($database) {
            return $database->queryInto($stmt->merge($params), $params);
        };

        // select
        $stmt = $gateway->where(['name' => 'b'])->prepareSelect('*', ['id = :id']);
        $this->assertEquals("SELECT test.* FROM test WHERE (test.name = 'b') AND (id = '1')", $queryInto($stmt, ['id' => 1]));
        $this->assertEquals($stmt->executeSelect(['id' => 2])->fetchAllAssociative(), $gateway->array('*', ['id' => 2]));

        $stmt = $database->foreign_p()->where(['name' => 'a'])->prepareSelect([
            'submax'   => $database->foreign_c1()->submax('id'),
            'subcount' => $database->foreign_c2()->subcount(),
        ], [
            'id = :id',
            $database->subexists('foreign_c1'),
            $database->notSubexists('foreign_c2'),
        ]);
        $max = $platform->quoteSingleIdentifier('foreign_c1.id@max');
        $count = $platform->quoteSingleIdentifier('*@count');
        $this->assertStringContainsString("(SELECT MAX(foreign_c1.id) AS $max FROM foreign_c1 WHERE foreign_c1.id = foreign_p.id) AS submax", $queryInto($stmt, ['id' => 1]));
        $this->assertStringContainsString("(SELECT COUNT(*) AS $count FROM foreign_c2 WHERE foreign_c2.cid = foreign_p.id) AS subcount", $queryInto($stmt, ['id' => 1]));
        $this->assertStringContainsString("(EXISTS (SELECT * FROM foreign_c1 WHERE foreign_c1.id = foreign_p.id))", $queryInto($stmt, ['id' => 1]));
        $this->assertStringContainsString("(NOT EXISTS (SELECT * FROM foreign_c2 WHERE foreign_c2.cid = foreign_p.id))", $queryInto($stmt, ['id' => 1]));

        // insert
        $stmt = $gateway->prepareInsert([':name', 'id' => new Expression(':id')]);
        $this->assertEquals("INSERT INTO test (name, id) VALUES ('xxx', '1')", $queryInto($stmt, ['id' => 1, 'name' => 'xxx']));
        if (!$cplatform->supportsIdentityUpdate()) {
            $database->getConnection()->executeStatement($cplatform->getIdentityInsertSQL($gateway->tableName(), true));
        }
        $stmt->executeAffect(['id' => 101, 'name' => 'XXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'YYY']);
        if (!$cplatform->supportsIdentityUpdate()) {
            $database->getConnection()->executeStatement($cplatform->getIdentityInsertSQL($gateway->tableName(), false));
        }
        $this->assertEquals(['XXX', 'YYY'], $gateway->lists('name', ['id' => [101, 102]]));

        // update
        $stmt = $gateway->prepareUpdate([':name'], ['id = :id']);
        $this->assertEquals("UPDATE test SET name = 'xxx' WHERE id = '1'", $queryInto($stmt, ['id' => 1, 'name' => 'xxx']));
        $stmt->executeAffect(['id' => 101, 'name' => 'updateXXX']);
        $stmt->executeAffect(['id' => 102, 'name' => 'updateYYY']);
        $this->assertEquals(['updateXXX', 'updateYYY'], $gateway->lists('name', ['id' => [101, 102]]));

        // delete
        $stmt = $gateway->prepareDelete(['id = :id']);
        $this->assertEquals("DELETE FROM test WHERE id = '1'", $queryInto($stmt, ['id' => 1]));
        $stmt->executeAffect(['id' => 101]);
        $stmt->executeAffect(['id' => 102]);
        $this->assertEquals([], $gateway->lists('name', ['id' => [101, 102]]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_count($gateway)
    {
        // 素で呼ぶと全件
        $this->assertEquals(10, $gateway->count());
        $this->assertCount(10, $gateway);

        // where 後はその結果
        $this->assertEquals(3, $gateway->where(['id' => [1, 2, 3]])->count());
        $this->assertEquals(1, $gateway->where(['id' => [1, 2, 3]])->count('*', ['name' => 'b']));

        // iterate 後はキャッシュ結果
        $this->assertCount(3, $gateway->where(['id' => [1, 2, 3]]));
        $this->assertCount(1, $gateway->where(['id' => [1, 2, 3]])->where(['name' => 'b']));

        // 上記の操作はオリジナルに一切影響を与えない（全件のまま）
        $this->assertEquals(10, $gateway->count());
        $this->assertCount(10, $gateway);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_exists($gateway)
    {
        $this->assertTrue($gateway->exists(['id' => 1]));
        $this->assertFalse($gateway->exists(['id' => 99]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_selectExists($gateway)
    {
        $builder = $gateway->selectExists(['id' => 1]);
        $this->assertEquals('EXISTS (SELECT * FROM test WHERE test.id = ?)', "$builder");
        $this->assertEquals([1], $builder->getParams());

        $builder = $gateway->selectNotExists(['id' => 1]);
        $this->assertEquals('NOT EXISTS (SELECT * FROM test WHERE test.id = ?)', "$builder");
        $this->assertEquals([1], $builder->getParams());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_selectAggregate($gateway, $database)
    {
        $gateway = $database->aggregate;
        $qi = function ($str) use ($database) {
            return $database->getPlatform()->quoteSingleIdentifier($str);
        };

        $builder = $gateway->selectCount('id');
        $this->assertEquals("SELECT COUNT(aggregate.id) AS {$qi('aggregate.id@Count')} FROM aggregate", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals(10, $builder->value());

        $builder = $gateway->selectMax('id');
        $this->assertEquals("SELECT MAX(aggregate.id) AS {$qi('aggregate.id@Max')} FROM aggregate", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals(10, $builder->value());

        $builder = $gateway->selectCount('id', [], ['group_id2']);
        $this->assertEquals("SELECT group_id2, COUNT(aggregate.id) AS {$qi('aggregate.id@Count')} FROM aggregate GROUP BY group_id2", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals([
            10 => 5,
            20 => 5,
        ], $builder->pairs());

        $builder = $gateway->selectMin('id', [], ['group_id2']);
        $this->assertEquals("SELECT group_id2, MIN(aggregate.id) AS {$qi('aggregate.id@Min')} FROM aggregate GROUP BY group_id2", "$builder");
        $this->assertEquals([], $builder->getParams());
        $this->assertEquals([
            10 => 1,
            20 => 6,
        ], $builder->pairs());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_find($gateway)
    {
        $this->assertEquals([
            'id'   => 2,
            'name' => 'b',
            'data' => '',
        ], $gateway->find(2));

        $this->assertEquals([
            'id'   => 2,
            'name' => 'b',
            'data' => '',
        ], $gateway->find([2]));

        $this->assertEquals([
            'name' => 'b',
        ], $gateway->find(2, ['name']));

        $this->assertEquals([
            'name' => 'b',
        ], $gateway->find([2], 'name'));

        $this->assertEquals([
            'name' => 'b',
        ], $gateway->findOrThrow([2], 'name'));
        $this->assertException(new NonSelectedException(), L($gateway)->findOrThrow(999));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_pk($gateway, $database)
    {
        $this->assertEquals('b', $gateway->pk(2)->value('name'));
        $this->assertEquals('b', $gateway->pk([2])->value('name'));
        $this->assertEquals(false, $gateway->where('1=0')->pk(2)->value('name'));
        $this->assertEquals(['a', 'b'], $gateway->pk(1, 2)->lists('name'));

        $this->assertEquals('b', $database->multiprimary()->pk([1, 2])->value('name'));
        $this->assertEquals(false, $database->multiprimary()->pk([99, 99])->value('name'));
        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $database->multiprimary()->pk([1], [2, 6])->lists('name'));

        @$this->assertException('is not match primary columns', L($gateway)->pk([1, 2]));
        @$this->assertException('is not match primary columns', L($database->multiprimary())->pk([1, 2, 3]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_uk($gateway, $database)
    {
        $multiunique = $database->multiunique();

        // 整数を与えれば uc_i が使われる
        $actual = $multiunique->as('M')->uk(1)->select()->queryInto();
        $this->assertEquals("SELECT * FROM multiunique M WHERE M.uc_i = '1'", $actual);

        // 文字列を与えれば uc_s が使われる
        $actual = $multiunique->as('M')->uk('s')->select()->queryInto();
        $this->assertEquals("SELECT * FROM multiunique M WHERE M.uc_s = 's'", $actual);

        // 数が一致してるのが1つなら型は無関係でそれが使われる
        $actual = $multiunique->as('M')->uk(['s', 't'])->select()->queryInto();
        $this->assertEquals("SELECT * FROM multiunique M WHERE (M.uc1 = 's') AND (M.uc2 = 't')", $actual);

        // 複数個も OK
        $this->assertEquals("SELECT * FROM multiunique M WHERE (M.uc_i = '1') OR (M.uc_i = '2')", (string) $multiunique->as('M')->uk(1, 2));
        $this->assertEquals("SELECT * FROM multiunique M WHERE (M.uc_s = 's') OR (M.uc_s = 't')", (string) $multiunique->as('M')->uk('s', 't'));
        $this->assertEquals("SELECT * FROM multiunique M WHERE ((M.uc1 = 's1') AND (M.uc2 = 't1')) OR ((M.uc1 = 's2') AND (M.uc2 = 't2'))", (string) $multiunique->as('M')->uk(['s1', 't1'], ['s2', 't2']));

        // 数が一致しないなら例外
        $this->assertException('not match unique index', L($multiunique)->uk(1, 2, [3, 4]));

        // 型が一致しないなら例外
        $this->assertException('not match unique index', L($multiunique)->uk(1.2));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_paginate($gateway, $database)
    {
        $pagenator = $gateway->where(['id >= 5'])->paginate(2, 2);

        $this->assertEquals([7, 8], array_column($pagenator->getItems(), 'id'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_sequence($gateway, $database)
    {
        $sequencer = $gateway->where([])->sequence(['id' => 5], 2, false);

        $this->assertEquals([4, 3], array_column($sequencer->getItems(), 'id'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_chunk($gateway, $database)
    {
        $gateway = $gateway->where(['id >= 5']);

        $actual = iterator_to_array($gateway->chunk(3), false);
        $this->assertEquals(iterator_to_array($gateway, false), $actual);

        $actual = iterator_to_array($gateway->chunk(3, '-id'), false);
        $this->assertEquals(iterator_to_array($gateway->orderBy('-id'), false), $actual);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_fetch($gateway, $database)
    {
        /// 「正しく委譲されているか？」が確認できればいいので細かい動作はテストしない

        $this->assertEquals([
            'id'     => 2,
            'idname' => '2b',
        ], $gateway->tuple([
            'id',
            'idname' => $database->getCompatiblePlatform()->getConcatExpression('id', 'name'),
        ], ['id' => 2]));

        $this->assertEquals([
            'id'   => 2,
            'name' => 'b',
            'data' => '',
        ], $gateway->tuple('*', ['id' => 2]));

        $this->assertEquals([
            [
                'id'   => 2,
                'name' => 'b',
                'data' => '',
            ],
            [
                'id'   => 4,
                'name' => 'd',
                'data' => '',
            ],
        ], $gateway->array('*', ['id' => [2, 4]]));

        $this->assertException(new NonSelectedException(), L($gateway)->arrayOrThrow('*', ['id' => [999]]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_fetch_lock($gateway, $database)
    {
        // lock が活きてるか、同時指定できてるかが確認できればいいので sqlite のみでいい（ロッククエリはバラバラなので全RDBMSは辛い）
        if ($database->getCompatiblePlatform()->getWrappedPlatform() instanceof SqlitePlatform) {
            $database->setAutoOrder(false);

            $logger = new DebugStack();
            $current = $database->getConnection()->getConfiguration()->getSQLLogger();
            $database->getConnection()->getConfiguration()->setSQLLogger($logger);

            $gateway->arrayForUpdate('*');
            $log = $logger->queries[1];
            $this->assertEquals('SELECT test.* FROM test /* lock for write */', $log['sql']);

            $gateway->findForUpdate(1);
            $log = $logger->queries[2];
            $this->assertEquals('SELECT * FROM test WHERE test.id = ? /* lock for write */', $log['sql']);
            $this->assertEquals([1], $log['params']);

            $this->assertException('record', L($gateway)->findInShareOrThrow(0));
            $log = $logger->queries[3];
            $this->assertEquals('SELECT * FROM test WHERE test.id = ? /* lock for read */', $log['sql']);
            $this->assertEquals([0], $log['params']);

            $this->assertException('record', L($gateway)->findForAffect(0));
            $log = $logger->queries[4];
            $this->assertEquals('SELECT * FROM test WHERE test.id = ? /* lock for write */', $log['sql']);
            $this->assertEquals([0], $log['params']);

            $database->getConnection()->getConfiguration()->setSQLLogger($current);
            $database->setAutoOrder(true);
        }
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_relation($gateway, $database)
    {
        $article = new TableGateway($database, 't_article');
        $comment = new TableGateway($database, 't_comment');

        // 指定の仕方が違うだけで同じものが得られるはず
        $row = $article->tuple(['*', 'Comment' => ['*']], ['article_id' => 1]);
        $find = $article->find(1, ['*', 'Comment' => ['*']]);
        $this->assertEquals($row, $find);

        // 指定の仕方が違うだけで同じものが得られるはず
        $find = $comment->find(2, ['*', 'Article' => ['*']]);
        $row = $comment->tuple(['*', 'Article' => ['*']], ['comment_id' => 2]);
        $this->assertEquals($row, $find);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_foreign($gateway, $database)
    {
        $select = $database->foreign_s()->select([
            'has_s1' => $database->foreign_sc()->foreign('fk_sc1')->subexists(),
            'has_s2' => $database->foreign_sc()->foreign('fk_sc2')->subexists(),
        ]);
        $exists1 = $database->getCompatiblePlatform()->convertSelectExistsQuery('EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id1 = foreign_s.id)');
        $exists2 = $database->getCompatiblePlatform()->convertSelectExistsQuery('EXISTS (SELECT * FROM foreign_sc WHERE foreign_sc.s_id2 = foreign_s.id)');
        $this->assertEquals("SELECT $exists1 AS has_s1, $exists2 AS has_s2 FROM foreign_s", "$select");

        // オリジナルは変更されないはず
        $this->assertNull($database->foreign_sc()->foreign());
        // チェーンすれば指定したものが得られるはず
        $this->assertEquals('fk_sc1', $database->foreign_sc()->foreign('fk_sc1')->foreign());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_hint($gateway, $database)
    {
        $article = new TableGateway($database, 't_article');
        $comment = new TableGateway($database, 't_comment');

        $select = $article->hint('HintA')->joinForeign($comment->hint('HintC'))->select();
        $this->assertEquals('SELECT * FROM t_article HintA LEFT JOIN t_comment HintC ON t_comment.article_id = t_article.article_id', "$select");
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_aggregate($gateway)
    {
        $this->assertEquals(10, $gateway->count());
        $this->assertEquals(1, $gateway->min('id'));
        $this->assertEquals(10, $gateway->max('id'));
        $this->assertEquals(55, $gateway->sum('id'));
        $this->assertEquals(5.5, $gateway->avg('id'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_dryrun($gateway)
    {
        // クエリ文字列を返す
        $this->assertEquals("DELETE FROM test WHERE test.id = '1'", $gateway->dryrun()->delete(['id' => 1]));

        // Context で実装されているのでこの段階では普通に実行される
        $this->assertEquals(1, $gateway->delete(['id' => 2]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_affect($gateway, $database)
    {
        /// 「正しく委譲されているか？」が確認できればいいので細かい動作はテストしない

        $count = $gateway->count();

        // insert すると1件増えるはず
        $gateway->insert(['name' => 'A']);
        $this->assertEquals($count + 1, $count = $gateway->count());

        // insertOrThrow すると1件増えて主キーが返ってくるはず
        $pri = $gateway->insertOrThrow(['name' => 'A']);
        $this->assertEquals($count + 1, $count = $gateway->count());
        $this->assertEquals(['id' => $count], $pri);

        // update すると更新されるはず
        $gateway->update(['name' => 'XXX'], $pri);
        $this->assertEquals('XXX', $gateway->value('name', $pri));

        // updateOrThrow すると更新されて主キーが返ってくるはず
        $pri = $gateway->updateOrThrow(['name' => 'YYY'], $pri);
        $this->assertEquals('YYY', $gateway->value('name', $pri));
        $this->assertEquals(['id' => $count], $pri);

        // 主キー有りで upsert すると更新されるはず
        $gateway->upsert($pri + ['name' => 'ZZZ']);
        $this->assertEquals($count, $count = $gateway->count());
        $this->assertEquals('ZZZ', $gateway->value('name', $pri));

        // 主キー無しで upsert すると挿入されるはず
        $pri = $gateway->upsertOrThrow(['name' => 'KKK']);
        $this->assertEquals($count + 1, $count = $gateway->count());
        $this->assertEquals('KKK', $gateway->value('name', $pri));

        // delete すると1件減るはず
        $gateway->delete($pri);
        $this->assertEquals($count - 1, $count = $gateway->count());

        /// insertSelect
        $gateway->insertSelect($gateway->select(['name', 'data']), ['name', 'data']);
        $this->assertEquals($count * 2, $count = $gateway->count());
        $this->assertEquals(24, $count);

        // truncate すると全て吹き飛ぶはず
        $gateway->truncate();
        $this->assertEquals(0, $gateway->count());

        /// array 系
        $gateway->insertArray([
            ['name' => 'XXX'],
            ['name' => 'YYY'],
        ]);
        $this->assertEquals(['XXX', 'YYY'], $gateway->lists('name'));

        $gateway->changeArray([
            ['id' => 2, 'name' => 'XXX'],
            ['name' => 'XXX'],
        ], ['name' => 'XXX']);
        $this->assertEquals(['XXX', 'XXX'], $gateway->lists('name'));

        if ($database->getCompatiblePlatform()->supportsMerge()) {
            $gateway->updateArray([
                ['id' => 1, 'name' => 'xxx'],
                ['id' => 2, 'name' => 'yyy'],
                ['id' => 3, 'name' => 'zzz'],
            ]);
            $this->assertEquals(['yyy', 'zzz'], $gateway->lists('name'));

            $gateway->modifyArray([
                ['id' => 1, 'name' => 'AAA'],
                ['id' => 2, 'name' => 'BBB'],
                ['id' => 3, 'name' => 'CCC'],
            ]);
            $this->assertEquals(['AAA', 'BBB', 'CCC'], $gateway->lists('name'));
        }
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_affect_ignore($gateway, $database)
    {
        if ($database->getCompatiblePlatform()->supportsIgnore()) {
            $this->assertEquals(['id' => '11'], $gateway->insertIgnore(['id' => 11, 'name' => 'hoge']));
            $this->assertEquals([], $gateway->insertIgnore(['id' => 11, 'name' => 'hoge']));

            $this->assertEquals(['id' => '11'], $gateway->updateIgnore(['name' => 'fuga'], ['id' => 11]));
            $this->assertEquals([], $gateway->updateIgnore(['name' => 'hoge'], ['id' => -1]));

            $this->assertEquals(['id' => '11'], $gateway->saveIgnore(['id' => 11]));

            // array 系
            $gateway = $database->noauto;
            $gateway->truncate();
            $gateway->insert(['id' => 1, 'name' => '']);
            $gateway->insert(['id' => 2, 'name' => '']);
            $this->assertEquals(0, $gateway->insertSelectIgnore('select 1 union select 2', ['id']));
            $this->assertEquals(0, $gateway->insertArrayIgnore([
                ['id' => 1, 'name' => ''],
                ['id' => 2, 'name' => ''],
            ]));
            $this->assertEquals(0, $gateway->updateArrayIgnore([
                ['id' => 1, 'name' => null],
                ['id' => 2, 'name' => null],
            ]));
            $this->assertEquals(0, $gateway->modifyArrayIgnore([
                ['id' => 1, 'name' => null],
                ['id' => 2, 'name' => null],
            ]));
            $this->assertEquals([
                [],
                [],
            ], $gateway->changeArrayIgnore([
                ['name' => null],
                ['name' => null],
            ], false));

            $database->import([
                'foreign_p' => [
                    [
                        'id'         => 1,
                        'name'       => 'P1',
                        'foreign_c1' => [],
                    ],
                    [
                        'id'         => 2,
                        'name'       => 'P2',
                        'foreign_c1' => [['seq' => 1, 'name' => 'C']],
                    ],
                ],
            ]);
        }
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_affect_conditionally($gateway, $database)
    {
        $this->assertEquals(['id' => '11'], $gateway->createConditionally(['id' => -1], ['name' => 'xxxx']));
        $this->assertEquals(['id' => '12'], $gateway->insertConditionally(['id' => -1], ['name' => 'hoge']));
        $this->assertEquals(['id' => '13'], $gateway->upsertConditionally(['id' => -1], ['name' => 'fuga']));
        $this->assertEquals(['id' => '14'], $gateway->modifyConditionally(['id' => -1], ['name' => 'piyo']));

        $this->assertEquals([], $gateway->createConditionally(['id' => 11], ['name' => 'xxxx']));
        $this->assertEquals([], $gateway->insertConditionally(['id' => 12], ['name' => 'hoge']));
        $this->assertEquals([], $gateway->upsertConditionally(['id' => 13], ['name' => 'fuga']));
        $this->assertEquals([], $gateway->modifyConditionally(['id' => 14], ['name' => 'piyo']));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_affect_override($gateway, $database)
    {
        if (!$database->getCompatiblePlatform()->supportsIgnore()) {
            return;
        }

        $gateway = new class($database, 'test') extends TableGateway {
            public $called = [];

            public function insert($data)
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function update($data, array $identifier = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function delete(array $identifier = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function remove(array $identifier = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function destroy(array $identifier = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function reduce($limit = null, $orderBy = [], $groupBy = [], $identifier = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function create($data)
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function upsert($insertData, $updateData = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function modify($insertData, $updateData = [])
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function replace($insertData)
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }

            public function truncate($cascade = false)
            {
                $this->called[] = __FUNCTION__;
                return parent::{__FUNCTION__}(...func_get_args());
            }
        };
        $gateway->truncate();
        $gateway->createIgnore(['id' => 1]);
        $gateway->upsertOrThrow(['id' => 2]);
        $gateway->modifyConditionally(['id' => 3], ['id' => 3]);
        $gateway->insertIgnore(['id' => 4]);
        $gateway->insertConditionally(['id' => 5], ['id' => 5]);
        $gateway->updateIgnore(['name' => 'aaa'], ['id' => 1]);
        $gateway->updateOrThrow(['name' => 'aaa'], ['id' => 2]);
        $gateway->replaceOrThrow(['id' => 3, 'name' => 'aaa']);
        $gateway->deleteOrThrow(['id' => 1]);
        $gateway->removeOrThrow(['id' => 2]);
        $gateway->destroyOrThrow(['id' => 3]);
        $gateway->reduceOrThrow(1, 'id');

        // サフィックス付きでもオーバーライドしたメソッドが呼ばれている
        $this->assertEquals([
            'truncate',
            'create',
            'upsert',
            'modify',
            'insert',
            'insert',
            'update',
            'update',
            'replace',
            'delete',
            'remove',
            'destroy',
            'reduce',
        ], $gateway->called);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_scoped_affect($gateway, $database)
    {
        $logger = new DebugStack();
        $lastsql = function () use ($logger) {
            $last = end($logger->queries);
            return [$last['sql'] => $last['params']];
        };
        $database->getConnection()->getConfiguration()->setSQLLogger($logger);
        $database->setAutoOrder(false);

        // for SQLServer
        if ($database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            // select の bulk insert になる
            $gateway->scoping([
                'id' => 'id * 100',
                'name',
                'data',
            ], ['id' => 1])->insert(['name' => 'A']);
            $this->assertEquals(['INSERT INTO test SELECT test.id * 100 AS id, test.name, test.data FROM test WHERE test.id = ?' => [1]], $lastsql());
        }

        // scope の where が効いた update になる
        $gateway->where(['id' => 1])->update(['name' => 'XXX']);
        $this->assertEquals(['UPDATE test SET name = ? WHERE test.id = ?' => ['XXX', 1]], $lastsql());

        // scope の where が効いた delete になる
        $gateway->where(['id' => 1])->delete();
        $this->assertEquals(['DELETE FROM test WHERE test.id = ?' => [1]], $lastsql());

        // scope の where が効いていない update になる
        $gateway->addScope('hogehoge', [], ['id' => 1]);
        $gateway->setIgnoreAffectScope(['hogehoge']);
        $gateway->update(['name' => 'XXX']);
        $this->assertEquals(['UPDATE test SET name = ?' => ['XXX']], $lastsql());

        // ORDER,LIMIT が効いた update になる
        if ($database->getPlatform() instanceof SqlitePlatform) {
            try {
                $gateway->where(['id' => 1])->orderBy('id')->limit(1)->update(['name' => 'XXX']);
            }
            catch (\Exception $ex) {
                $this->assertEquals(['UPDATE test SET name = ? WHERE test.id = ? ORDER BY id ASC LIMIT 1' => ['XXX', 1]], $lastsql());
            }
        }

        // ORDER,LIMIT が効いた delete になる
        if ($database->getPlatform() instanceof SqlitePlatform) {
            try {
                $gateway->where(['id' => 1])->orderBy('id')->limit(1)->delete();
            }
            catch (\Exception $ex) {
                $this->assertEquals(['DELETE FROM test WHERE test.id = ? ORDER BY id ASC LIMIT 1' => [1]], $lastsql());
            }
        }

        // JOIN された update になる
        if ($database->getPlatform() instanceof SqlitePlatform) {
            try {
                $gateway->joinOn(new TableGateway($database, 'test1'), '1=1')->where(['id' => 1])->update(['name' => 'XXX']);
            }
            catch (\Exception $ex) {
                $this->assertEquals(['UPDATE test INNER JOIN test1 test1 ON 1=1 SET name = ? WHERE test.id = ?' => ['XXX', 1]], $lastsql());
            }
        }

        // JOIN された delete になる
        if ($database->getPlatform() instanceof SqlitePlatform) {
            try {
                $gateway->joinOn(new TableGateway($database, 'test1'), '1=1')->where(['id' => 1])->delete();
            }
            catch (\Exception $ex) {
                $this->assertEquals(['DELETE test FROM test INNER JOIN test1 test1 ON 1=1 WHERE test.id = ?' => [1]], $lastsql());
            }
        }

        // JOIN された deleteIfPossible になる
        if ($database->getPlatform() instanceof SqlitePlatform) {
            try {
                $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
                $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
                $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
                $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);
                (new TableGateway($database, 'foreign_p'))->joinForeign((new TableGateway($database, 'foreign_c1'))->where(['seq' => 11]))->where(['id' => 2])->remove();
            }
            catch (\Exception $ex) {
                $this->assertEquals(["DELETE foreign_p FROM foreign_p INNER JOIN foreign_c1 foreign_c1 ON foreign_p.id = foreign_c1.id AND foreign_c1.seq = '11' WHERE (foreign_p.id = ?) AND ((NOT EXISTS (SELECT * FROM foreign_c1 WHERE foreign_p.id = foreign_c1.id))) AND ((NOT EXISTS (SELECT * FROM foreign_c2 WHERE foreign_p.id = foreign_c2.cid)))" => [2]], $lastsql());
            }
        }

        $this->assertException('not allow affect query', L($gateway->having('1=1'))->update([]));
        $this->assertException('not allow affect query', L($gateway->having('1=1'))->delete([]));

        $database->getConnection()->getConfiguration()->setSQLLogger(null);
        $database->setAutoOrder(true);

        // for SQLServer
        if (!$database->getCompatiblePlatform()->supportsIdentityUpdate()) {
            $database->executeAffect($database->getCompatiblePlatform()->getIdentityInsertSQL('test', false));
        }
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_save($gateway, $database)
    {
        $t_article = new TableGateway($database, 't_article');

        $primary = $t_article->save([
            'article_id' => 3,
            'title'      => 'saved',
            'checks'     => '',
            't_comment'  => [
                [
                    'comment' => 'saved comment',
                ],
            ],
        ]);
        $this->assertEquals([
            'article_id' => 3,
            't_comment'  => [
                [
                    'comment_id' => 4,
                ],
            ],
        ], $primary);

        $primary = $t_article->save([
            'article_id' => $primary['article_id'],
            'title'      => 'saved2',
            'checks'     => '',
            't_comment'  => [
                [
                    'comment' => 'saved comment',
                ],
                [
                    'comment' => 'saved comment',
                ],
            ],
        ]);
        $this->assertEquals([
            'article_id' => 3,
            't_comment'  => [
                [
                    'comment_id' => 5,
                ],
                [
                    'comment_id' => 6,
                ],
            ],
        ], $primary);

        $article = $t_article->pk($primary['article_id'])->tuple([
            'article_id',
            't_comment comments' => [
                'comment_id',
            ],
        ]);
        $this->assertEquals([
            'article_id' => 3,
            'comments'   => [
                5 => [
                    'comment_id' => 5,
                ],
                6 => [
                    'comment_id' => 6,
                ],
            ],
        ], $article);

        $this->assertEquals(3, $database->count('t_article'));
        $this->assertEquals(5, $database->count('t_comment'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_remove($gateway, $database)
    {
        // CASCADE なのですべて吹き飛ぶ
        $t_article = new TableGateway($database, 't_article');
        $t_article->remove();
        $this->assertEmpty($t_article->array());

        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);

        $foreign_p = new TableGateway($database, 'foreign_p');
        $affected = $foreign_p->remove([
            'id' => [1, 2],
        ]);

        // 1 は子供で使われていて 3 は指定していない。結果 2 しか消えない
        $this->assertEquals(1, $affected);
        $this->assertEquals([1, 3], $foreign_p->lists('id'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_destroy($gateway, $database)
    {
        $database->insert('foreign_p', ['id' => 1, 'name' => 'name1']);
        $database->insert('foreign_p', ['id' => 2, 'name' => 'name2']);
        $database->insert('foreign_p', ['id' => 3, 'name' => 'name3']);
        $database->insert('foreign_c1', ['id' => 1, 'seq' => 11, 'name' => 'c1name1']);

        $foreign_p = new TableGateway($database, 'foreign_p');
        $affected = $foreign_p->destroy([
            'id' => [1, 2],
        ]);

        // 指定していない 3 しか残らない
        $this->assertEquals(3, $affected);
        $this->assertEquals([3], $foreign_p->lists('id'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_reduce($gateway, $database)
    {
        $oprlog = new TableGateway($database, 'oprlog');
        $this->assertEquals(35, $oprlog->reduce(10, '-log_date', [], ['category' => 'category-9']));
        $this->assertEquals(26, $oprlog->where(["category" => 'category-8'])->orderBy(['log_date' => false])->groupBy('category')->limit(10)->reduce());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_entity($gateway, $database)
    {
        $Article = new TableGateway($database, 't_article', 'Article');
        $t_article = new TableGateway($database, 't_article');

        // Article で作成した Gateway はエンティティで返すはず
        $this->assertInstanceOf(Article::class, $Article->find(1));
        // t_article で作成した Gateway は配列で返すはず
        $this->assertIsArray($t_article->find(1));

        // alias しても大丈夫
        $this->assertInstanceOf(Article::class, $Article->as('A')->find(1));

        // lists, pairs, value には効かない
        $this->assertSame($Article->lists(), $t_article->lists());
        $this->assertSame($Article->pairs(), $t_article->pairs());
        $this->assertSame($Article->limit(1)->value(), $t_article->limit(1)->value());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_describe($gateway)
    {
        $this->assertEquals('test', $gateway->describe()->getName());
        $this->assertCount(3, $gateway->describe()->getColumns());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_getEmptyRecord($gateway, $database)
    {
        $Article = new TableGateway($database, 't_article', 'Article');
        $t_article = new TableGateway($database, 't_article');

        $entity = $Article->getEmptyRecord(['title' => 'hoge']);
        $record = $t_article->getEmptyRecord(['title' => 'hoge']);

        // Article で作成した Gateway はエンティティで返すはず
        $this->assertInstanceOf(Article::class, $entity);
        // t_article で作成した Gateway は配列で返すはず
        $this->assertIsArray($record);

        // デフォルト値が効いてるはず
        $this->assertEquals('hoge', $entity['title']);
        $this->assertEquals('hoge', $record['title']);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_gather($gateway, $database)
    {
        $t_article = new TableGateway($database, 't_article');
        $this->assertEquals([
            't_article' => [
                1 => ['article_id' => '1'],
            ],
            't_comment' => [
                1 => ['comment_id' => '1'],
                3 => ['comment_id' => '3'],
            ],
        ], $t_article->pk(1)->gather([], ['t_comment' => ['comment_id <> ?' => 2]]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_subquery($gateway, $database)
    {
        $Article = new TableGateway($database, 't_article', 'Article');
        $t_comment = new TableGateway($database, 't_comment');

        $this->assertStringIgnoreBreak("SELECT Article.article_id,
(SELECT t_comment.article_id FROM t_comment WHERE t_comment.article_id = Article.article_id) AS has_comment
FROM t_article Article", $Article->column([
            'article_id',
            'has_comment' => $t_comment->subquery(),
        ]));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_subexists($gateway, $database)
    {
        $Article = new TableGateway($database, 't_article', 'Article');
        $t_comment = new TableGateway($database, 't_comment');

        $rows = $Article->assoc([
            'article_id',
            'has_comment'    => $t_comment->subexists(),
            'nothas_comment' => $t_comment->notSubexists(),
        ]);
        $this->assertTrue(!!$rows[1]['has_comment']);
        $this->assertFalse(!!$rows[1]['nothas_comment']);
        $this->assertFalse(!!$rows[2]['has_comment']);
        $this->assertTrue(!!$rows[2]['nothas_comment']);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_subaggregate($gateway, $database)
    {
        $row = $database->t_article()->pk(1)->tuple([
            'cmin' => $database->t_comment()->submin('comment_id'),
            'cmax' => $database->t_comment()->submax('comment_id'),
        ]);
        $this->assertEquals('1', $row['cmin']);
        $this->assertEquals('3', $row['cmax']);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_subselect($gateway, $database)
    {
        $test1 = new TableGateway($database, 'test1');
        $test2 = new TableGateway($database, 'test2');

        $rows = $test1->assoc([
            'tests2s{id}' => $test2->subselectAssoc('*'),
        ], ['id' => 1]);
        $this->assertEquals([
            1 => [
                'id'      => '1',
                'name1'   => 'a',
                'tests2s' => [
                    1 => [
                        'id'    => '1',
                        'name2' => 'A',
                    ],
                ],
            ],
        ], $rows);

        $rows = $test1->assoc([
            'tests2s{id}' => $test2->subpairs('*'),
        ], ['id' => 1]);
        $this->assertEquals([
            1 => [
                'id'      => '1',
                'name1'   => 'a',
                'tests2s' => [
                    1 => 'A',
                ],
            ],
        ], $rows);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_modifier($gateway, $database)
    {
        $t_article = new TableGateway($database, 't_article');
        $t_comment = new TableGateway($database, 't_comment');

        // 素。何も問題ない
        $query = $t_article->as('A')->where([
            $t_comment->as('C')->subexists(),
        ])->select()->queryInto();
        $this->assertEquals('SELECT * FROM t_article A WHERE (EXISTS (SELECT * FROM t_comment C WHERE C.article_id = A.article_id))', $query);

        // emptyCondition
        $query = $t_article->as('A')->where([
            $t_comment->as('C')->subexists('*', ['!id' => null]),
        ])->select()->queryInto();
        $this->assertEquals('SELECT * FROM t_article A', $query);

        // サブクエリ
        $query = $t_article->as('A')->where([
            'article_id' => $t_comment->as('C')->select('article_id'),
        ])->select()->queryInto();
        $this->assertEquals('SELECT * FROM t_article A WHERE A.article_id IN (SELECT C.article_id FROM t_comment C)', $query);

        // 結合テーブル明示（動いていない時代があった）
        $query = $t_article->as('A')->where([
            'A' => $t_comment->as('C')->subexists(),
        ])->select()->queryInto();
        $this->assertEquals('SELECT * FROM t_article A WHERE (EXISTS (SELECT * FROM t_comment C WHERE C.article_id = A.article_id))', $query);

        // HAVING や ORDER の自動修飾子は邪魔なだけ…なんだが場合によっては WHERE に関数カマすこともあるし、それを言い出すと「自動修飾自体が邪魔」となる
        // 将来的には「自動修飾オプション」を設ける。このテストはさしあたり「.があれば修飾されない」を確認するもの
        $query = $t_article->as('A')->having([
            'FUNC(A.article_id)' => 1,
        ])->orderBy([
            'FUNC(A.article_id)' => 'ASC',
        ])->select()->queryInto();
        $this->assertEquals("SELECT * FROM t_article A HAVING FUNC(A.article_id) = '1' ORDER BY FUNC(A.article_id) ASC", $query);
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_join($gateway, $database)
    {
        $Article = new TableGateway($database, 't_article', 'Article');
        $t_comment = new TableGateway($database, 't_comment');

        // column
        $select = $Article->scoping('article_id')->joinForeign($t_comment->as('C')->scoping('comment_id'))->select(null);
        $this->assertEquals('SELECT Article.article_id, C.comment_id FROM t_article Article LEFT JOIN t_comment C ON C.article_id = Article.article_id', "$select");

        // where
        $select = $Article->joinForeign($t_comment->as('C')->scoping([], ['1=1']))->select();
        $this->assertEquals('SELECT * FROM t_article Article LEFT JOIN t_comment C ON (C.article_id = Article.article_id) AND (1=1)', "$select");

        // orderBy
        $select = $Article->scoping(null, [], ['article_id' => 'ASC'])->joinForeign($t_comment->as('C')->scoping([], ['1=1'], ['comment_id' => 'DESC']))->select();
        $this->assertEquals('SELECT * FROM t_article Article LEFT JOIN t_comment C ON (C.article_id = Article.article_id) AND (1=1) ORDER BY Article.article_id ASC, C.comment_id DESC', "$select");

        // foreignOn
        $select = $Article->joinForeignOn($t_comment->as('C')->scoping([]), 'C.delete_flg = 0')->select();
        $this->assertEquals('SELECT * FROM t_article Article LEFT JOIN t_comment C ON (C.article_id = Article.article_id) AND (C.delete_flg = 0)', "$select");

        // innerOn
        $select = $Article->innerJoinOn($t_comment->as('C')->scoping([], ['1=1']), [])->select();
        $this->assertEquals('SELECT * FROM t_article Article INNER JOIN t_comment C ON 1=1', "$select");

        // rightOn where/groupBy
        $select = $Article->rightJoinOn($t_comment->as('C')->scoping([], ['a' => 1, ['b' => 2, 'c' => 3]], [], [], 'comment_id'), '1=1')->select();
        $this->assertEquals('SELECT * FROM t_article Article RIGHT JOIN (SELECT * FROM t_comment C WHERE (C.a = ?) AND ((C.b = ?) OR (C.c = ?)) GROUP BY comment_id) C ON 1=1', "$select");

        // magic join
        $select = $database->t_article('article_id', 'article_id=1')->as('A')->t_comment(['comment_id'], ['comment_id' => 1])->select([]);
        $this->assertEquals('SELECT A.article_id, t_comment.comment_id FROM t_article A LEFT JOIN t_comment ON (t_comment.article_id = A.article_id) AND (t_comment.comment_id = ?) WHERE article_id=1', "$select");
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_column($gateway, $database)
    {
        $Article = new TableGateway($database, 't_article', 'Article');
        $t_comment = new TableGateway($database, 't_comment');

        // スコープ無しは * のはず
        $select = $Article->select();
        $this->assertEquals('SELECT * FROM t_article Article', "$select");

        // [] は何も取得しないはず
        $select = $Article->column([])->select(null);
        $this->assertEquals('SELECT * FROM t_article Article', "$select");

        // スコープを当てればそれのはず
        $select = $Article->column('article_id')->select(null);
        $this->assertEquals('SELECT Article.article_id FROM t_article Article', "$select");

        // join してもスコープ無しは * のはず
        $select = $Article->as('A')->joinForeign($t_comment->as('C'))->select();
        $this->assertEquals('SELECT * FROM t_article A LEFT JOIN t_comment C ON C.article_id = A.article_id', "$select");

        // join しても [] は何も取得しないはず
        $select = $Article->as('A')->column([])->joinForeign($t_comment->as('C')->column([]))->select();
        $this->assertEquals('SELECT * FROM t_article A LEFT JOIN t_comment C ON C.article_id = A.article_id', "$select");

        // join しても スコープを当てればそれのはず
        $select = $Article->as('A')->column('article_id')->joinForeign($t_comment->as('C')->column('article_id'))->select(null);
        $this->assertEquals('SELECT A.article_id, C.article_id FROM t_article A LEFT JOIN t_comment C ON C.article_id = A.article_id', "$select");

        // Join 記法も受け付けられるので一応テスト
        $select = $Article->as('A')->column([
            'article_id',
            '+t_comment C' => [],
        ])->select(null);
        $this->assertEquals('SELECT A.article_id FROM t_article A INNER JOIN t_comment C ON C.article_id = A.article_id', "$select");

        $select = $Article->as('A')->column([
            '+t_comment C' => [],
        ])->select(null);
        $this->assertEquals('SELECT * FROM t_article A INNER JOIN t_comment C ON C.article_id = A.article_id', "$select");

        $select = $Article->as('A')->column([
            '+C' => $t_comment->column([]),
        ])->select(null);
        $this->assertEquals('SELECT * FROM t_article A INNER JOIN t_comment C ON C.article_id = A.article_id', "$select");
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $_
     * @param Database $database
     */
    function test_magic_join($_, $database)
    {
        $expected = $database->g_parent->where('2')->joinForeign($database->g_child()->where('3'));
        $p = $database->g_parent->where('2')->g_child->where('3')->end();
        $this->assertEquals($expected->select(), $p->select());

        $expected = $database->Article()->joinForeign($database->Comment());
        $p = $database->Article()->Comment()->end();
        $this->assertEquals($expected->select(), $p->select());

        $expected = $database->g_ancestor()->where('1')->joinForeign($database->g_parent()->where('2')->joinForeign($database->g_child()->where('3')));

        // 部分的に magic した記法
        $actual = $database->g_ancestor->where('1')->joinForeign($database->g_parent->where('2')->g_child->where('3')->end());
        $this->assertEquals($expected->select(), $actual->select());
        // 全て magic した記法
        $actual = $database->g_ancestor()->where('1')->g_parent->where('2')->g_child->where('3')->end();
        $this->assertEquals($expected->select(), $actual->select());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $_
     * @param Database $database
     */
    function test_magic_join_method($_, $database)
    {
        // t_article は LEFT になるようにクラス変数で定義されている
        $expected = $database->t_article()->t_comment()->end();
        $this->assertEquals('SELECT * FROM t_article LEFT JOIN t_comment ON t_comment.article_id = t_article.article_id', '' . $expected->select());

        // もちろん直指定すればそちらが優先される
        $expected = $database->t_article()->setDefaultJoinMethod('right')->t_comment()->end();
        $this->assertEquals('SELECT * FROM t_article RIGHT JOIN t_comment ON t_comment.article_id = t_article.article_id', '' . $expected->select());

        // alias が効かない不具合があったのでテスト
        $expected = $database->t_article()->as('A')->t_comment->as('C')->end();
        $this->assertEquals('SELECT * FROM t_article A RIGHT JOIN t_comment C ON C.article_id = A.article_id', '' . $expected->select());
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     * @param Database $database
     */
    function test_magic_scope_column($gateway, $database)
    {
        $database->refresh();
        $database = $database->context(['registerSpecialMethod' => true]);

        // set から始まるメソッドで仮想カラムの更新ができる
        $database->t_article->pk(1)->update([
            'title_checks' => 'hello world:1,2,3',
        ]);
        // get から始まるメソッドで仮想カラムの取得ができる
        $this->assertEquals([
            'article_id'    => '1',
            'title'         => 'hello world',
            'checks'        => '1,2,3',
            'statement'     => 'HELLO WORLD',
            'closure'       => '1-hello world',
            'query_builder' => '3',
        ], $database->t_article->scope('id', 1)->tuple([
            '*',
            'statement',
            'closure',
            'query_builder',
        ]));

        // statement はアノテーションで implicit を指定してるので ! で引っ張ることができる
        $this->assertArrayHasKey('statement', $database->t_article->pk(1)->tuple('!'));
    }

    /**
     * @dataProvider provideGateway
     * @param TableGateway $gateway
     */
    function test_proxyAutoIncrement($gateway)
    {
        // プロキシメソッドなので設定して取得する単純なテスト
        $gateway->resetAutoIncrement(55);
        $gateway->insert(['name' => 'hoge']);
        $lastid = $gateway->getLastInsertId('id');
        $this->assertEquals($gateway->max('id'), $lastid);
    }
}
