<?php

namespace ryunosuke\dbml\Transaction;

use Doctrine\DBAL\Logging\SQLLogger;
use ryunosuke\dbml\Mixin\OptionTrait;
use function ryunosuke\dbml\is_stringable;
use function ryunosuke\dbml\sql_bind;
use function ryunosuke\dbml\sql_format;
use function ryunosuke\dbml\str_ellipsis;

/**
 * スタンダードな SQL ロガー
 *
 * Database の logger オプションにこのインスタンスを渡すとクエリがログられるようになる。
 *
 * ```php
 * # 標準出力にログる
 * $db = new Database($connection, [
 *     'logger' => new Logger([
 *         'destination' => STDOUT
 *     ]),
 * ]);
 * # /var/log/query.log にログる
 * $db = new Database($connection, [
 *     'logger' => new Logger([
 *         'destination' => '/var/log/query.log'
 *     ]),
 * ]);
 * # クロージャでログる
 * $db = new Database($connection, [
 *     'logger' => new Logger([
 *         'destination' => function ($log) { echo $log; }
 *     ]),
 * ]);
 * ```
 *
 * Transaction 名前空間に居るのは少し小細工をしているから（癒着している）＋「クエリログは膨大なのでログらない（RDBMS のログに任せる）がトランザクションログはアプリで取っておきたい」という要件が多いため。
 * 別にグローバルなロガーとして設定しても問題はない。
 *
 * ### buffer オプションについて
 *
 * コンストラクタオプションで buffer を渡すと下記のような動作モードになる。
 * fastcgi_finish_request など、クライアントに速度を意識させない方法があるなら基本的には array を推奨する。
 * BLOB INSERT が多いとか、軽めのクエリの数が多いとか、バッチで動いているとか、要件・状況に応じて適時変更したほうが良い。
 *
 * #### false
 *
 * 逐次書き込む。
 *
 * 逐次変換処理は行われるがメモリは一切消費しないし、ロックも伴わない。
 * ただし、逐次書き込むので**ログがリクエスト単位にならない**（別リクエストの割り込みログが発生する）。
 *
 * #### int
 *
 * 指定されたサイズでバッファリングして終了時に書き込む（超えた分は一時ファイル書き出し）。
 *
 * メモリには優しいが、逐次ログの変換処理が発生するため、場合によっては動作速度があまりよろしくない。
 * 終了時にロックして書き込むので**ログがリクエスト単位になる**（別リクエストの割り込みログが発生しない）。
 *
 * #### true
 *
 * 配列に溜め込んで終了時に書き込む。
 *
 * ログの変換処理が逐次行われず、終了時に変換と書き込みを行うので、 fastcgi_finish_request があるなら（クライアントの）動作速度に一切の影響を与えない。
 * ただし、 長大なクエリや BLOB INSERT などもすべて蓄えるのでメモリには優しくない。
 * 終了時にロックして書き込むので**ログがリクエスト単位になる**（別リクエストの割り込みログが発生しない）。
 *
 * #### array
 *
 * 指定されたサイズまでは配列に溜め込んで、それ以上はバッファリングして終了時に書き込む。
 *
 * 上記の int と true の合わせ技（2要素の配列で指定する）。
 * http のときは全部配列に収まるように、 batch のときは溢れてもいいようなサイズを設定すれば共通の設定を使い回せる。
 * 終了時にロックして書き込むので**ログがリクエスト単位になる**（別リクエストの割り込みログが発生しない）。
 */
class Logger implements SQLLogger
{
    use OptionTrait;

    /** @var resource|\Closure ログハンドル */
    private $handle;

    /** @var int array 用の現在のバッファサイズ */
    private $bufferSize;

    /** @var int array 用の最大のバッファサイズ */
    private $bufferLimit;

    /** @var resource 一時リソースログバッファ */
    private $resourceBuffer;

    /** @var array 一時配列ログバッファ */
    private $arrayBuffer;

    public static function getDefaultOptions()
    {
        return [
            // 出力場所（string/resource/Closure/null）。null はログらない
            'destination' => null,
            // $sql, $params, $types の文字列化コールバック
            'callback'    => self::simple(),
            // バッファリングモード（true だと 配列に溜め込む。数値だとバッファサイズになる。destination が Closure の場合は無効）
            'buffer'      => [1024 * 1024 * 8],
            // flock するか否か
            'lockmode'    => true,
        ];
    }

    /**
     * シンプルに値の埋め込みだけを行うコールバックを返す
     *
     * @param int|null $trimsize bind パラメータの切り詰めサイズ
     * @return \Closure 文字列化コールバック
     */
    public static function simple($trimsize = null)
    {
        return function ($sql, $params, $types) use ($trimsize) {
            foreach ($params as $k => $param) {
                if (is_string($param) || (is_object($param) && is_stringable($param))) {
                    $param = (string) $param;
                    if (strpos($param, '\0') !== false) {
                        $param = bin2hex($param);
                        if ($trimsize !== null) {
                            $param = str_ellipsis($param, $trimsize);
                        }
                        $param = 'binary(' . strtoupper($param) . ')';
                    }
                    elseif ($trimsize !== null) {
                        $param = str_ellipsis($param, $trimsize);
                    }
                    $params[$k] = $param;
                }
            }
            return sql_bind(trim($sql), $params);
        };
    }

