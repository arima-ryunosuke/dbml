<?php

namespace ryunosuke\Test;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use ryunosuke\dbml\Query\AffectBuilder;
use ryunosuke\dbml\Query\Clause\Where;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\Test\Entity\Article;
use ryunosuke\Test\Entity\Comment;
use function ryunosuke\dbml\try_return;

/**
 * 複数のクラスをまたがる結合テストのようなテスト（その他大勢とも言う）
 */
class IntegrationTest extends AbstractUnitTestCase
{
    /**
     * Cスタイルコメントがエラーを出さないか
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_insteadof($database)
    {
        $sb = new class($database) extends SelectBuilder {
            public function hoge()
            {
                return 'hoge';
            }
        };

        $sb::insteadof();
        $select = $database->select('test');
        $this->assertInstanceOf(get_class($sb), $select);
        $this->assertEquals('hoge', $select->hoge());

        SelectBuilder::insteadof(get_class($sb));
        $select = $database->select('test');
        $this->assertInstanceOf(get_class($sb), $select);
        $this->assertEquals('hoge', $select->hoge());

        SelectBuilder::insteadof();
        $select = $database->select('test');
        $this->assertInstanceOf(SelectBuilder::class, $select);
    }

    /**
     * Cスタイルコメントがエラーを出さないか
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_commentize_exec($database)
    {
        $cplatform = $database->getCompatiblePlatform();
        $this->assertCount(10, $database->fetchArray('select name ' . $cplatform->commentize("hoge\nfuga", true) . ' from test'));
    }

    /**
     * operator した条件で having するようなちょっと特殊な状況
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_column_having($database)
    {
        // group by なしの having は mysql でしか動かない
        if ($database->getPlatform() instanceof MySQLPlatform) {
            $select = $database->select([
                't_article' => ['*'],
                ''          => [
                    'cond_id_in'      => $database->operator(['article_id' => [1, 3]]),
                    'cond_title_like' => $database->operator(['title:%LIKE%' => 'タイトル']),
                ],
            ]);
            $select->having([
                'cond_id_in'      => 1,
                'cond_title_like' => 1,
            ]);
            // SELECT 句と HAVING 句に出現することを担保した上で・・・
            $this->assertEquals("SELECT t_article.*, (article_id IN (?,?)) AS cond_id_in, (title LIKE ?) AS cond_title_like FROM t_article HAVING (cond_id_in = ?) AND (cond_title_like = ?)", "$select");
            // 実行結果が絞り込まれていることをテストする
            $this->assertEquals([
                [
                    'article_id'      => '1',
                    'title'           => 'タイトルです',
                    'cond_id_in'      => '1',
                    'cond_title_like' => '1',
                    'checks'          => '',
                    'delete_at'       => null,
                ],
            ], $select->array());
        }
    }

    /**
     * 若干複雑な Operator の実際の実行
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_operator($database)
    {
        $this->trapThrowable('is not supported');

        // spaceship
        $this->assertEquals([5], $database->selectLists('nullable', [
            'cint:<=>' => 0,
        ]));
        $this->assertEquals([2, 4, 6, 8, 10], $database->selectLists('nullable', [
            'cint:<=>' => null,
        ]));

        // phrase
        $database->truncate('test');
        $database->insertArray('test', [
            ['data' => 'this is sentence: "PHP is a general-purpose scripting language geared towards web development."'],
            ['data' => 'this is formula: (A1+B1-Z1)'],
            ['data' => 'this is sourcecode: `json_encode([1, 2, 3], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT);`'],
        ]);
        $this->assertEquals([1, 2, 3], $database->selectLists('test.id', [
            'data:phrase' => "this",
        ]));
        $this->assertEquals([1, 3], $database->selectLists('test.id', [
            'data:phrase' => "-Z1", // "Z1" 除外
        ]));
        $this->assertEquals([2], $database->selectLists('test.id', [
            'data:phrase' => '"-Z1"', // "-Z1" 包含
        ]));
        $this->assertEquals([1, 3], $database->selectLists('test.id', [
            'data:phrase' => "this php, json",
        ]));
        $this->assertEquals([1], $database->selectLists('test.id', [
            'data:phrase' => "php script",
        ]));
        $this->assertEquals([1, 3], $database->selectLists('test.id', [
            'data:phrase' => "s*ce", // sentence, source
        ]));
        $this->assertEquals([1, 2], $database->selectLists('test.id', [
            'data:phrase' => "this sentence | this formula",
        ]));
        $this->assertEquals([2], $database->selectLists('test.id', [
            'data:phrase' => "this sentence -php | this formula",
        ]));
    }

    /**
     * SKIP LOCKED 構文
     *
     * @dataProvider provideDatabase
     * @param Database $db1
     */
    function test_lockForUpdate_SkipLocked($db1)
    {
        if ($db1->getPlatform() instanceof MySQLPlatform || $db1->getPlatform() instanceof PostgreSQLPlatform) {
            // 排他ロックで取得しておく
            $db1->begin();
            $db1->select('test1', ['id' => [1, 2]])->lockForUpdate()->array();

            // 別接続で SKIP LOCKED 取得してみる
            $db2 = new Database(DriverManager::getConnection($db1->getConnection()->getParams()));
            $rows = $db2->select('test1', ['id' => [1, 2, 3]])->lockForUpdate('SKIP LOCKED')->array();
            $db1->rollback();

            // 1,2 はロックされているので 1 件しか取得できないはず
            $this->assertCount(1, $rows);
        }
    }

