<?php

namespace ryunosuke\dbml\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use ReflectionClass;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\dbml\Query\Parser;
use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\date_convert;
use function ryunosuke\dbml\is_bindable_closure;
use function ryunosuke\dbml\is_stringable;
use function ryunosuke\dbml\parameter_length;
use function ryunosuke\dbml\sql_format;
use function ryunosuke\dbml\sql_quote;
use function ryunosuke\dbml\starts_with;
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
class Logger extends AbstractLogger
{
    use DebugInfoTrait;
    use OptionTrait;

    private int $transacting;

    /** @var resource|\Closure */
    private $handle;

    private int $bufferSize;
    private int $bufferLimit;

    /** @var resource */
    private $resourceBuffer;

    #[DebugInfo(false)]
    private ?array $arrayBuffer = null;

    public static function getDefaultOptions(): array
    {
        return [
            /** @var string ログレベル */
            'level'       => LogLevel::INFO,
            /** @var bool トランザクションだけをログるか（level >= INFO 以上である必要がある） */
            'transaction' => false,
            /** @var null|string|resource|\Closure 出力場所（string/resource/Closure/null）。null はログらない */
            'destination' => null,
            /** @var \Closure ($sql, $params, $types) の文字列化コールバック */
            'callback'    => self::simple(),
            /** @var bool|int|array バッファリングモード
             * - false: バッファ無効で逐次書き込む
             * - true: 配列にバッファして終了時に書き込む
             * - int: 指定サイズでバッファする
             * - array: 指定サイズでバッファし、超えたらフラッシュする
             */
            'buffer'      => [1024 * 1024 * 8],
            /** @var bool flock するか否か */
            'lockmode'    => true,
            /** @var \Closure[] メタデータをコメント化して出力する際の処理（下記はあくまで組み込み。任意のキーを生やせばそれがログられる）
             * static なクロージャを与えると初回呼び出しの結果が固定化され、次回以降同じ結果を返すようになる（コネクション ID などに使える）
             */
            'metadata'    => [
                'id'      => static function ($metadata) {
                    return uniqid();
                },
                'time'    => function ($metadata) {
                    if (isset($metadata['time'])) {
                        return date_convert('Y/m/d H:i:s.v', $metadata['time']);
                    }
                },
                'elapsed' => function ($metadata) {
                    if (isset($metadata['time'])) {
                        return number_format(microtime(true) - $metadata['time'], 3);
                    }
                },
                'traces'  => function ($metadata) {
                    return array_each(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), function (&$carry, $item) {
                        if (isset($item['file'], $item['line']) && strpos($item['file'], 'vendor') === false) {
                            $carry[] = $item['file'] . '#' . $item['line'];
                        }
                    }, []);
                },
            ],
        ];
    }

    /**
     * シンプルに値の埋め込みだけを行うコールバックを返す
     */
    public static function simple(?int $trimsize = null): \Closure
    {
        return function ($sql, $params, $types, $metadata) use ($trimsize) {
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

            $parser = new Parser(false, Parser::ERROR_MODE_SILENT);
            $sql = $parser->convertQuotedSQL(trim($sql), $params, fn($v) => sql_quote($v));

            if ($metadata) {
                $datalines = [];
                foreach ($metadata as $key => $data) {
                    if (is_iterable($data)) {
                        foreach ($data as $k => $d) {
                            if ($d !== null) {
                                $datalines[] = sprintf("-- %s[%s]: %s\n", $key, is_int($k) ? '' : $k, $d);
                            }
                        }
                    }
                    else {
                        if ($data !== null) {
                            $datalines[] = sprintf("-- %s: %s\n", $key, $data);
                        }
                    }
                }
                $sql = implode("", $datalines) . $sql;
            }
            return $sql;
        };
    }

    /**
     * 値を埋め込んだ上で sql フォーマットするコールバックを返す
     */
    public static function pretty(?int $trimsize = null): \Closure
    {
        $simple = self::simple($trimsize);
        return function ($sql, $params, $types, $metadata) use ($simple) {
            return sql_format($simple($sql, $params, $types, $metadata));
        };
    }

    /**
     * 連続する空白をまとめて1行化するコールバックを返す
     */
    public static function oneline(?int $trimsize = null): \Closure
    {
        $simple = self::simple($trimsize);
        return function ($sql, $params, $types, $metadata) use ($simple) {
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
            return $simple($stripsql, $params, $types, $metadata);
        };
    }

    /**
     * 1行 json (jsonl) のコールバックを返す
     */
    public static function json(bool $bind = true): \Closure
    {
        return function ($sql, $params, $types, $metadata) use ($bind) {
            if ($bind) {
                $data = [
                    'sql' => (new Parser(false, Parser::ERROR_MODE_SILENT))->convertQuotedSQL($sql, $params, fn($v) => sql_quote($v)),
                ];
            }
            else {
                $data = [
                    'sql'    => $sql,
                    'params' => $params,
                    'types'  => $types,
                ];
            }
            return json_encode($data + $metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

        $this->transacting = 0;

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

        if ($this->handle instanceof \Closure && parameter_length($this->handle) <= 1) {
            $handle = $this->handle;
            $this->handle = function () use ($handle) {
                $handle($this->_stringify(...func_get_args()));
            };
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

    private function _stringify($sql, $params, $types, $metadata, $indent): string
    {
        $callback = $this->getUnsafeOption('callback') ?? fn($v) => $v;
        $result = $callback($sql, $params, $types, $metadata);

        // バッファモードじゃないと入り乱れるのでインデントの意味がない（むしろ誤読を助長するので害悪）
        if ($indent && !($this->handle instanceof \Closure && $this->resourceBuffer === null && $this->arrayBuffer === null)) {
            $result = preg_replace('#^#usm', str_repeat('  ', $indent), $result);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = []): void
    {
        static $levels = null;
        $levels ??= array_flip(array_values((new ReflectionClass(LogLevel::class))->getConstants()));
        if ($levels[$level] > $levels[$this->getUnsafeOption('level')]) {
            return;
        }

        $currentTransacting = $this->transacting;

        if (strcasecmp($message, 'BEGIN') === 0) {
            $this->transacting = 1;
        }

        if (!$this->transacting && $this->getUnsafeOption('transaction')) {
            return;
        }

        if (starts_with($context['sql'] ?? $message, ['SAVEPOINT', 'SAVE TRANSACTION'], true)) {
            $this->transacting = 1 + $currentTransacting;
        }

        if (starts_with($context['sql'] ?? $message, ['RELEASE SAVEPOINT', 'ROLLBACK TO SAVEPOINT', 'ROLLBACK TRANSACTION'], true)) {
            $this->transacting = --$currentTransacting;
        }

        if (strcasecmp($message, 'COMMIT') === 0 || strcasecmp($message, 'ROLLBACK') === 0) {
            $this->transacting = $currentTransacting = 0;
        }

        $sql = $context['sql'] ?? $message;
        $params = array_merge($context['params'] ?? []);
        $types = $context['types'] ?? [];
        $metadata = $context['metadata'] ?? (function ($context) {
            $metadata = $this->getUnsafeOption('metadata');
            $result = [];
            foreach ($metadata as $name => $data) {
                if ($data instanceof \Closure && !is_bindable_closure($data)) {
                    $data = $data($context);
                    $metadata[$name] = $data;
                }
                elseif (is_callable($data)) {
                    $data = $data($context);
                }
                $result[$name] = $data;
            }
            $this->setUnsafeOption('metadata', $metadata);
            return $result;
        })($context);

        // arrayBuffer を優先するため下記の順番を変えてはならない
        if (is_array($this->arrayBuffer)) {
            $this->arrayBuffer[] = [$sql, $params, $types, $metadata, $currentTransacting];
        }
        elseif (is_resource($this->resourceBuffer)) {
            fwrite($this->resourceBuffer, $this->_stringify($sql, $params, $types, $metadata, $currentTransacting) . "\n");
        }
        elseif (is_resource($this->handle)) {
            fwrite($this->handle, $this->_stringify($sql, $params, $types, $metadata, $currentTransacting) . "\n");
        }
        else {
            ($this->handle)($sql, $params, $types, $metadata, $currentTransacting);
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
}
