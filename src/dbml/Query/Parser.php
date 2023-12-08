<?php

namespace ryunosuke\dbml\Query;

use Doctrine\DBAL\SQL\Parser as DBALParser;

/**
 * クエリをパースして本ライブラリの使用に足る情報を得るクラス
 *
 * 実際のところは doctrine のパースを利用してるので、?, :name をどう取得してどう返すか？ を決め打ちしているだけである。
 * コンストラクタオプションでエラーレベルを指定できるが、実質的に指定することはない。
 * このオプションはロガーなどのエラーが出てはまずいような特殊な状況でしか使われない。
 * （パラメータ不足でエラーが出たのにそれをログろうとしてエラーになっていては本末転倒である）。
 */
class Parser
{
    public const ERROR_MODE_SILENT    = 0;
    public const ERROR_MODE_WARNING   = 10;
    public const ERROR_MODE_EXCEPTION = 20;

    private $parser;

    private $errorMode;

    public static function raiseMismatchParameter($key, $errorMode)
    {
        if ($errorMode === self::ERROR_MODE_SILENT) {
            return;
        }

        if (is_array($key)) {
            $message = sprintf('parameter length is long (%s).', implode(',', $key));
        }
        else {
            $message = sprintf('parameter %s does not have a bound value.', $key);
        }

        if ($errorMode === self::ERROR_MODE_WARNING) {
            return trigger_error($message, E_USER_WARNING);
        }

        if ($errorMode === self::ERROR_MODE_EXCEPTION) {
            throw new \InvalidArgumentException($message);
        }
    }

    public function __construct($original, $errorMode = self::ERROR_MODE_EXCEPTION)
    {
        assert(in_array($errorMode, [self::ERROR_MODE_SILENT, self::ERROR_MODE_WARNING, self::ERROR_MODE_EXCEPTION], true));
        $this->parser = $original instanceof DBALParser ? $original : new DBALParser($original);
        $this->errorMode = $errorMode;
    }

    /**
     * クエリをプレースホルダー単位で分割する
     *
     * 汎用的に使う（わざわざ visitor を使わずにサクッとパースしたいことがある）。
     *
     * @param string $sql 書き換えるクエリ
     * @return array プレースホルダーの箇所で分割されている配列
     */
    public function convertPartialSQL($sql)
    {
        $visitor = new class(
            $this->errorMode,
        ) implements DBALParser\Visitor {
            private int   $errorMode;
            private array $buffer = [''];

            public function __construct($errorMode)
            {
                $this->errorMode = $errorMode;
            }

            public function acceptPositionalParameter(string $sql): void
            {
                $this->buffer[] = $sql;
                $this->buffer[] = '';
            }

            public function acceptNamedParameter(string $sql): void
            {
                $this->buffer[] = $sql;
                $this->buffer[] = '';
            }

            public function acceptOther(string $sql): void
            {
                $this->buffer[array_key_last($this->buffer)] .= $sql;
            }

            public function getResult()
            {
                return [array_values(array_filter($this->buffer, fn($v) => strlen($v)))];
            }
        };
        $this->parser->parse($sql, $visitor);

        [$parts] = $visitor->getResult();
        return $parts;
    }

    /**
     * クエリを名前付きプレースホルダーに統一する
     *
     * 位置プレースホルダが足りなかったり多かったりしたら例外を投げる。
     * 元からある名前付きプレースホルダーには言及しない。もしあってもそのまま追加される。
     *
     * @param string $sql 書き換えるクエリ
     * @param iterable $params パラメータ兼変換後のレシーバ
     * @return string 書き換えられたクエリ
     */
    public function convertNamedSQL($sql, iterable &$params)
    {
        $visitor = new class(
            array_merge($params instanceof \Traversable ? iterator_to_array($params) : $params),
            $this->errorMode,
        ) implements DBALParser\Visitor {
            private int   $errorMode;
            private array $buffer = [];

            private int   $position    = 0;
            private array $knownParams = [];
            private array $oldParams   = [];
            private array $newParams   = [];

            public function __construct($params, $errorMode)
            {
                $this->knownParams = $params;
                $this->oldParams = $params;

                $this->errorMode = $errorMode;
            }

            public function acceptPositionalParameter(string $sql): void
            {
                $key = $this->position++;
                $param = '__dbml_auto_bind' . $key;

                if (!array_key_exists($key, $this->oldParams)) {
                    Parser::raiseMismatchParameter($key, $this->errorMode);
                }
                $this->newParams[$param] = $this->oldParams[$key] ?? null;
                $this->buffer[] = ":" . $param;
                unset($this->knownParams[$key]);
            }

            public function acceptNamedParameter(string $sql): void
            {
                $key = substr($sql, 1);

                if (array_key_exists($key, $this->oldParams)) {
                    $this->newParams[$key] = $this->oldParams[$key];
                }
                $this->buffer[] = $sql;
                unset($this->knownParams[$key]);
            }

            public function acceptOther(string $sql): void
            {
                $this->buffer[] = $sql;
            }

            public function getResult()
            {
                if ($rest = array_filter($this->knownParams, fn($k) => is_int($k), ARRAY_FILTER_USE_KEY)) {
                    Parser::raiseMismatchParameter(array_keys($rest), $this->errorMode);
                }
                return [implode('', $this->buffer), $this->newParams];
            }
        };
        $this->parser->parse($sql, $visitor);

        [$sql, $params] = $visitor->getResult();
        return $sql;
    }

