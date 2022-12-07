<?php

namespace ryunosuke\dbml\Utility;

use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\QueryBuilder;
use function ryunosuke\dbml\array_unset;
use function ryunosuke\dbml\preg_capture;
use function ryunosuke\dbml\reflect_callable;

/**
 * 比較的固有な処理を記述する Utility クラス
 */
class Adhoc
{
    /**
     * 配列から指定番目の要素を差っ引いて callable のデフォルト引数を埋め込む
     *
     * @param array $arguments
     * @param callable $callable
     * @param array $remap
     * @return array
     */
    public static function reargument(&$arguments, $callable, $remap = [])
    {
        static $cache = [];

        // $call_name でキャッシュ。しかしクロージャはすべて「Closure::__invoke」になるのでキャッシュできない
        is_callable($callable, true, $call_name);
        if (!isset($cache[$call_name]) || $callable instanceof \Closure) {
            $refunc = reflect_callable($callable);
            $cache[$call_name] = [
                'NumberOfRequiredParameters' => $refunc->getNumberOfRequiredParameters(),
                'ParameterDefaultValues'     => [],
            ];
            foreach ($refunc->getParameters() as $n => $param) {
                if ($param->isDefaultValueAvailable()) {
                    $cache[$call_name]['ParameterDefaultValues'][$n] = $param->getDefaultValue();
                }
            }
        }

        $restargs = [];
        foreach ($remap as $n => $key) {
            $restargs[$key] = array_unset($arguments, $n);
        }
        $arguments = array_values($arguments);

        if (count($arguments) < $cache[$call_name]['NumberOfRequiredParameters']) {
            throw new \InvalidArgumentException("argument's length too short.");
        }
        $arguments += $cache[$call_name]['ParameterDefaultValues'];

        return array_combine($remap, $restargs);
    }

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
}
