<?php

namespace ryunosuke\dbml\Mixin;

/**
 * オプションを保持し、get/set できるようにする trait
 *
 * use する側は必ず getDefaultOptions を実装する。
 *
 * このトレイトを使うと「その場限りの設定変更」が容易になる。
 * 具体的には `stack/unstack` や `context` を使用して一時的に設定を変更し、不要になったときに一気に戻す。
 * `stack/unstack` や `context` の違いは「明示的に戻す必要があるか」である。以下に例を挙げる。
 *
 * ```php
 * # 今だけは hoge:1 にしたい
 * $that->stack();
 * $that->setOption('hoge', 1);
 * $that->doSomething(); // この処理は hoge:1 になっている
 * $that->unstack(); // 終わったので元に戻す
 *
 * # 今だけは hoge:2 にしたい
 * $cx = $that->context();
 * $cx->setOption('hoge', 2);
 * $cx->doSomething(); // この処理は hoge:2 になっている
 * unset($cx); // 終わったので元に戻す
 *
 * # 今だけは hoge:3 にしたい
 * $that->context(['hoge' => 3])->doSomething(); // この処理は hoge:3 になっている
 * // 終わったので元に戻す…必要はない。 context 既に参照が切れており、 RAII により既に元に戻っている
 * ```
 *
 * stack/context の併用は出来ない（併用したときの動作は未定義）が、併用さえしなければどちらもネスト可能。
 * ただし、 `context` は自身を clone するのでループ内での使用は控えること。
 */
trait OptionTrait
{
    private array $__options = [];

    private \ArrayObject $__stacking;

    private self $__original;

    /**
     * オプションのデフォルト値を返す static メソッド
     *
     * このメソッドの返り値が構成要素とデフォルト値を担っていて、その配列以外のキーは基本的に保持できない。
     */
    public static function getDefaultOptions(): array
    {
        throw new \DomainException('must be implemented getDefaultOptions.');
    }

    /**
     * マジックメソッド __call 用メソッド
     *
     * OptionTrait\_\_callGetSet('getHoge')         で getOption('hoge') が起動する。
     * OptionTrait\_\_callGetSet('setHoge', $value) で setOption('hoge', $value) が起動する。
     *
     * マッチしてコールされたら $called に true が格納される。
     *
     * @ignoreinherit
     */
    protected function OptionTrait__callGetSet(string $name, array $arguments, ?bool &$called): mixed
    {
        $called = false;
        if (preg_match('#^(get|set)(.+)$#u', $name, $m)) {
            $called = true;
            $getset = $m[1] . 'option';
            array_unshift($arguments, lcfirst($m[2]));
            return $this->$getset(...$arguments);
        }
        return null;
    }

    /**
     * マジックメソッド __call 用メソッド
     *
     * OptionTrait\_\_callOption('hoge')         で getOption('hoge') が起動する。
     * OptionTrait\_\_callOption('hoge', $value) で setOption('hoge', $value) が起動する。
     *
     * マッチしてコールされたら $called に true が格納される
     *
     * @ignoreinherit
     */
    protected function OptionTrait__callOption(string $name, array $arguments, ?bool &$called): mixed
    {
        $called = false;
        if ($this->existsOption($name)) {
            $called = true;
            array_unshift($arguments, $name);
            return $this->option(...$arguments);
        }
        return null;
    }

    /**
     * マジックメソッド __call 用メソッド
     *
     * OptionTrait\_\_callGetSet と OptionTrait\_\_callOption を呼び出し、マッチしなければ例外を投げる。
     *
     * @ignoreinherit
     */
    protected function OptionTrait__call(string $name, array $arguments): mixed
    {
        $result = $this->OptionTrait__callGetSet($name, $arguments, $called);
        if ($called) {
            return $result;
        }
        $result = $this->OptionTrait__callOption($name, $arguments, $called);
        if ($called) {
            return $result;
        }

        throw new \BadMethodCallException("$name does not exists.");
    }

    /**
     * マジックメソッド __destruct 用メソッド
     *
     * 自身が context で生成されていたら unstack する。
     *
     * @ignoreinherit
     */
    public function OptionTrait__destruct()
    {
        if (isset($this->__original) && $this->_getStacking()->count()) {
            $this->unstack();
        }
    }

