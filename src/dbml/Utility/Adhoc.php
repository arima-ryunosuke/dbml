<?php

namespace ryunosuke\dbml\Utility;

use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\QueryBuilder;
use function ryunosuke\dbml\is_stringable;
use function ryunosuke\dbml\preg_capture;

/**
 * 比較的固有な処理を記述する Utility クラス
 */
class Adhoc
{
    /**
     * 値が「空」なら true を返す
     *
     * @param mixed $value
     * @return bool
     */
    public static function is_empty($value)
    {
        if ($value === null) {
            return true;
        }
        if ($value === []) {
            return true;
        }
        if ($value === '') {
            return true;
        }
        if ($value instanceof QueryBuilder && $value->isEmptyCondition()) {
            return true;
        }

        return false;
    }

    /**
     * 指定配列を個数に応じて再帰的に小括弧()で包む
     *
     * @param array $array
     * @return array
     */
    public static function wrapParentheses($array)
    {
        $count = count($array);
        $result = [];
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $result[$k] = Adhoc::wrapParentheses($v);
            }
            else {
                if ($count === 1) {
                    $result[$k] = $v;
                }
                else {
                    $result[$k] = "($v)";
                }
            }
        }
        return $result;
    }

    /**
     * 配列が Queryable を含むなら true を返す
     *
     * @param mixed $array 対象配列
     * @return bool Queryable を含むなら true
     */
    public static function containQueryable($array)
    {
        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $v) {
            if ($v instanceof Queryable) {
                return true;
            }
        }
        return false;
    }

    /**
     * テーブル修飾子を付与する
     *
     * @param string $tablename テーブル名
     * @param array $tablecolumns テーブルカラム
     * @param array $array 修飾する配列
     * @return array 修飾された配列
     */
    public static function modifier($tablename, $tablecolumns, $array)
    {
        if (!strlen($tablename)) {
            return $array;
        }

        $result = [];
        foreach ($array as $key => $val) {
            // QueryBuilder で submethod ならプレフィックスを付けない
            if ($val instanceof QueryBuilder && ($val->getSubmethod() !== null && $val->getSubmethod() !== 'query')) {
                $result[$key] = $val;
                continue;
            }
            // 同上。配列の中に Queryable が紛れている場合
            if (Adhoc::containQueryable($val)) {
                $result[$key] = $val;
                continue;
            }
            // ( を含む場合は大抵の場合不要なのでプレフィックスを付けない
            if (is_string($key) && strpos($key, '(') !== false) {
                $result[$key] = $val;
                continue;
            }
            if (is_string($key) && isset($key[0]) && strpos($key, '.') === false) {
                $colname = preg_capture('#^[!\-+]?([_a-z][_0-9a-z]*)#i', $key, [1 => ''])[1];
                if (in_array($key[0] ?? '', ['!', '+', '-'])) {
                    if (isset($tablecolumns[$colname])) {
                        $key = substr($key, 0, 1) . $tablename . '.' . substr($key, 1);
                    }
                }
                else {
                    if (isset($tablecolumns[$colname])) {
                        $key = $tablename . '.' . $key;
                    }
                }
            }
            if (is_array($val)) {
                $val = self::modifier($tablename, $tablecolumns, $val);
            }
            $result[$key] = $val;
        }
        return $result;
    }

    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
    public static function stringifyParameter($param, callable $quoter): string
    {
        if ($param instanceof \BackedEnum) {
            $param = $param->value;
        }
        if (is_object($param) && is_callable($param) && !is_stringable($param)) {
            $param = $param();
        }
        if (is_bool($param)) {
            return (int) $param;
        }
        if ($param === null) {
            return 'NULL';
        }
        return $quoter($param);
    }

    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
    public static function bindableParameters(iterable $params): array
    {
        $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;
        foreach ($params as $k => $param) {
            if ($param instanceof \BackedEnum) {
                $param = $param->value;
            }
            if (is_object($param) && is_callable($param) && !is_stringable($param)) {
                $param = $param();
            }
            if (is_bool($param)) {
                $param = (int) $param;
            }
            $params[$k] = $param;
        }
        return $params;
    }
}
