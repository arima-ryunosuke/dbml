<?php

namespace ryunosuke\dbml\Mixin;

use function ryunosuke\dbml\first_keyvalue;

/**
 * イテレータ（主に結果セット）を利用しやすくするための trait
 *
 * 結果セットプロバイダを渡すと \Countable::count, \IteratorAggregate::getIterator においてその結果セットの値を返すようになる。
 */
trait IteratorTrait
{
    /** @var \Closure 行プロバイダ */
    private $__provider = null;

    /** @var array ↑の引数 */
    private $__args = [];

    /** @var array 結果セット配列 */
    private $__result = null;

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
     * @return $this 自分自身
     */
    public function setProvider($caller)
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
     *
     * @return $this 自分自身
     */
    public function resetProvider()
    {
        $this->__provider = null;
        $this->__args = [];
        return $this;
    }

    /**
     * 結果セットをクリアして無効化する
     *
     * @ignoreinherit
     *
     * @return $this 自分自身
     */
    public function resetResult()
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
     *
     * @return array 結果セット
     */
    public function getResult()
    {
        if ($this->__provider === null) {
            throw new \UnexpectedValueException('provider is not set.');
        }

        if ($this->__result === null) {
            $this->__result = $this->__provider->call($this, ...$this->__args);
        }
        return $this->__result;
    }

    /**
     * 結果セットのイテレータを返す
     *
     * @ignoreinherit
     * @see http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return \ArrayIterator 結果セットのイテレータ
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
     *
     * @return int 結果セットの件数
     */
    public function count(): int
    {
        return count($this->getResult());
    }
}
