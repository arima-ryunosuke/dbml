<?php

namespace ryunosuke\dbml\Gateway;

use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\parameter_length;

/**
 * スコープを表すクラス
 */
class Scope
{
    private \Closure $provider;
    public array     $params;

    private bool $selective = true;
    private bool $affective = true;

    public static function mergeArray(array ...$variadic_params): array
    {
        $newparams = [];

        foreach ($variadic_params as $params) {
            $newparams = array_merge_recursive($newparams, $params);

            // limit は配列とスカラーで扱いが異なるので「指定されていたら上書き」という挙動にする
            if ($params['limit'] ?? null) {
                $newparams['limit'] = $params['limit'];
            }
        }

        return $newparams;
    }

    public function __construct($column = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [], $set = [])
    {
        if ($column instanceof \Closure) {
            $this->provider = $column;
        }
        else {
            $this->params = [
                'column'  => arrayize($column),
                'where'   => arrayize($where),
                'orderBy' => arrayize($orderBy),
                'limit'   => arrayize($limit),
                'groupBy' => arrayize($groupBy),
                'having'  => arrayize($having),
                'set'     => arrayize($set),
            ];
        }
    }

    public function isClosure(): bool
    {
        return isset($this->provider);
    }

    public function selective(?bool $newValue = null): bool
    {
        if ($newValue === null) {
            return $this->selective;
        }

        $result = $this->selective;
        $this->selective = $newValue;
        return $result;
    }

    public function affective(?bool $newValue = null): bool
    {
        if ($newValue === null) {
            return $this->affective;
        }

        $result = $this->affective;
        $this->affective = $newValue;
        return $result;
    }

    public function resolve($that, ...$args)
    {
        if (!isset($this->provider)) {
            return $this->params;
        }

        $provider = $this->provider;
        if ($that !== null) {
            $provider = $provider->bindTo($that, get_class($that));
        }
        return $provider(...$args);
    }

    public function rearguments(array $args, array &$params): array
    {
        if (!isset($this->provider)) {
            return $args;
        }

        $alength = parameter_length($this->provider, false, true);
        if (is_infinite($alength)) {
            $alength = count($params);
        }
        return array_merge($args, array_splice($params, 0, $alength - count($args), []));
    }

    public function bind(array $binding): static
    {
        // for compatible delete in future scope
        ksort($binding);
        $provider = $this->provider;
        return new static(fn(...$args) => ($provider)(...($args + $binding)));
    }

    public function merge(array ...$variadic_params): static
    {
        return new static(...static::mergeArray($this->params, ...$variadic_params));
    }
}
