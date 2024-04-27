dbml (Database Manipulation layer)
====

## Description

Doctrine/dbal を使用して CRUD 操作に特化したライブラリです。

対応（テスト済み）RDBMS

- sqlite 3.8
- MySQL 8.0
- PostgreSQL 15
- SQLServer 2017

ドキュメントは下記においてあります。

- [クラスリファレンス](https://arima-ryunosuke.github.io/document/dbml/)

## Install

```json
{
    "require": {
        "ryunosuke/dbml": "dev-master"
    }
}
```

## Usage

```php
$db = new \ryunosuke\dbml\Database([
    'driver'   => 'pdo_mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'dbname'   => 'dbname',
    'user'     => 'user',
    'password' => 'password',
    'charset'  => 'utf8',
], [
    /* オプション配列 */
]);
```

要するに設定情報（\Doctrine\DBAL\DriverManager に渡す配列）をコンストラクタ引数に渡します。
原則としてこの Database インスタンスのみ使用し、他のクラスは直接的には使用しません。
上記のように $db インスタンスを作成してあとはこの $db を使用してデータベースアクセスします。

往々にしてクエリビルダを使用するかゲートウェイを使用することが多いです。
基本的な CRUD のコードを以下に記します。

```php
// t_article のレコードを全件取得
$db->selectArray('t_article'); // クエリビルダ版
$db->t_article->array();       // ゲートウェイ版

// t_article にレコードを追加する
$db->insert('t_article', [
    'article_title' => 'title',
    'content'       => 'content',
]);
$db->t_article->insert([
    'article_title' => 'title',
    'content'       => 'content',
]);

// t_article のレコードを更新する
$db->update('t_article', [
    'article_title' => 'title2',
    'content'       => 'content2',
], [
    'article_id' => 1,
]);
$db->t_article->update([
    'article_title' => 'title2',
    'content'       => 'content2',
], [
    'article_id' => 1,
]);

// t_article のレコードを削除する
$db->delete('t_article', [
    'article_id' => 1,
]);
$db->t_article->delete([
    'article_id' => 1,
]);
```

実際は JOIN ができたり下位テーブルを階層で取得できたり、 WHERE に条件が埋め込めたりしますが、そのような細かい使い方は[クラスリファレンス](https://arima-ryunosuke.github.io/document/dbml/)を参照。

## License

MIT
