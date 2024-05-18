<?php

namespace ryunosuke\dbml\Query\Clause;

use ryunosuke\dbml\Database;
use function ryunosuke\dbml\concat;

/**
 * カラムエイリアスを表すクラス
 *
 * `new Alias('alias', 'actual')` を select に与えると "actual AS alias" に展開される。
 */
class Select extends AbstractClause
{
    private ?string $alias;

    private mixed $actual;

    private ?string $modifier;

    private bool $placeholdable;

    /**
     * インスタンスを返す
     *
     * - new Alias('hoge', 'actual');
     * - Alias::hoge('actual');
     *
     * これらはそれぞれ等価になる。
     */
    public static function __callStatic(string $alias, array $actuals): static
    {
        if (count($actuals) !== 1) {
            throw new \InvalidArgumentException('argument\'s length must be 1.');
        }
        return new Select($alias, $actuals[0]);
    }

    /**
     * hoge as fuga を分割する
     *
     * @return array [エイリアス, 実名]
     */
    public static function split(string $string, $defaultAlias = null): array
    {
        $parts = preg_split('#(?<!,)\s+(as\s+)?#ui', $string);
        if (count($parts) === 2) {
            return array_reverse($parts + [1 => $defaultAlias]);
        }
        return [$defaultAlias, $string];
    }

    /**
     * 値を Alias 化して返す
     *
     * 変換できなそうならそのまま返す。
     *
     * @param string $alias エイリアス名
     * @param mixed $actual 実名
     * @param string|null $modifier $actual の修飾子
     * @return Select|mixed Alias 化できたら Alias オブジェクト、できなかったら $actual をそのまま返す
     */
    public static function forge($alias, $actual, $modifier = null)
    {
        $alen = strlen($alias);

        // エイリアス名が指定されていないならパース
        if ($alen === 0 && is_string($actual)) {
            [$alias, $actual2] = self::split($actual);
            if ($alias !== null) {
                return new self($alias, $actual2, $modifier);
            }
        }

        // エイリアス名が指定されているならエイリアスとみなす
        if ($alen > 0 && is_string($alias)) {
            return new self($alias, $actual, $modifier, strpos($alias, Database::AUTO_KEY) === 0 || $actual === 'NULL');
        }

        // じゃないなら実部をそのまま返す
        return $actual;
    }

    /**
     * コンストラクタ
     */
    public function __construct(?string $alias, mixed $actual, ?string $modifier = null, bool $placeholdable = false)
    {
        $this->alias = $alias;
        $this->actual = $actual;
        $this->modifier = $modifier;
        $this->placeholdable = $placeholdable;
    }

    /**
     * 文字列表現を返す
     */
    public function __toString(): string
    {
        $alias = $this->alias;
        if (is_string($this->actual) && (explode('.', $this->actual, 2)[1] ?? '') === $alias) {
            $alias = '';
        }

        return $this->actual . concat(' AS ', $alias);
    }

    /**
     * エイリアス名を返す
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * 実部名を返す
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * 修飾子を返す
     */
    public function getModifier(): ?string
    {
        return $this->modifier;
    }

    /**
     * 自動で伏せるべきか
     */
    public function isPlaceholdable(): bool
    {
        return $this->placeholdable;
    }
}
