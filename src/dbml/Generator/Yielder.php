<?php

namespace ryunosuke\dbml\Generator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Metadata\CompatibleConnection;
use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;

/**
 * 少しずつ fetch する Generator のようなクラス
 */
class Yielder implements \IteratorAggregate
{
    use DebugInfoTrait;

    private Result|\Closure $statement;

    #[DebugInfo(false)]
    private CompatibleConnection $cconnection;

    #[DebugInfo(false)]
    private Connection $connection;

    private ?string   $method;
    private ?\Closure $callback;
    private ?int      $chunk;

    private bool $emulationedUnique = true;

    /**
     * コンストラクタ
     *
     * @param Result|\Closure $statement 取得に使用される \Statement
     * @param CompatibleConnection $cconnection 取得に使用するコネクション
     * @param ?string $method フェッチメソッド名
     * @param ?callable $callback $chunk 行ごとに呼ばれるコールバック処理
     * @param ?int $chunk コールバック処理のチャンク数。指定するとその数だけバッファリングされるので留意
     */
    public function __construct($statement, $cconnection, ?string $method = null, ?callable $callback = null, ?int $chunk = null)
    {
        $this->statement = $statement;
        $this->cconnection = $cconnection;
        $this->connection = $cconnection->getConnection();
        $this->method = $method;
        $this->callback = $callback === null ? null : \Closure::fromCallable($callback);
        $this->chunk = $chunk;
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

    private function _cleanup()
    {
        $this->setBufferMode(true);
        if (isset($this->statement) && $this->statement instanceof Result) {
            $this->statement->free();
        }
        unset($this->statement);
    }

    /**
     * フェッチメソッドを設定する
     *
     * {@link Database::METHOD_ARRAY Database の METHOD_XXX 定数}を参照。
     */
    public function setFetchMethod(string $method): static
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
     */
    public function setBufferMode(bool $mode): static
    {
        $this->cconnection->setBufferMode($mode);

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
     */
    public function setEmulationUnique(bool $mode): static
    {
        $this->emulationedUnique = $mode;
        return $this;
    }

    public function getIterator(): \Traversable
    {
        if ($this->statement instanceof \Closure) {
            $this->statement = ($this->statement)($this->connection);
        }

        $metadata = $this->cconnection->getMetadata($this->statement);

        $position = -1;
        $indexes = [];

        $loop = function ($rows) use (&$position, &$indexes) {
            foreach ($rows as $row) {
                $position++;

                // assoc/pairs のために重複を除去しなければならない
                if ($this->emulationedUnique) {
                    switch ($this->method) {
                        case Database::METHOD_ASSOC:
                        case Database::METHOD_PAIRS:
                            foreach ($row as $v) {
                                // 既読なら更に次へ行く
                                if (isset($indexes[$v])) {
                                    continue 3;
                                }
                                // 突破したら既読マークを付ける
                                $indexes[$v] = true;
                                break;
                            }
                    }
                }

                switch ($this->method) {
                    case Database::METHOD_ARRAY:
                        yield $position => $row;
                        break;
                    case Database::METHOD_ASSOC:
                        foreach ($row as $v) {
                            yield $v => $row;
                            break;
                        }
                        break;
                    case Database::METHOD_LISTS:
                        foreach ($row as $v) {
                            yield $position => $v;
                            break;
                        }
                        break;
                    case Database::METHOD_PAIRS:
                        $key = null;
                        $flg = false;
                        foreach ($row as $v) {
                            if (!$flg) {
                                $flg = true;
                                $key = $v;
                                continue;
                            }
                            else {
                                yield $key => $v;
                                break;
                            }
                        }
                        break;
                    default:
                        throw new \UnexpectedValueException("method '{$this->method}' is undefined.");
                }
            }
        };

        $chunks = [];

        while (true) {
            $row = $this->statement->fetchAssociative();
            if ($row === false) {
                break;
            }

            // チャンクが有効なら溜めておく
            if ($this->chunk) {
                $chunks[] = $row;
                if (count($chunks) < $this->chunk) {
                    continue;
                }

                if ($this->callback) {
                    $chunks = ($this->callback)($chunks, $metadata);
                }

                yield from $loop($chunks);
                $chunks = [];
            }
            else {
                if ($this->callback) {
                    $row = ($this->callback)([$row], $metadata)[0];
                }

                yield from $loop([$row]);
            }
        }

        if ($chunks) {
            if ($this->callback) {
                $chunks = ($this->callback)($chunks, $metadata);
            }

            yield from $loop($chunks);
        }

        $this->_cleanup();
    }
}
