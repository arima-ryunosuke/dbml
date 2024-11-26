<?php

namespace ryunosuke\dbml\Query\Expression;

use ryunosuke\dbml\Mixin\FactoryTrait;
use ryunosuke\dbml\Query\Queryable;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\first_keyvalue;
use function ryunosuke\dbml\starts_with;

/**
 * 生クエリを表すクラス
 *
 * `Expression::new('NOW()')` を select に与えると "NOW()" に展開される（エスケープやサブクエリ化などの余計なことを一切行わない）。
 */
class Expression implements Queryable
{
    use FactoryTrait;

    protected ?string $expr;

    protected array $params;

    private static function _bind($expr, array &$params = [], $raw = false): string
    {
        if ($expr instanceof Queryable) {
            return $expr->merge($params);
        }
        elseif ($raw) {
            return (string) $expr;
        }
        else {
            $params[] = $expr;
            return '?';
        }
    }

    /**
     * CASE ～ END 構文
     *
     * @param null|string|Queryable $expr 対象カラム。null 指定時は CASE WHEN 構文になる
     * @param array $whens [条件 => 値]の配列
     * @param mixed $else else 句。未指定時は else 句なし
     * @return Expression CASE ～ END 構文の Expression インスタンス
     */
    public static function case($expr, array $whens, $else = null): static
    {
        $params = [];
        $bind = function ($expr, $raw = false) use (&$params) { return Expression::_bind($expr, $params, $raw); };

        $query = 'CASE ';
        $query .= $expr === null ? '' : "{$bind($expr, true)} ";
        $query .= array_reduce(array_keys($whens), function ($carry, $cond) use ($whens, $expr, $bind) {
            return "{$carry}WHEN " . ($expr === null ? $cond : $bind($cond)) . " THEN {$bind($whens[$cond])} ";
        });
        $query .= $else === null ? '' : "ELSE {$bind($else)} ";
        $query .= 'END';

        return Expression::new("($query)", $params);
    }

    /**
     * OVER 句を生成する
     *
     * @see static::window()
     */
    public static function over($partitionBy = [], $orderBy = [], $frame = null): static
    {
        $window = static::window($partitionBy, $orderBy, $frame);
        return Expression::new("OVER({$window})", $window->getParams());
    }

    /**
     * 裸の OVER 句を生成する
     *
     * エスケープなどは一切行われないので注意（OVER でリテラルを指定するシチュエーションが少ないため）。
     * ただし Queryable は受け付ける。
     *
     * $frame は使い慣れないと呪文みたいな句になるので簡易的に与えられるようにしてある。
     * 文字列を渡せばそのまま埋め込まれるので、簡易指定が不要な場合はそのまま与えればよい。
     * 下記のようにキーがモード、値が式に対応する（配列の場合は BETWEEN になる）。
     *
     * // null を指定すると UNBOUNDED になる
     * ['ROWS' => null];     // ROWS UNBOUNDED PRECEDING
     * // 0 を指定する CURRENT ROW になる
     * ['ROWS' => 0];        // ROWS CURRENT ROW
     * // 非0 を指定すると OFFSET になる（さらに配列なので BETWEEN になる）
     * ['ROWS' => [-1, +1]]; // ROWS BETWEEN 1 PRECEDING AND 1 FOLLOWING
     */
    public static function window($partitionBy = [], $orderBy = [], $frame = null): static
    {
        $params = [];
        $bind = function ($expr) use (&$params) { return Expression::_bind($expr, $params, true); };

        $partitions = '';
        $partitionBy = arrayize($partitionBy);
        if ($partitionBy) {
            $partitions = 'PARTITION BY ' . implode(', ', array_map($bind, $partitionBy));
        }

        $orders = '';
        $orderBy = arrayize($orderBy);
        if ($orderBy) {
            $orders = [];
            foreach ($orderBy as $order => $by) {
                if (is_int($order)) {
                    if (is_string($by) && starts_with($by, ['-', '+'])) {
                        $orders[] = substr($by, 1) . " " . ($by[0] === '+' ? 'ASC' : 'DESC');
                    }
                    else {
                        $orders[] = $bind($by);
                    }
                }
                else {
                    if (is_bool($by)) {
                        $by = $by ? 'ASC' : 'DESC';
                    }
                    $by = strtoupper($by);
                    $orders[] = "$order " . ($by === 'ASC' ? 'ASC' : 'DESC');
                }
            }
            $orders = 'ORDER BY ' . implode(', ', $orders);
        }

        if (is_array($frame)) {
            $convert = fn($value, $suffix) => match (true) {
                $value === null => "UNBOUNDED $suffix",
                $value === 0    => "CURRENT ROW",
                default         => "{$bind($value)} $suffix",
            };

            [$mode, $between] = first_keyvalue($frame);
            $mode = strtoupper($mode);
            $between = arrayize($between);
            if (count($between) > 1) {
                $frames = "$mode BETWEEN {$convert($between[0], 'PRECEDING')} AND {$convert($between[1], 'FOLLOWING')}";
            }
            else {
                $frames = "$mode {$convert($between[0], 'PRECEDING')}";
            }
        }
        else {
            $frames = $bind($frame);
        }

        return Expression::new(implode(' ', array_filter([$partitions, $orders, $frames], 'strlen')), $params);
    }