    /**
     * クエリを位置プレースホルダーに統一する
     *
     * 名前付きプレースホルダが足りなかったり多かったりしたら例外を投げる。
     * 元からある位置プレースホルダーには言及しない。もしあってもそのまま追加される。
     *
     * 1つのプレースホルダーが複数に対応することもあるので呼び元で適宜 $paramMap を使って読み替えなければならない。
     *
     * @param string $sql 書き換えるクエリ
     * @param iterable $params パラメータ兼変換後のレシーバ
     * @param array $paramMap 変換表レシーバ
     * @param ?callable $callback 位置記号のコールバック。普通は ? で十分であり、指定するのは実質的に PostgreSql 専用
     * @return string 書き換えられたクエリ
     */
    public function convertPositionalSQL($sql, iterable &$params, &$paramMap = [], $callback = null)
    {
        $visitor = new class(
            array_merge($params instanceof \Traversable ? iterator_to_array($params) : $params),
            $callback ?? static fn($n) => '?',
            $this->errorMode,
        ) implements DBALParser\Visitor {
            private int   $errorMode;
            private array $buffer = [];

            private \Closure $callback;

            private int   $position     = 0;
            private array $knownParams  = [];
            private array $oldParams    = [];
            private array $newParams    = [];
            private array $parameterMap = [];

            public function __construct($params, $callback, $errorMode)
            {
                $this->knownParams = $params;
                $this->oldParams = $params;
                $this->callback = $callback;

                $this->errorMode = $errorMode;
            }

            public function acceptPositionalParameter(string $sql): void
            {
                $key = $this->position++;

                if (array_key_exists($key, $this->oldParams)) {
                    $this->newParams[] = $this->oldParams[$key];
                }
                $this->buffer[] = ($this->callback)(count($this->parameterMap));
                $this->parameterMap[] = $key;
                unset($this->knownParams[$key]);
            }

            public function acceptNamedParameter(string $sql): void
            {
                $key = substr($sql, 1);

                if (!array_key_exists($key, $this->oldParams)) {
                    Parser::raiseMismatchParameter($key, $this->errorMode);
                }
                $this->newParams[] = $this->oldParams[$key] ?? null;
                $this->buffer[] = ($this->callback)(count($this->parameterMap));
                $this->parameterMap[] = $key;
                unset($this->knownParams[$key]);
            }

            public function acceptOther(string $sql): void
            {
                $this->buffer[] = $sql;
            }

            public function getResult()
            {
                if ($rest = array_filter($this->knownParams, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY)) {
                    Parser::raiseMismatchParameter(array_keys($rest), $this->errorMode);
                }
                return [implode('', $this->buffer), $this->newParams, $this->parameterMap];
            }
        };
        $this->parser->parse($sql, $visitor);

        [$sql, $params, $paramMap] = $visitor->getResult();
        return $sql;
    }

    /**
     * @see convertPositionalSQL()
     */
    public function convertDollarSQL($sql, iterable &$params, &$paramMap = [])
    {
        return $this->convertPositionalSQL($sql, $params, $paramMap, static fn($n) => '$' . ($n + 1));
    }

    /**
     * クエリのプレースホルダーに値を埋め込んで完全なクエリにする
     *
     * プレースホルダの数や種類が完全に一致していないと例外を投げる。
     *
     * 値の埋め込みは本質的に危険なので避けるべきだが、一部の内部処理や代替がない場合に埋め込む必要性が稀にある。
     *
     * @param string $sql 書き換えるクエリ
     * @param iterable $params パラメータ
     * @param callable $quoter クォートコールバック
     * @return string 書き換えられたクエリ
     */
    public function convertQuotedSQL($sql, iterable $params, $quoter)
    {
        $visitor = new class(
            array_merge($params instanceof \Traversable ? iterator_to_array($params) : $params),
            $quoter,
            $this->errorMode,
        ) implements DBALParser\Visitor {
            private int   $errorMode;
            private array $buffer = [];

            private \Closure $callback;

            private int $position = 0;

            private array $knownParams = [];
            private array $parameters  = [];

            public function __construct(array $parameters, $quoter, $errorMode)
            {
                $this->knownParams = $parameters;
                $this->parameters = $parameters;
                $this->callback = static function ($value) use ($quoter) {
                    if ($value instanceof \Closure) {
                        $value = $value();
                    }
                    if ($value === null) {
                        return 'NULL';
                    }
                    if (is_bool($value)) {
                        return (int) $value;
                    }
                    return $quoter($value);
                };

                $this->errorMode = $errorMode;
            }

            public function acceptPositionalParameter(string $sql): void
            {
                $this->acceptParameter($this->position++);
            }

            public function acceptNamedParameter(string $sql): void
            {
                $this->acceptParameter(substr($sql, 1));
            }

            private function acceptParameter($key): void
            {
                $exists = array_key_exists($key, $this->parameters);
                if (!$exists) {
                    Parser::raiseMismatchParameter($key, $this->errorMode);
                }

                $this->buffer[] = $exists ? ($this->callback)($this->parameters[$key]) : "undefined($key)";
                unset($this->knownParams[$key]);
            }

            public function acceptOther(string $sql): void
            {
                $this->buffer[] = $sql;
            }

            public function getResult()
            {
                if ($this->knownParams) {
                    Parser::raiseMismatchParameter(array_keys($this->knownParams), $this->errorMode);
                }

                return [implode('', $this->buffer)];
            }
        };
        $this->parser->parse($sql, $visitor);

        [$sql] = $visitor->getResult();
        return $sql;
    }
}
