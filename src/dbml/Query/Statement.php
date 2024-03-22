<?php

namespace ryunosuke\dbml\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Utility\Adhoc;
use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;

/**
 * Statement をラップして扱いやすくしたクラス
 *
 * 主にプリペアドステートメントのために存在する。よってエミュレーションモードがオンだとほとんど意味を為さない。
 * が、 {@link Database::insert()} や {@link Database::update()} などはそれ自体にそれなりの付随処理があるので、使うことに意味がないわけではない。
 *
 * クエリビルダは疑問符プレースホルダが大量に埋め込まれる可能性があるので、全部パラメータにするのが大変。
 * ので、「prepare した時点で固定し、残り（名前付き）のみ後から指定する」という仕様になっている。
 *
 * ```php
 * $qb = $db->select('t_table.*', [':id', 'opt1' => 1, 'opt2' => 2])->prepare();
 * // :id は解決していないため、パラメータで渡すことができる（下記はエミュレーションモードがオフなら『本当の』プリペアドステートメントで実行される）
 * $qb->array(['id' => 100]); // SELECT t_table.* FROM t_table WHERE id = 100 AND opt1 = 1 AND opt2 = 2
 * $qb->array(['id' => 101]); // SELECT t_table.* FROM t_table WHERE id = 101 AND opt1 = 1 AND opt2 = 2
 * $qb->array(['id' => 102]); // SELECT t_table.* FROM t_table WHERE id = 102 AND opt1 = 1 AND opt2 = 2
 * ```
 *
 * 上記のように ":id" という形で「キー無しでかつ :から始まる要素」は利便性のため `['id = :id']` のように展開される。
 * 普通の条件式では通常の値バインドと区別する必要があるので注意（`['id > ?' => ':id']` だと `WHERE id > ? = ":id"` というただの文字列の WHERE になる）。
 */
class Statement implements Queryable
{
    use DebugInfoTrait;

    /** @var Parser */
    private $parser;

    /** @var string */
    private $query;

    /** @var array */
    private $params = [];

    /** @var bool */
    private $namedSupported;

    /** @var array */
    private $paramMap = [];

    /** @var Database */
    private $database;

    /** @var \Doctrine\DBAL\Statement[] */
    #[DebugInfo(false)]
    private $statements = [];

    public function __construct($query, iterable $params, Database $database)
    {
        // コンストラクタ時点で疑問符プレースホルダーをすべて名前付きプレースホルダーに変換しておく
        $this->parser = new Parser($database->getPlatform()->createSQLParser());
        $this->query = $this->parser->convertNamedSQL($query, $params);
        $this->params = $params;

        $this->namedSupported = $database->getCompatibleConnection()->isSupportedNamedPlaceholder();

        // コネクションを保持
        $this->database = $database;
    }

    private function _execute($method, iterable $params, Connection $connection)
    {
        // 引数パラメータを基本として初期パラメータで上書く
        $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;
        $params += $this->params;

        // 同じコネクションの stmt はキャッシュする（$this->query は不変なので問題ない）
        $key = spl_object_hash($connection);
        if (!isset($this->statements[$key])) {
            $query = $this->query;
            if (!$this->namedSupported) {
                $sampling = $params; // paramMap を得るのが目的で params の書き換えは望まない
                $query = $this->parser->convertPositionalSQL($query, $sampling, $this->paramMap);
            }
            $this->statements[$key] = $connection->prepare($query);
        }
        $stmt = $this->statements[$key];

        if (!$this->namedSupported) {
            $params = array_map(fn($key) => $params[$key], $this->paramMap);
            array_unshift($params, null);
            unset($params[0]);
        }
        $params = Adhoc::bindableParameters($params);
        $types = Adhoc::bindableTypes($params);
        foreach ($params as $k => $param) {
            $stmt->bindValue($k, $param, $types[$k]);
        }

        // 実行して返す
        return $stmt->$method();
    }

    /**
     * 取得系クエリとして実行する
     *
     * @param array $params 追加パラメータ
     * @param ?Connection $connection コネクション
     * @return Result result オブジェクト
     */
    public function executeSelect(iterable $params = [], Connection $connection = null)
    {
        return $this->_execute('executeQuery', $params, $connection ?: $this->database->getSlaveConnection());
    }

    /**
     * 更新系クエリとして実行する
     *
     * @param array $params 追加パラメータ
     * @param ?Connection $connection コネクション
     * @return int affected row
     */
    public function executeAffect(iterable $params = [], Connection $connection = null)
    {
        return $this->_execute('executeStatement', $params, $connection ?: $this->database->getMasterConnection());
    }

    /**
     * @inheritdoc
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @inheritdoc
     */
    public function merge(?array &$params)
    {
        $params = $params ?? [];
        foreach ($this->getParams() as $k => $param) {
            $params[$k] = $param;
        }
        return $this->getQuery();
    }
}
