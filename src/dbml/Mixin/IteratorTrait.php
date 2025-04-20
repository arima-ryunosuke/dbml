<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\utility\attribute\Attribute\DebugInfo;
use function ryunosuke\dbml\first_keyvalue;

/**
 * イテレータ（主に結果セット）を利用しやすくするための trait
 *
 * 結果セットプロバイダを渡すと \Countable::count, \IteratorAggregate::getIterator においてその結果セットの値を返すようになる。
 */
trait IteratorTrait
{
    private \Closure $__provider;

    private array $__args = [];

    private array $__applyments = [];

    #[DebugInfo(false)]
    private ?array $__result = null;

    /**
     * 結果セットプロバイダを登録する
     *
     * クロージャを渡すと単純にそのクロージャがコールされる。
     * 文字列を渡すとメソッド名でコールする。
     * 要素が一つだけの配列を与えるとキーをメソッド名、値を引数としてコールする。
     *
     * いずれにせよ、全てクロージャに変換され、そのクロージャの $this はこのトレイトを use しているインスタンス自身になる。
     *
     * ```php
     * // クロージャが単純にコールされる
     * $that->setProvider(function () {return (array) $this;});
     * // $that->method() がコールされる
     * $that->setProvider('method');
     * // $that->method(1, 2, 3) がコールされる
     * $that->setProvider(['method'] => [1, 2, 3]);
     * ```
     *
     * @ignoreinherit
     *
     * @param array|string|\Closure $caller プロバイダ
     */
    public function setProvider($caller): static
    {
        if (is_array($caller) && count($caller) === 1) {
            [$caller, $args] = first_keyvalue($caller);
            $caller = function () use ($caller) { return $this->$caller(...func_get_args()); };
        }
        elseif (is_string($caller)) {
            $args = [];
            $caller = function () use ($caller) { return $this->$caller(...func_get_args()); };
        }
        elseif ($caller instanceof \Closure) {
            $args = [];
        }
        else {
            throw new \InvalidArgumentException('$caller is invalid.');
        }

        $this->__provider = $caller;
        $this->__args = $args;
        $this->__result = null;
        return $this;
    }

    /**
     * 結果セットプロバイダを解除する
     *
     * @ignoreinherit
     */
    public function resetProvider(): static
    {
        unset($this->__provider);
        $this->__args = [];
        return $this;
    }

    /**
     * 結果セットをクリアして無効化する
     *
     * @ignoreinherit
     */
    public function resetResult(): static
    {
        $this->__result = null;
        return $this;
    }

    /**
     * 結果セットを取得する
     *
     * 結果はキャッシュされるため、複数回呼んでも問題ない。
     *
     * @ignoreinherit
     */
    public function getResult(): array
    {
        if (!isset($this->__provider)) {
            throw new \UnexpectedValueException('provider is not set.');
        }

        return $this->__result ??= (function () {
            $result = [];
            $i = 0;
            foreach ($this->__provider->call($this, ...$this->__args) as $n => $item) {
                foreach ($this->__applyments as $applyment) {
                    $item = $applyment($item, $n, $i);
                }
                $result[$n] = $item;
                $i++;
            }
            return $result;
        })();
    }

    /**
     * 結果セットの map 処理を登録する
     *
     * 複数呼べば複数実行される。
     *
     * @ignoreinherit
     */
    public function apply(callable $callback): static
    {
        $this->__applyments[] = \Closure::fromCallable($callback);
        return $this;
    }

    /**
     * 結果セットのイテレータを返す
     *
     * @ignoreinherit
     * @see http://php.net/manual/en/iteratoraggregate.getiterator.php
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->getResult());
    }

    /**
     * 結果セットの件数を返す
     *
     * @ignoreinherit
     * @see http://php.net/manual/en/countable.count.php
     */
    public function count(): int
    {
        return count($this->getResult());
    }
}