    /**
     * デストラクタのデフォルト実装
     *
     * デストラクタはコンストラクタに比べてそれほど実装されないので trait 側で定義してしまって良いと判断。
     * use 側でデストラクタを定義したい場合は OptionTrait__destruct を呼ぶようにすること。
     *
     * @ignoreinherit
     */
    public function __destruct()
    {
        $this->OptionTrait__destruct();
    }

    private function _getStacking()
    {
        // コンストラクタでの生成は use 先で強制できないので getter で生成する
        return $this->__stacking ??= new \ArrayObject();
    }

    public function checkUnknownOption(array $options): bool
    {
        if ($diff = array_diff_key($options, static::getDefaultOptions())) {
            trigger_error(sprintf('%s specified unknown options(%s)', get_class($this), implode(',', array_keys($diff))), E_USER_NOTICE);
            return false;
        }
        return true;
    }

    /**
     * デフォルト値を設定する
     *
     * このメソッドでオプション項目が確定するので、これを呼ばないと何も出来ない。
     *
     * @ignoreinherit
     */
    public function setDefault(array $overridden = []): static
    {
        $default = static::getDefaultOptions();
        $this->__options = array_intersect_key($overridden, $default) + $default;

        if (isset($this->__original)) {
            $this->__original->setDefault($this->__options);
        }

        return $this;
    }

    /**
     * 大本のオブジェクトを返す
     *
     * {@link context()} している場合に、オリジナルの $this を返す。
     *
     * @ignoreinherit
     */
    public function getOriginal(): static
    {
        $that = $this;
        while (isset($that->__original)) {
            $that = $that->__original;
        }
        return $that;
    }

    /**
     * コンテキスト（一過性の自分自身）を生成して返す
     *
     * このメソッドを呼ぶと「自身の設定と同一のインスタンス」が得られる。
     * そのインスタンスへの操作は自身・オリジナルに反映されるが、スコープを外れるとオリジナルの方は元に戻る。
     *
     * ```php
     * // somthing が呼ばれる時点では hoge:1, fuga:2 となっている
     * $that->context(['hoge' => 1])->setOption('fuga', 2)->somthing();
     * // この時点で参照が切れているので hoge, fuga の値は元に戻っている
     * $that->somthing();
     * ```
     *
     * @ignoreinherit
     */
    public function context(array $options = []): static
    {
        $this->stack();

        $that = clone $this;
        $that->__original = $this;
        return $that->setOptions($options);
    }

    /**
     * 現オプションをスタックに積む
     *
     * このメソッドを呼ぶと「現時点の設定」が保存される。
     * その後、いかなる変更を加えても unstack を呼べば保存された「現時点の設定」へ戻すことができる。
     * 名前の通りスタック構造なので、保存はスタッカブルに行われる。
     *
     * ```php
     * // somthing が呼ばれる時点では hoge:1, fuga:2 となっている
     * $that->stack(['hoge' => 1])->setOption('fuga', 2);
     * $that->somthing();
     * // この時点ではまだ hoge:1, fuga:2 のままなので下記のように明示的に戻す必要がある
     * $that->unstack();
     * // この時点で hoge, fuga の値は元に戻っている
     * $that->somthing();
     * ```
     *
     * @ignoreinherit
     */
    public function stack(array $options = []): static
    {
        $stack = $this->_getStacking();

        $next_index = $stack->count();
        $stack[$next_index] = $this->getOptions();
        return $this->setOptions($options);
    }

    /**
     * {@link stack()} で積んだオプションを復元する
     *
     * @ignoreinherit
     */
    public function unstack(): static
    {
        $stack = $this->_getStacking();

        if (!$stack->count()) {
            throw new \UnexpectedValueException('stack is empty.');
        }

        $last_index = $stack->count() - 1;
        $last = $stack[$last_index];
        unset($stack[$last_index]);

        $this->setDefault($last);
        return $this;
    }

    /**
     * 戻せるまで {@link unstack()} する
     *
     * @ignoreinherit
     */
    public function unstackAll(): static
    {
        $stack = $this->_getStacking();

        if (!$stack->count()) {
            return $this;
        }

        $this->setDefault($stack[0]);
        $stack->exchangeArray([]);
        return $this;
    }

