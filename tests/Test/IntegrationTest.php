<?php

namespace ryunosuke\Test;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use ryunosuke\Test\Entity\Article;
use ryunosuke\Test\Entity\Comment;

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
                    'cond_id_in'      => $database->operator('article_id', [1, 3]),
                    'cond_title_like' => $database->operator('title:%LIKE%', 'タイトル'),
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
        $row = $database->Article()->joinForeign($database->t_comment()->column('*')->orderBy('comment_id'))->limit(1)->tuple();
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
            'titlex' => 'title2',
            'title2',
            'title3',
            'title4',
            'comment_count',
            'checks',
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
            'checks'     => [1, 2, 3],
            'statement'  => 'DUMMY TITLE',
            'title5'     => 'DUMMY TITLE',
            'delete_at'  => null,
        ], $database->t_article->as('A')->select('!', $pk)->tuple());

        $where = (string) $database->t_article->where([
            '*' => 'aaa',
        ]);
        // vcolumn で collate を指定してるので含まれる
        $this->assertStringContainsString('t_article.title collate utf8_bin LIKE', $where);
        // vcolumn で type: simple_array にしているので含まれない
        $this->assertStringNotContainsString('t_article.checks LIKE', $where);

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

        $rows = $database->selectAssoc('t_article A/t_comment C.comment', [
            'A.article_id <> ?' => 2,
            'C'                 => [
                'C.comment_id <> ?' => 1,
            ],
        ], [
            'C' => [
                'C.comment_id' => 'DESC',
            ],
        ], [
            1,
            'C' => [
                1 => 3,
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
                as_year       YEAR NULL DEFAULT NULL,
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
                as_year       = "2014",
                as_datetime   = "2014-12-24T12:34:56",
                as_timestamp  = "2014-12-24T12:34:56",
                as_point      = ST_GeomFromText("POINT(1 1)"),
                as_enum       = "Y",
                as_set        = "A,B"
        SQL,);

        $syncrows = $database->executeSelect('SELECT * FROM t_alltype')->fetchAllAssociative();
        $asyncrows = $database->executeSelectAsync('SELECT * FROM t_alltype')();
        $this->assertSame($syncrows, $asyncrows);

        $database->executeAffect('DROP TABLE IF EXISTS t_alltype');
    }
}