    /**
     * 値を Expression 化して返す
     *
     * 変換できなそうならそのまま返す。
     *
     * ```php
     * # 素の文字列はそのまま文字列のまま返す
     * $expr = Expression::forge('hoge'); // string: hoge
     *
     * # "NULL" という文字列は expression を返す
     * $expr = Expression::forge("NULL"); // Expression: "NULL"
     *
     * # (が含まれているなら Expression を返す
     * $expr = Expression::forge("NOW()"); // Expression: "NOW()"
     *
     * # 数値型なら Expression を返す
     * $expr = Expression::forge(123); // Expression: "123"
     * $expr = Expression::forge(1.2); // Expression: "1.2"
     *
     * # 真偽値なら数値 Expression を返す
     * $expr = Expression::forge(true); // Expression: "1"
     * $expr = Expression::forge(false); // Expression: "0"
     * ```
     */
    public static function forge(mixed $expr): mixed
    {
        // 文字列で
        if (is_string($expr)) {
            // 'NULL' は特別扱いで式とみなす
            if (strcasecmp($expr, 'null') === 0) {
                return Expression::new('NULL');
            }
            // 括弧が含まれていたら式とみなす
            if (strpos($expr, '(') !== false) {
                return Expression::new($expr);
            }
        }
        // 数値は自動修飾を無効にするために式とみなす
        if (is_numeric($expr)) {
            return Expression::new($expr);
        }
        // ↑の真偽値版（あらゆる RDBMS で実質的には数値みたいなものなので）
        if (is_bool($expr)) {
            return Expression::new((int) $expr);
        }
        return $expr;
    }

    /**
     * インスタンスを返す
     *
     * - 引数なしなら呼び出し式をそのまま返す
     * - 引数ありならパラメータ付きで返す
     *     - ただしプレースホルダーがないなら(?,...,?)を付け足す
     *
     * つまり
     *
     * - `Expression::new('NOW()');`
     * - `Expression::NOW();`
     *
     * や
     *
     * - `Expression::new('ADD(?, ?)', array(1, 2));`
     * - `Expression::{'ADD(?, ?)'}(1, 2);`
     * - `Expression::ADD(1, 2);`
     *
     * はそれぞれ等価になる。
     */
    public static function __callStatic(string $expr, array $params): static
    {
        if (strpos($expr, '(') !== false) {
            return Expression::new($expr, $params);
        }

        $inners = [];
        $newparams = [];
        foreach ($params as $param) {
            if (!$param instanceof Queryable) {
                $param = Expression::new('?', $param);
            }
            $inners[] = $param->merge($newparams);
        }
        return Expression::new($expr . "(" . implode(', ', $inners) . ")", $newparams);
    }

    /**
     * コンストラクタ
     */
    public function __construct(?string $expr, mixed $params = [])
    {
        if ($params instanceof \ArrayObject) {
            $params = iterator_to_array($params);
        }
        $this->expr = $expr;
        $this->params = arrayize($params);
    }

    /**
     * 文字列表現を返す
     */
    public function __toString(): string
    {
        return (string) $this->expr;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @inheritdoc
     */
    public function getParams(): array
    {
        $this->__toString(); // 実質的なビルド
        return $this->params;
    }

    /**
     * @inheritdoc
     */
    public function getQuery(): string
    {
        return $this->__toString();
    }

    /**
     * @inheritdoc
     */
    public function merge(?array &$params): string
    {
        $params = $params ?? [];
        foreach ($this->getParams() as $param) {
            $params[] = $param;
        }
        return $this->getQuery();
    }
}