    /**
     * 全オプション値を返却する
     *
     * @ignoreinherit
     */
    public function getOptions(): array
    {
        return $this->__options;
    }

    /**
     * 単一のオプション値を返却する
     *
     * オプション名が存在しない場合、例外が飛ぶ。
     *
     * @ignoreinherit
     */
    public function getOption(string $name): mixed
    {
        if (!$this->existsOption($name)) {
            throw new \InvalidArgumentException("$name is not option.");
        }
        return $this->getUnsafeOption($name);
    }

    /**
     * 単一のオプション値を返却する(キーチェックなし)
     *
     * オプション名が存在しない場合、例外は飛ばないが notice は出るかもしれない。
     *
     * @ignoreinherit
     */
    protected function getUnsafeOption(string $name): mixed
    {
        return $this->__options[$name];
    }

    /**
     * 配列でオプション値を設定する
     *
     * @ignoreinherit
     */
    public function setOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            if ($this->existsOption($key)) {
                $this->setUnsafeOption($key, $value);
            }
        }

        return $this;
    }

    /**
     * 単一のオプション値を設定する
     *
     * オプション名が存在しない場合、例外が飛ぶ。
     *
     * @ignoreinherit
     */
    public function setOption(string $name, mixed $value): static
    {
        if (!$this->existsOption($name)) {
            throw new \InvalidArgumentException("$name is not option.");
        }
        return $this->setUnsafeOption($name, $value);
    }

    /**
     * 単一のオプション値を設定する(キーチェックなし)
     *
     * オプション名が存在しない場合、例外は飛ばないし、 getDefaultOptions で規定されている項目外も設定可能。
     *
     * @ignoreinherit
     */
    protected function setUnsafeOption(string $name, mixed $value): static
    {
        $this->__options[$name] = $value;

        if (isset($this->__original)) {
            $this->__original->setUnsafeOption($name, $value);
        }

        return $this;
    }

    /**
     * 配列でオプション値を設定し、返り値として元の値に戻すクロージャを返す
     *
     * ```php
     * // 設定を hoge:1 にする
     * $restore = $that->storeOptions(['hoge' => 1]);
     * // この時点では hoge:1 のまま
     * $that->something();
     * // $retore を呼ぶと設定が元に戻る
     * $retore();
     * ```
     *
     * @ignoreinherit
     */
    public function storeOptions(array $options): \Closure
    {
        $backup = [];
        foreach ($options as $key => $value) {
            if ($this->existsOption($key)) {
                $backup[$key] = $this->getUnsafeOption($key);
                $this->setOption($key, $value);
            }
        }
        return function () use ($backup) {
            return $this->setOptions($backup);
        };
    }

    /**
     * 配列のオプション値をマージする
     *
     * @ignoreinherit
     */
    public function mergeOption(string $name, array $options): static
    {
        if (!$this->existsOption($name)) {
            throw new \InvalidArgumentException("$name is not option.");
        }
        if (!(is_array($options) && is_array($this->__options[$name]))) {
            throw new \InvalidArgumentException("$name requires array.");
        }
        return $this->mergeUnsafeOption($name, $options);
    }

    /**
     * 配列のオプション値をマージする(キーチェックなし)
     *
     * @ignoreinherit
     */
    protected function mergeUnsafeOption(string $name, array $options): static
    {
        return $this->setUnsafeOption($name, array_replace_recursive($this->__options[$name], $options));
    }

    /**
     * オプションが存在するなら true を返す
     *
     * @ignoreinherit
     */
    public function existsOption(string $name): bool
    {
        return array_key_exists($name, $this->__options);
    }

    /**
     * 引数が1つなら get、引数が2つなら set する
     *
     * ```php
     * // getter として働く
     * $that->option('opt-name');
     * // setter として働く
     * $that->option('opt->name', 'opt-value');
     * ```
     *
     * @ignoreinherit
     */
    public function option(string $name, mixed $value = null): mixed
    {
        if (func_num_args() === 1) {
            return $this->getOption($name);
        }
        else {
            return $this->setOption($name, $value);
        }
    }
}
