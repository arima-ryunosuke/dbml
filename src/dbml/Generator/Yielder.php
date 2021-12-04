<?php

namespace ryunosuke\dbml\Generator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use ryunosuke\dbml\Database;

/**
 * 少しずつ fetch する Generator のようなクラス
 */
class Yielder implements \Iterator
{
    /** @var int 整数キーのための連番 */
    private $position;

    /** @var array 重複キーを取り除くためのインデックス */
    private $indexes = [];

    /** @var array 現在行 */
    private $current;

    /** @var Result|\Closure ステートメント */
    private $statement;

    /** @var Connection */
    private $connection;

    /** @var string イテレートメソッド（Database::METHOD_XXX） */
    private $method;

    /** @var bool FETCH_UNIQUE の動作を模倣するか */
    private $emulationedUnique = true;

    /** @var bool|null バッファモードの現在値 */
    private $bufferedMode;

    /** @var callable 行コールバック */
    private $callback;

    /**
     * コンストラクタ
     *
     * @param Result|\Closure $statement 取得に使用される \Statement
     * @param Connection $connection 取得に使用するコネクション
     * @param ?string $method フェッチメソッド名
     * @param ?callable $callback 1行ごとに呼ばれるコールバック処理
     */
    public function __construct($statement, $connection, $method = null, $callback = null)
    {
        $this->statement = $statement;
        $this->connection = $connection;
        $this->method = $method;
        $this->callback = $callback;
    }

    /**
     * デストラクタ
     *
     * 設定を戻したりカーソルを閉じたりする。
     */
    public function __destruct()
    {
        $this->_cleanup();
    }

    private function _fetch()
    {
        if ($this->statement instanceof \Closure) {
            $this->statement = ($this->statement)($this->connection);
            if (!$this->statement instanceof Result) {
                throw new \RuntimeException('stetement provider returns invalid type.');
            }
        }

        $row = $this->statement->fetchAssociative();
        if ($row === false) {
            return false;
        }

        if ($this->callback) {
            $row = ($this->callback)($row);
        }
        return $row;
    }

    private function _cleanup()
    {
        $this->setBufferMode($this->bufferedMode);
        if ($this->statement instanceof Result) {
            $this->statement->free();
        }
        $this->statement = null;
        $this->indexes = [];
    }

    /**
     * フェッチメソッドを設定する
     *
     * {@link Database::METHOD_ARRAY Database の METHOD_XXX 定数}を参照。
     *
     * @param string $method Database の METHOD_XXX を指定する
     * @return $this 自分自身
     */
    public function setFetchMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * mysql におけるバッファモード/非バッファモードを切り替える
     *
     * このメソッドを true で呼び出すと「同時にクエリを実行できない代わりに省メモリモード」で動作する。
     * 詳細は {@link http://php.net/manual/ja/mysqlinfo.concepts.buffering.php 公式マニュアル}を参照。
     *
     * ```php
     * foreach ($db->yieldAssoc($sql)->setBufferMode(false) as $key => $row) {
     *     // このループは非バッファモードで動作する（このブロック内で別のクエリを投げることは出来ない）
     *     var_dump($row);
     * }
     * ```
     *
     * 「同時にクエリを実行できない」は Database::sub 系クエリが使えないことを意味するので、本当に必要な時以外は呼ばなくていい。
     *
     * @param bool|null $mode バッファモード/非バッファモード
     * @return $this 自分自身
     */
    public function setBufferMode($mode)
    {
        if ($mode === null) {
            return $this;
        }

        // 非バッファモードは pdo_mysql しか対応していない（それすら非推奨の流れがあるが…）
        $conn = $this->connection->getWrappedConnection();
        if ($conn instanceof \Doctrine\DBAL\Driver\PDO\Connection) {
            $pdo = $conn->getWrappedConnection();
        }
        if (isset($pdo) && $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
            // @codeCoverageIgnoreStart
            $this->bufferedMode = $this->bufferedMode ?? $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $mode);
            // @codeCoverageIgnoreEnd
        }

        return $this;
    }

    /**
     * FETCH_UNIQUE の動作を模倣するか設定
     *
     * このクラスは foreach で回せるが、逐次取得なので FETCH_UNIQUE 相当の動作（キーを最初のカラムにする）ができない。
     * （ループ処理そのものなので重複処理が行えない）。
     * このメソッドを true で呼び出すとアプリレイヤーでなんとかしてその動作を模倣するようになる。
     *
     * 要するに「キーが連番になるか最初のカラム値になるか」を指定する。
     *
     * ```php
     * foreach ($db->yieldAssoc($sql)->setEmulationUnique(true) as $key => $row) {
     *     // $key が「レコードの最初のカラム値」を表すようになる
     *     var_dump($key);
     * }
     * ```
     *
     * とはいえデフォルトで true なので明示的に呼ぶ必要はほとんど無い。
     * 上記のコードを false にすると挙動が分かりやすい。
     *
     * @param bool $mode FETCH_UNIQUE の動作を模倣するなら true
     * @return $this 自分自身
     */
    public function setEmulationUnique($mode)
    {
        $this->emulationedUnique = $mode;
        return $this;
    }

    /**
     * @ignore
     */
    public function rewind()
    {
        $this->position = 0;
        $this->current = $this->_fetch();
    }

    /**
     * @ignore
     */
    public function next()
    {
        $this->position++;
        $this->current = $this->_fetch();
    }

    /**
     * @ignore
     */
    public function valid()
    {
        // 現在行が終わってるなら掃除して終了（false）
        if ($this->current === false) {
            $this->_cleanup();
            return false;
        }

        // 継続してるなら true を返せばよいが、 assoc/pairs のために重複を除去しなければならない
        if ($this->emulationedUnique) {
            switch ($this->method) {
                case Database::METHOD_ASSOC:
                case Database::METHOD_PAIRS:
                    foreach ($this->current as $v) {
                        // 既読なら更に次へ行く
                        if (isset($this->indexes[$v])) {
                            $this->next();
                            return $this->valid();
                        }
                        // 突破したら既読マークを付ける
                        $this->indexes[$v] = true;
                        break;
                    }
            }
        }

        return $this->current;
    }

    /**
     * @ignore
     */
    public function key()
    {
        switch ($this->method) {
            case Database::METHOD_ARRAY:
            case Database::METHOD_LISTS:
                return $this->position;

            case Database::METHOD_PAIRS:
            case Database::METHOD_ASSOC:
                foreach ($this->current as $v) {
                    return $v;
                }
        }

        throw new \UnexpectedValueException("method '{$this->method}' is undefined.");
    }

    /**
     * @ignore
     */
    public function current()
    {
        switch ($this->method) {
            case Database::METHOD_ARRAY:
            case Database::METHOD_ASSOC:
                return $this->current;

            /** @noinspection PhpMissingBreakStatementInspection */
            case Database::METHOD_LISTS:
                foreach ($this->current as $v) {
                    return $v;
                }
            case Database::METHOD_PAIRS:
                $flg = false;
                foreach ($this->current as $v) {
                    if ($flg) {
                        return $v;
                    }
                    $flg = true;
                }
        }

        throw new \UnexpectedValueException("method '{$this->method}' is undefined.");
    }
}