    /**
     * ゲートウェイjoin
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_gateway_join($database)
    {
        $select = $database->t_article()->as('A')
            ->joinOn($database->t_comment()->as('C')
                ->joinOn($database->t_article()->as('tA')->scoping('*', 'delete_flg = 0', [], [], 'article_id'), ['tA.article_id = C.article_id']),
                ['C.article_id = A.article_id']
            )
            ->select();
        $this->assertEquals('SELECT * FROM t_article A INNER JOIN t_comment C ON C.article_id = A.article_id INNER JOIN (SELECT tA.* FROM t_article tA WHERE delete_flg = 0 GROUP BY article_id) tA ON tA.article_id = C.article_id', "$select");

        /** @var Article $row */
        $row = $database->Article()->innerJoinForeign($database->t_comment()->column('*')->orderBy('comment_id'))->limit(1)->tuple();
        $this->assertInstanceOf(Article::class, $row);
        $this->assertEquals('コメント1です', $row->comment);

        $row = $database->Article()->limit(1)->tuple([
            'comments' => $database->Comment()->column('*'),
        ]);
        $this->assertInstanceOf(Article::class, $row);
        $this->assertInstanceOf(Comment::class, $row->comments[3]);
        $this->assertEquals('コメント3です', $row->comments[3]->comment);
    }

    /**
     * ゲートウェイiterate
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_gateway_iterate($database)
    {
        // t_article は Database のデフォルトが効かない(クラスで指定されている)
        unset($database->t_article);
        $database->setDefaultIteration('array');
        $this->assertEquals([
            1 => [
                'article_id' => '1',
                'title'      => 'タイトルです',
                'checks'     => '',
                'delete_at'  => null,
            ],
        ], iterator_to_array($database->t_article()->pk(1)));

        // t_comment は Database のデフォルトが効く(クラスで指定されていない)
        unset($database->t_comment);
        $database->setDefaultIteration('assoc');
        $this->assertEquals([
            1 => [
                'article_id' => '1',
                'comment_id' => '1',
                'comment'    => 'コメント1です',
            ],
        ], json_decode(json_encode(iterator_to_array($database->t_comment()->pk(1))), true));
        $database->setDefaultIteration('array');
    }

    /**
     * ゲートウェイ仮想カラム
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_gateway_virtual($database)
    {
        $database->setAutoCastType([
            'simple_array' => true,
        ]);

        $pk = $database->t_article->insertOrThrow([
            'article_id' => 9,
            'vaffect'    => 'dummy title:1:2:3',
        ]);

        $row = $database->t_article->as('A')->select([
            'title',
            'titlex'              => 'title2',
            'title2',
            'title3',
            'title4',
            'comment_count',
            'checks|simple_array' => 'checks',
        ], $pk)->tuple();

        $this->assertEquals([
            'title'         => 'dummy title',
            'titlex'        => 'DUMMY TITLE',
            'title2'        => 'DUMMY TITLE',
            'comment_count' => '0',
            'checks'        => [1, 2, 3],
            'title3'        => 'a dummy title z',
            'title4'        => function () { /* dummy */ },
        ], $row);
        $this->assertEquals('[prefix] dummy title', $row['title4']('[prefix] '));

        $this->assertEquals([
            'article_id' => '9',
            'title'      => 'dummy title',
            'statement'  => 'DUMMY TITLE',
            'title5'     => 'DUMMY TITLE',
            'delete_at'  => null,
        ], $database->t_article->as('A')->select('!checks', $pk)->tuple());

        $database->setAutoCastType([]);
    }

    /**
     * 子供関係
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_children($database)
    {
        $database->insert('t_comment', ['article_id' => 1, 'comment_id' => 4, 'comment' => 'コメント4です']);
        $database->insert('t_comment', ['article_id' => 1, 'comment_id' => 5, 'comment' => 'コメント5です']);
        $database->insert('t_comment', ['article_id' => 1, 'comment_id' => 6, 'comment' => 'コメント6です']);
        $database->insert('t_article', ['article_id' => 3, 'title' => 'new', 'checks' => '']);

        $rows = $database->selectAssoc([
            't_article A#0' => [
                't_comment C-comment_id#1-4' => [
                    'comment',
                    ['C.comment_id <> ?' => 1],
                ],
                ['A.article_id <> ?' => 2],
            ],
        ]);
        $this->assertEquals([
            1 => [
                'article_id' => '1',
                'title'      => 'タイトルです',
                'checks'     => '',
                'delete_at'  => null,
                'C'          => [
                    // ORDER BY C.commnet_id DESC が効くので降順になる
                    // LIMIT 3 が効くので 6 の t_comment は含まれない
                    5 => [
                        'comment' => 'コメント5です',
                    ],
                    4 => [
                        'comment' => 'コメント4です',
                    ],
                    3 => [
                        'comment' => 'コメント3です',
                    ],
                    // OFFSET 1 が効くので 2 の t_comment は含まれない
                    // C.comment_id <> 1 が効くので 1 の t_comment は含まれない
                ],
            ],
            // A.article_id <> 2 が効くので 2 の t_article は含まれない
            // LIMIT 2 が効くので 3 の t_article は含まれない
        ], $rows);
    }

    /**
     * DB context 関係
     *
     * DB を渡す処理系が仮に循環参照などでインスタンスを握り続けるとコンテキストは戻らない。
     * 多少の設定ならともかく dryrun,prepare 等だとあらゆる処理がコケ始めるので担保しておく。
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_context($database)
    {
        $db = $database->context(['defaultChunk' => 10]);
        $t = $db->transaction(fn(Database $db) => $db->fetchValue('select 1'));
        $this->assertEquals('1', $t->perform());
        unset($t);
        unset($db);
        $this->assertNull($database->getDefaultChunk());

        $database->test(); // 一度は作っておく
        $db = $database->context(['defaultChunk' => 10]);
        $t = $database->test;
        $t->array();
        unset($t);
        unset($db);
        $this->assertNull($database->getDefaultChunk());

        $db = $database->context(['defaultChunk' => 10]);
        $where = Where::and([fn() => 0, Where::or([1, 2, 3]), 'name' => ['a', 'b', 'c']])($db);
        $this->assertEquals('(0) AND ((1) OR (2) OR (3)) AND (name IN (?,?,?))', $where->getQuery());
        //unset($where); // $where は $db を握らないはず
        unset($db);
        $this->assertNull($database->getDefaultChunk());

        $db = $database->context(['defaultChunk' => 10]);
        $selecter = SelectBuilder::new($db);
        $selecter->column('test')->where(['id > ?' => 5])->orderBy('-id')->groupBy('name')->limit(10);
        $affecter = AffectBuilder::new($db);
        $affecter->reduce('test', 10, '-id', 'name', ['id > ?' => 5]);
        unset($selecter);
        unset($affecter);
        unset($db);
        $this->assertNull($database->getDefaultChunk());
    }

    /**
     * native type 関係
     *
     * native type が簡単に得られないのと直の値の確認が困難なので表示用に近い。
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_native_type($database)
    {
        $alltype = new Table('t_alltype',
            [
                new Column('id', Type::getType(Types::BIGINT), ['autoincrement' => true]),

                new Column('c_boolean', Type::getType(Types::BOOLEAN), []),

                new Column('c_smallint', Type::getType(Types::SMALLINT), []),
                new Column('c_integer', Type::getType(Types::INTEGER), []),

                new Column('c_float', Type::getType(Types::FLOAT), []),
                new Column('c_decimal', Type::getType(Types::DECIMAL), ['scale' => 2, 'precision' => 3]),

                new Column('c_dateinterval', Type::getType(Types::DATEINTERVAL), []),
                new Column('c_date', Type::getType(Types::DATE_MUTABLE), []),
                new Column('c_time', Type::getType(Types::TIME_MUTABLE), []),
                new Column('c_datetime', Type::getType(Types::DATETIME_MUTABLE), []),
                new Column('c_datetimetz', Type::getType(Types::DATETIMETZ_MUTABLE), []),

                new Column('c_string', Type::getType(Types::STRING), ['length' => 255]),
                new Column('c_binary', Type::getType(Types::BINARY), ['length' => 255]),
                new Column('c_text', Type::getType(Types::TEXT), []),
                new Column('c_blob', Type::getType(Types::BLOB), []),
            ],
            [new Index('PRIMARY', ['id'], true, true)]
        );
        $smanager = $database->getConnection()->createSchemaManager();
        try_return([$smanager, 'dropTable'], 't_alltype');

        $smanager->createTable($alltype);
        $database->getSchema()->refresh();

        $result = $database->executeSelect('select * from t_alltype');
        $metadata = $database->getCompatibleConnection()->getMetadata($result);
        $result->fetchAllAssociative();
        $nativeTypes = array_column($metadata, 'nativeType', 'aliasColumnName');
        $doctrineTypes = array_column($metadata, 'doctrineType', 'aliasColumnName');

        $this->assertIsArray($nativeTypes);
        $this->assertIsArray($doctrineTypes);

        //\ryunosuke\dbml\var_pretty($nativeTypes);
        //\ryunosuke\dbml\var_pretty($doctrineTypes);
        // sqlite3 はほとんど情報が得られないので飛ばす
        if ($database->getCompatibleConnection()->getName() !== 'sqlite3') {
            $this->assertEquals('integer', $doctrineTypes['id']);
            $this->assertEquals('boolean', $doctrineTypes['c_boolean']);
            $this->assertEquals('integer', $doctrineTypes['c_smallint']);
            $this->assertEquals('integer', $doctrineTypes['c_integer']);
            $this->assertEquals('float', $doctrineTypes['c_float']);
            $this->assertEquals('decimal', $doctrineTypes['c_decimal']);
            $this->assertEquals('string', $doctrineTypes['c_dateinterval']);
            $this->assertEquals('date', $doctrineTypes['c_date']);
            $this->assertEquals('time', $doctrineTypes['c_time']);
            $this->assertEquals('datetime', $doctrineTypes['c_datetime']);
            $this->assertStringStartsWith('datetime', $doctrineTypes['c_datetimetz']);
            $this->assertEquals('string', $doctrineTypes['c_string']);
            $this->assertThat($doctrineTypes['c_binary'], $this->logicalOr(
                $this->equalTo('blob'),
                $this->equalTo('string'),
                $this->equalTo('binary'),
            ));
            $this->assertThat($doctrineTypes['c_text'], $this->logicalOr(
                $this->equalTo('text'),
                $this->equalTo('blob'),
                $this->equalTo('binary'),
            ));
            $this->assertThat($doctrineTypes['c_blob'], $this->logicalOr(
                $this->equalTo('blob'),
                $this->equalTo('binary'),
            ));
        }

        $smanager->dropTable($alltype->getName());
        $database->getSchema()->refresh();
    }

    /**
     * 非同期関係
     *
     * @dataProvider provideDatabase
     * @param Database $database
     */
    function test_async($database)
    {
        if ($database->getCompatibleConnection()->getName() !== 'mysqli') {
            return;
        }

        $database->setAutoCastType([
            'simple_array' => true,
            'json'         => true,
        ]);

        $database->getSchema()->setTableColumn('misctype', 'carray', ['type' => Types::SIMPLE_ARRAY]);
        $database->getSchema()->setTableColumn('misctype', 'cjson', ['type' => Types::JSON]);

        $database->insert('misctype', [
            'cint'      => 1,
            'cfloat'    => 1.1,
            'cdecimal'  => 1.2,
            'cdate'     => '2012-12-12',
            'cdatetime' => '2012-12-12 12:34:56',
            'cstring'   => 'ho,ge',
            'ctext'     => 'fuga',
            'carray'    => [1, 2, 3],
            'cjson'     => ['a' => 'A'],
            'eint'      => 1,
            'estring'   => 'hoge',
        ]);

        $syncrows = $database->selectArray('misctype.carray,cjson');
        $asyncrows = $database->executeSelectAsync('SELECT carray, cjson FROM misctype')();
        $this->assertSame($syncrows, $asyncrows);

        $database->executeAffect('DROP TABLE IF EXISTS t_alltype');
        $database->executeAffect(<<<SQL
            CREATE TABLE t_alltype (
                id            BIGINT(20) UNSIGNED NOT NULL,
                as_bit        BIT(1) NULL DEFAULT NULL,
                as_tinyint    TINYINT(3) NULL DEFAULT NULL,
                as_shortint   SMALLINT(5) NULL DEFAULT NULL,
                as_mediumint  MEDIUMINT(7) NULL DEFAULT NULL,
                as_int        INT(10) NULL DEFAULT NULL,
                as_bigint     BIGINT(19) NULL DEFAULT NULL,
                as_float      FLOAT NULL DEFAULT NULL,
                as_double     DOUBLE NULL DEFAULT NULL,
                as_decimal    DECIMAL(20,6) NULL DEFAULT NULL,
                as_char       CHAR(50) NULL DEFAULT NULL,
                as_varchar    VARCHAR(50) NULL DEFAULT NULL,
                as_tinytext   TINYTEXT NULL DEFAULT NULL,
                as_text       TEXT NULL DEFAULT NULL,
                as_mediumtext MEDIUMTEXT NULL DEFAULT NULL,
                as_longtext   LONGTEXT NULL DEFAULT NULL,
                as_binary     BINARY(50) NULL DEFAULT NULL,
                as_varbinary  VARBINARY(50) NULL DEFAULT NULL,
                as_tinyblob   TINYBLOB NULL DEFAULT NULL,
                as_blob       BLOB NULL DEFAULT NULL,
                as_mediumblob MEDIUMBLOB NULL DEFAULT NULL,
                as_longblob   LONGBLOB NULL DEFAULT NULL,
                as_json       JSON NULL DEFAULT NULL,
                as_date       DATE NULL DEFAULT NULL,
                as_time       TIME NULL DEFAULT NULL,
                as_datetime   DATETIME NULL DEFAULT NULL,
                as_timestamp  TIMESTAMP NULL DEFAULT NULL,
                as_point      POINT NULL DEFAULT NULL,
                as_enum       ENUM('Y','N') NULL DEFAULT NULL,
                as_set        SET('A','B') NULL DEFAULT NULL,
                PRIMARY KEY (id) USING BTREE
            )
        SQL,);
        $database->executeAffect(<<<SQL
            INSERT INTO t_alltype SET
                id            = 1,
                as_bit        = 0,
                as_tinyint    = 1,
                as_shortint   = 2,
                as_mediumint  = 3,
                as_int        = 4,
                as_bigint     = 5,
                as_float      = 1.1,
                as_double     = 2.2,
                as_decimal    = 3.3,
                as_char       = "s1",
                as_varchar    = "s2",
                as_tinytext   = "s3",
                as_text       = "s4",
                as_mediumtext = "s5",
                as_longtext   = "s6",
                as_binary     = "b1",
                as_varbinary  = "b2",
                as_tinyblob   = "b3",
                as_blob       = "b4",
                as_mediumblob = "b5",
                as_longblob   = "b6",
                as_json       = "[1,2,3]",
                as_date       = "2014-12-24",
                as_time       = "12:34:56",
                as_datetime   = "2014-12-24T12:34:56",
                as_timestamp  = "2014-12-24T12:34:56",
                as_point      = ST_GeomFromText("POINT(1 1)"),
                as_enum       = "Y",
                as_set        = "A,B"
        SQL,);

        // executeSelect だと型変換が効かないので明示的に変換する
        $syncrows = $database->executeSelect('SELECT * FROM t_alltype')->fetchAllAssociative();
        $syncrows = array_map(function ($row) {
            $row['as_json'] = json_decode($row['as_json'], true);
            return $row;
        }, $syncrows);
        $asyncrows = $database->executeSelectAsync('SELECT * FROM t_alltype')();
        $this->assertSame($syncrows, $asyncrows);

        $database->executeAffect('DROP TABLE IF EXISTS t_alltype');

        $database->setAutoCastType([]);
        $database->getSchema()->refresh();
    }

    /**
     * mysql の暗黙のロールバック関係
     *
     * @dataProvider provideDatabase
     * @param Database $database
     * @noinspection PhpUnusedLocalVariableInspection
     */
    function test_retry_mysql_deadlock($database)
    {
        if (!$database->getPlatform() instanceof MySQLPlatform) {
            return;
        }

        $params = ['driver' => 'mysqli', 'driverOptions' => []] + $database->getMasterConnection()->getParams();
        $other = new Database(DriverManager::getConnection($params));

        $other->begin();
        $async = null;

        $transaction = $database->transaction(function () use ($database, $other, &$async) {
            $select = 'select * from test where id=? for update';
            $affect = 'update test set name=? where id=?';
            // 1回目（$other のせいでデッドロックする）
            if ($other->getMasterConnection()->isTransactionActive()) {
                // @formatter:off
                [$dummy, $dummy] = [$database->executeSelect($select, [1]),      $other->executeSelect($select, [2])     ];
                [$dummy, $async] = [null,                                        $other->executeSelectAsync($select, [1])];
                [$dummy, $dummy] = [$database->executeAffect($affect, ['Y', 2]), null                                    ]; // deadlock!!!
                // @formatter:on
            }
            // 2回目（$other が終わっているのでデッドロックしない）
            else {
                $database->executeSelect($select, [1]);
                $database->executeAffect($affect, ['Y', 2]);
                // いまここがトランザクション内で実行されていることを担保するために例外を投げて rollback させる
                throw new \RuntimeException();
            }
        });
        $transaction->retryable(function ($retryCount, $ex) use ($database, $other, &$async) {
            // 1回目の実行で飛んでくる
            if ($ex instanceof DeadlockException) {
                $async();           // 回さないとデストラクタでエラーになる
                $other->rollback(); // トランザクションを完了させる
                return 0.1;
            }
        });
        $transaction->perform(false);

        // ロールバックされている（トランザクションのしがらみを切るため $other の方で取得する）
        // 例えば「1回目」のリトライで暗黙のロールバックが走り、「2回目」の処理がトランザクション内で行われていなかったらロールバックできないはず
        $this->assertEquals('b', $other->selectValue('test.name', ['id' => 2]));
    }
}
