<?php

namespace ryunosuke\dbml\Utility;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Tools\DsnParser;
use Psr\SimpleCache\CacheInterface;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\SelectBuilder;
use function ryunosuke\dbml\is_stringable;
use function ryunosuke\dbml\preg_capture;

/**
 * 比較的固有な処理を記述する Utility クラス
 */
class Adhoc
{
    /**
     * DsnParser::parse をアレンジしたもの
     *
     * 下記の点が異なる。
     *
     * - url に pdo-mysql+8.1.2 のようなバージョン付きスキームが使える
     *   - 指定されたバージョンは serverVersion に格納される
     * - url の query は params の要素として解釈される
     *   - これは doctrine と同じ（毎回迷うので併せて記載している）
     * - url の fragment は driverOptions として働く
     *   - その際、定数はキー・値ともに解決され、$params の driverOptions に追加される（上書きではない）
     * - url の指定より params 直指定の方が優先される
     *   - つまり、 url が共通設定のように作用する（url 文字列で指定できない配列や要エスケープ文字列を直に指定するイメージ）
     */
    public static function parseParams(array $params): array
    {
        $url = $params['url'] ?? null;
        if (!strlen($url ?? '')) {
            return $params;
        }

        // parse_url はホスト省略に対想定おらず false を返すので是正
        $url = strtr($url, [':///' => '://127.0.0.1/']);

        $urlParts = parse_url($url);
        $urlParams = ['driverOptions' => []];

        if (isset($urlParts['scheme']) && preg_match('#^(.+?)\+(\d+\.\d+\.\d+)$#', $urlParts['scheme'], $m)) {
            $urlParams['driver'] = str_replace('-', '_', $m[1]);
            $urlParams['serverVersion'] = $m[2];
        }

        if (isset($urlParts['fragment'])) {
            parse_str($urlParts['fragment'], $fragments);
            foreach ($fragments as $k => $v) {
                if (defined($k)) {
                    $k = constant($k);
                }
                if (defined($v)) {
                    $v = constant($v);
                }
                $urlParams['driverOptions'][$k] = $v;
            }
        }

        $params += $urlParams;
        $params['driverOptions'] += $urlParams['driverOptions'];

        unset($params['url']);
        return $params + (new DsnParser())->parse($url);
    }

    /**
     * キャッシュに有ったらそれを、無かったら登録して返す
     */
    public static function cacheByHash(CacheInterface $cacher, string $key, \Closure $provider, ?int $ttl = null): mixed
    {
        $cacheid = "Adhoc-" . hash('fnv164', $key . (new \ReflectionFunction($provider)));

        $cache = $cacher->get($cacheid) ?? [];
        if (!array_key_exists($key, $cache)) {
            $result = $provider($key);
            if ($result === null) {
                return null;
            }
            $cache[$key] = $result;
            $cacher->set($cacheid, $cache, $ttl);
        }
        return $cache[$key];
    }

    /**
     * 値が「空」なら true を返す
     */
    public static function is_empty($value): bool
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
        if ($value instanceof SelectBuilder && $value->isEmptyCondition()) {
            return true;
        }

        return false;
    }

    /**
     * 指定配列を個数に応じて再帰的に小括弧()で包む
     */
    public static function wrapParentheses(array $array): array
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
     */
    public static function containQueryable($array): bool
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
     */
    public static function modifier(string $tablename, array $tablecolumns, array $array): array
    {
        if (!strlen($tablename)) {
            return $array;
        }

        $result = [];
        foreach ($array as $key => $val) {
            // SelectBuilder で submethod ならプレフィックスを付けない
            if ($val instanceof SelectBuilder && ($val->getSubmethod() !== null && $val->getSubmethod() !== 'query')) {
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

    public static function bindableTypes(iterable $params): array
    {
        // 実質的に bindableParameters の後に呼ばれるのが前提なので enum とか is_object は考慮しない
        $types = [];
        foreach ($params as $k => $param) {
            if (is_null($param)) {
                $types[$k] = ParameterType::NULL;
            }
            elseif (is_bool($param)) {
                $types[$k] = ParameterType::BOOLEAN;
            }
            elseif (is_int($param)) {
                $types[$k] = ParameterType::INTEGER;
            }
            else {
                $types[$k] = null; // 決め打ちしない（null にすることで呼び元で ?? Hoge できるようにする）
            }
        }
        return $types;
    }
}
