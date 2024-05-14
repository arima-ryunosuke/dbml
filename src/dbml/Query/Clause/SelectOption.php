<?php

namespace ryunosuke\dbml\Query\Clause;

/**
 * SELECT オプションクラス
 *
 * このクラスのインスタンスを select すると、カラムとして追加されるのではなく、 SELECT 句の冒頭にカンマ無しで展開される。
 * `SELECT OPT1 OPT2 column1, column2` のような形を実現するためのクラス。
 *
 * ```php
 * $db->select('tablename.columname')->addSelectOption(SelectOption::DISTINCT);
 * // SELECT DISTINCT columnname FROM tablename
 * ```
 *
 * @method static $this   DISTINCT()
 * @method static $this   SQL_CACHE()
 * @method static $this   SQL_NO_CACHE()
 * @method static $this   SQL_CALC_FOUND_ROWS()
 * @method static $this   STRAIGHT_JOIN()
 */
class SelectOption
{
    /// 固定プション文字列（よく使われるのをコード補完のために定数化しているだけでそれ以上の意味はない）
    public const DISTINCT            = 'DISTINCT';
    public const SQL_CACHE           = 'SQL_CACHE';
    public const SQL_NO_CACHE        = 'SQL_NO_CACHE';
    public const SQL_CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';
    public const STRAIGHT_JOIN       = 'STRAIGHT_JOIN';

    /** @var string */
    private $expr;

    /**
     * インスタンスを返す
     *
     * - `new SelectOption('DISTINCT');`
     * - `SelectOption::DISTINCT();`
     *
     * これらはそれぞれ等価になる
     *
     * @param string $expr 構文
     * @param array $arguments ダミー
     * @return $this SelectOption オブジェクト
     */
    public static function __callStatic($expr, $arguments)
    {
        return new SelectOption($expr);
    }

    /**
     * コンストラクタ
     *
     * valid な文字列かどうかのチェックは行わないので、 SelectOption::DISTINCT のような定数を与えてもよいし、固定文字列を与えても良い。
     *
     * @param string $expr 文字列表現
     */
    public function __construct($expr)
    {
        $this->expr = $expr;
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
}
