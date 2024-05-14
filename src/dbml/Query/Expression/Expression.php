<?php

namespace ryunosuke\dbml\Query\Expression;

use ryunosuke\dbml\Query\Queryable;
use function ryunosuke\dbml\arrayize;

/**
 * 生クエリを表すクラス
 *
 * `new Expression('NOW()')` を select に与えると "NOW()" に展開される（エスケープやサブクエリ化などの余計なことを一切行わない）。
 */
class Expression implements Queryable
{
    /** @var string 文字列表現 */
    protected $expr;

    /** @var array bind パラメータ(optional) */
    protected $params;

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
     *
     * @param string $expr 値
     * @return $this|mixed Expression インスタンス
     */
    public static function forge($expr)
    {
        // 文字列で
        if (is_string($expr)) {
            // 'NULL' は特別扱いで式とみなす
            if (strcasecmp($expr, 'null') === 0) {
                return new Expression('NULL');
            }
            // 括弧が含まれていたら式とみなす
            if (strpos($expr, '(') !== false) {
                return new Expression($expr);
            }
        }
        // 数値は自動修飾を無効にするために式とみなす
        if (is_numeric($expr)) {
            return new Expression($expr);
        }
        // ↑の真偽値版（あらゆる RDBMS で実質的には数値みたいなものなので）
        if (is_bool($expr)) {
            return new Expression((int) $expr);
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
     * - `new Expression('NOW()');`
     * - `Expression::NOW();`
     *
     * や
     *
     * - `new Expression('ADD(?, ?)', array(1, 2));`
     * - `Expression::{'ADD(?, ?)'}(1, 2);`
     * - `Expression::ADD(1, 2);`
     *
     * はそれぞれ等価になる。
     *
     * @param string $expr 文字列表現
     * @param array $params bind パラメータ
     * @return $this Expression インスタンス
     */
    public static function __callStatic($expr, $params)
    {
        if (is_string($expr) && strpos($expr, '(') !== false) {
            return new Expression($expr, $params);
        }

        $inners = [];
        $newparams = [];
        foreach ($params as $param) {
            if (!$param instanceof Queryable) {
                $param = new Expression('?', $param);
            }
            $inners[] = $param->merge($newparams);
        }
        return new Expression($expr . "(" . implode(', ', $inners) . ")", $newparams);
    }

    /**
     * コンストラクタ
     *
     * @param string $expr 文字列表現
     * @param mixed $params bind パラメータ
     */
    public function __construct($expr, $params = [])
    {
        if ($params instanceof \ArrayObject) {
            $params = iterator_to_array($params);
        }
        $this->expr = $expr;
        $this->params = arrayize($params);
    }

    /**
     * 文字列表現を返す
     *
     * @return string 文字列表現
     */
    public function __toString()
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
    public function getParams()
    {
        $this->__toString(); // 実質的なビルド
        return $this->params;
    }

    /**
     * @inheritdoc
     */
    public function getQuery()
    {
        return $this->__toString();
    }

    /**
     * @inheritdoc
     */
    public function merge(?array &$params)
    {
        $params = $params ?? [];
        foreach ($this->getParams() as $param) {
            $params[] = $param;
        }
        return $this->getQuery();
    }
}