    /**
     * 値を埋め込んだ上で sql フォーマットするコールバックを返す
     *
     * @param int|null $trimsize bind パラメータの切り詰めサイズ
     * @return \Closure 文字列化コールバック
     */
    public static function pretty($trimsize = null)
    {
        $simple = self::simple($trimsize);
        return function ($sql, $params, $types) use ($simple) {
            return sql_format($simple($sql, $params, $types));
        };
    }

    /**
     * 連続する空白をまとめて1行化するコールバックを返す
     *
     * @param int|null $trimsize bind パラメータの切り詰めサイズ
     * @return \Closure 文字列化コールバック
     */
    public static function oneline($trimsize = null)
    {
        $simple = self::simple($trimsize);
        return function ($sql, $params, $types) use ($simple) {
            // ログ目的なので token_get_all で雑にやる（"" も '' も `` も php 的にはクオーティングなのでリテラルが保護される）
            // SqlServer はなんか特殊なクオートがあった気がするが考慮しない
            $tokens = token_get_all("<?php " . $sql);
            unset($tokens[0]);

            $stripsql = '';
            foreach ($tokens as $token) {
                if (is_string($token)) {
                    $stripsql .= $token;
                    continue;
                }
                if ($token[0] === T_WHITESPACE) {
                    $token[1] = ' ';
                }
                $stripsql .= $token[1];
            }
            return $simple($stripsql, $params, $types);
        };
    }

    /**
     * コンストラクタ
     *
     * @param mixed $destination 出力場所だけはほぼ必須かつ単一で与えることも多いため別引数で与え**られる**
     * @param array $options オプション
     */
    public function __construct($destination = null, $options = [])
    {
        if (is_array($destination)) {
            $options = $destination;
        }
        else {
            $options['destination'] = $destination;
        }
        $this->setDefault($options);

        $destination = $this->getUnsafeOption('destination');
        $buffer = $this->getUnsafeOption('buffer');
        if ($destination === null) {
            $destination = function () { };
        }
        if ($destination instanceof \Closure) {
            $buffer = false;
        }

        $this->handle = is_string($destination) ? fopen($destination, 'ab') : $destination;

        $this->bufferSize = 0;
        $this->bufferLimit = 0;
        if (is_array($buffer)) {
            $this->bufferLimit = $buffer[0];
            $this->resourceBuffer = fopen("php://temp/maxmemory:" . ($buffer[1] ?? $buffer[0]), 'r+b');
            $this->arrayBuffer = [];
        }
        elseif (is_int($buffer)) {
            $this->resourceBuffer = fopen("php://temp/maxmemory:{$buffer}", 'r+b');
        }
        elseif ($buffer) {
            $this->arrayBuffer = [];
        }
    }

    public function __destruct()
    {
        $this->OptionTrait__destruct();

        if ($this->resourceBuffer === null && $this->arrayBuffer === null) {
            return;
        }

        $locking = $this->getUnsafeOption('lockmode');

        if ($locking) {
            flock($this->handle, LOCK_EX);
        }

        if (is_resource($this->resourceBuffer)) {
            rewind($this->resourceBuffer);
            stream_copy_to_stream($this->resourceBuffer, $this->handle);
            fclose($this->resourceBuffer);
        }
        if (is_array($this->arrayBuffer)) {
            foreach ($this->arrayBuffer as $log) {
                fwrite($this->handle, $this->_stringify(...$log) . "\n");
            }
        }

        if ($locking) {
            flock($this->handle, LOCK_UN);
        }
    }

    private function _stringify($sql, $params, $types)
    {
        $sql = trim($sql, '"'); // DBAL\Connection がクォートするので外す
        $sql = $this->getUnsafeOption('callback')($sql, $params, $types);
        return $sql;
    }

    public function log($sql, array $params = [], array $types = [])
    {
        // arrayBuffer を優先するため下記の順番を変えてはならない
        if (is_array($this->arrayBuffer)) {
            $this->arrayBuffer[] = [$sql, $params, $types];
        }
        elseif (is_resource($this->resourceBuffer)) {
            fwrite($this->resourceBuffer, $this->_stringify($sql, $params, $types) . "\n");
        }
        elseif (is_resource($this->handle)) {
            fwrite($this->handle, $this->_stringify($sql, $params, $types) . "\n");
        }
        else {
            ($this->handle)($this->_stringify($sql, $params, $types));
        }

        if ($this->bufferLimit) {
            $this->bufferSize += strlen($sql) + array_sum(array_map('strlen', $params));
            if ($this->bufferSize > $this->bufferLimit) {
                foreach ($this->arrayBuffer as $log) {
                    fwrite($this->resourceBuffer, $this->_stringify(...$log) . "\n");
                }
                $this->arrayBuffer = [];
                $this->bufferSize = 0;
            }
        }
    }

    public function startQuery($sql, array $params = null, array $types = null) { $this->log($sql, $params ?? [], $types ?? []); }

    public function stopQuery() { }
}
