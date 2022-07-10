<?php

namespace benchmark;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use ryunosuke\dbml\Database;
use function ryunosuke\dbml\benchmark;

require_once __DIR__ . '/bootstrap.php';

$args = \ryunosuke\dbml\arguments([
    'initdb' => null,
    'sqlite',
]);
$dbms = $args[0];
$initdb = $args['initdb'];

// phpunit.xml から接続情報を拝借
$dbms = strtoupper($dbms);
$phpunit = simplexml_load_file(__DIR__ . '/phpunit.xml');
$url = $phpunit->xpath('/phpunit/php/const[contains(@name, "' . $dbms . '_URL")][1]');
$cmd = $phpunit->xpath('/phpunit/php/const[contains(@name, "' . $dbms . '_INITCOMMAND")][1]');
($url && $cmd) or die;

// 接続とアダプタを用意
$connection = DriverManager::getConnection([
    'url' => (string) $url[0]['value'],
]);
if (strlen($cmd[0]['value'])) {
    $connection->executeStatement((string) $cmd[0]['value']);
}
$database = new Database($connection);

// initdb フラグがあるなら初期化
if ($initdb) {
    // 適当なテーブルを用意
    call_user_func(function (AbstractSchemaManager $schemar) {
        $dropAndCreateTable = function (Table $table) use ($schemar) {
            if ($schemar->tablesExist([$table->getName()])) {
                $schemar->dropTable($table->getName());
            }
            $schemar->createTable($table);
        };
        $dropAndCreateTable(new Table(
            'article',
            [
                new Column('article_id', Type::getType('integer')),
                new Column('title', Type::getType('string')),
                new Column('content', Type::getType('string')),
            ],
            [
                new Index('PRIMARY', ['article_id'], true, true),
            ]
        ));
        $dropAndCreateTable(new Table(
            'comment',
            [
                new Column('article_id', Type::getType('integer')),
                new Column('seq', Type::getType('integer')),
                new Column('comment', Type::getType('string')),
            ],
            [
                new Index('PRIMARY', ['article_id', 'seq'], true, true),
            ]
        ));
        $dropAndCreateTable(new Table(
            'heavy',
            [
                new Column('heavy_id', Type::getType('integer')),
                new Column('data', Type::getType('string')),
            ],
            [
                new Index('PRIMARY', ['heavy_id'], true, true),
            ]
        ));
    }, $connection->createSchemaManager());

    // 適当なレコードを用意
    $database->transact(function (Database $database) {
        $ARTICLE_COUNT = 300;
        $COMMENT_COUNT = 2;
        foreach (range(1, $ARTICLE_COUNT) as $article_id) {
            $database->insert('article', [
                'article_id' => $article_id,
                'title'      => "title-$article_id",
                'content'    => str_repeat("content-$article_id", 10),
            ]);
            foreach (range(1, $COMMENT_COUNT) as $seq) {
                $database->insert('comment', [
                    'article_id' => $article_id,
                    'seq'        => $seq,
                    'comment'    => str_repeat("comment-$article_id-$seq", 10),
                ]);
            }
        }
    });
}

// 削除順のツラミがあるのでアプリ的に外部キーを定義
$database->addForeignKey('comment', 'article', 'article_id');

echo "select: ";
benchmark([
    'dbal' => function () use ($connection) {
        $articles = $connection->fetchAllAssociativeIndexed('SELECT article_id as id, article.* FROM article');
        $article_ids = implode(',', array_map([$connection, 'quote'], array_keys($articles)));
        $comments = [];
        foreach ($connection->fetchAllAssociative("SELECT comment.* FROM comment WHERE article_id IN ($article_ids)") as $comment) {
            $comments[$comment['article_id']][] = $comment;
        }
        foreach ($articles as $n => $article) {
            $articles[$n]['title'] = strtoupper($article['title']);
            foreach ($comments[$article['article_id']] as $comment) {
                $comment['comment'] = strtoupper($comment['comment']);
                $articles[$n]['comments'][$comment['seq']] = $comment;
            }
        }
        return $articles;
    },
    'dbml' => function () use ($database) {
        return $database->selectAssoc([
            'article.*' => [
                'title'              => function ($title) { return strtoupper($title); },
                'comment comments.*' => [
                    'comment' => function ($comment) { return strtoupper($comment); },
                ],
            ],
        ]);
    },
]);

echo "insert: ";
benchmark([
    'dbal' => function () use ($connection) {
        $connection->executeStatement($connection->getDatabasePlatform()->getTruncateTableSQL('heavy'));
        $connection->beginTransaction();
        foreach (range(1, 10) as $i) {
            $connection->insert('heavy', [
                'heavy_id' => $i,
                'data'     => "heavy-data-$i",
            ]);
        }
        $connection->commit();
        return $connection->fetchAllAssociative('SELECT * FROM heavy');
    },
    'dbml' => function () use ($database) {
        $database->getConnection()->executeStatement($database->getPlatform()->getTruncateTableSQL('heavy'));
        $database->begin();
        foreach (range(1, 10) as $i) {
            $database->insert('heavy', [
                'heavy_id' => $i,
                'data'     => "heavy-data-$i",
            ]);
        }
        $database->commit();
        return $database->getConnection()->fetchAllAssociative('SELECT * FROM heavy');
    },
]);

echo "update: ";
benchmark([
    'dbal' => function () use ($connection) {
        $connection->beginTransaction();
        foreach (range(1, 10) as $i) {
            $connection->update('heavy', [
                'data' => "heavy-data-$i-x",
            ], [
                'heavy_id' => $i,
            ]);
        }
        $connection->commit();
        return $connection->fetchAllAssociative('SELECT * FROM heavy');
    },
    'dbml' => function () use ($database) {
        $database->begin();
        foreach (range(1, 10) as $i) {
            $database->update('heavy', [
                'data' => "heavy-data-$i-x",
            ], [
                'heavy_id' => $i,
            ]);
        }
        $database->commit();
        return $database->getConnection()->fetchAllAssociative('SELECT * FROM heavy');
    },
]);

echo "delete: ";
benchmark([
    'dbal' => function () use ($connection) {
        $connection->beginTransaction();
        foreach (range(1, 10) as $i) {
            $connection->delete('heavy', [
                'heavy_id' => $i,
            ]);
        }
        $connection->commit();
        return $connection->fetchAllAssociative('SELECT * FROM heavy');
    },
    'dbml' => function () use ($database) {
        $database->begin();
        foreach (range(1, 10) as $i) {
            $database->delete('heavy', [
                'heavy_id' => $i,
            ]);
        }
        $database->commit();
        return $database->getConnection()->fetchAllAssociative('SELECT * FROM heavy');
    },
]);
