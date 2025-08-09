<?php

namespace ryunosuke\dbml\Query\Pagination;

use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Query\QueryBuilder;

/**
 * クエリビルダと条件カラムを渡して sequence するとシーケンシャルアクセスしてくれるクラス
 *
 * Paginator と比較して下記の特徴がある。
 *
 * - 読み飛ばすことが出来ない
 *     - ただし付随条件や id を直指定することで「当たり」をつけることは可能
 * - 全件数表示できない
 *     - 次へ次へと進んで行ってもいつ終わるのか見当がつけられない
 * - 比較的速い
 *     - ただし付随条件によるインデックスの使用可否によっては速くならないので注意
 * - 「前/次」という概念上、行の増減で不整合が発生しない
 *
 * 「前・次のN件」（件数多め）のような UI で使用する。
 *
 * ```php
 * $sequencer = new Sequencer($db->select('table_name', 'other where'));
 * // id が 150 以上のレコードを 50 件取得
 * $sequencer->sequence(['id' => 150], 50 [, '昇順降順フラグ']);
 * // ページ内アイテムを表示
 * var_dump($sequencer->getItems());
 * // IteratorAggregate を実装してるので foreach でも回せる
 * foreach ($sequencer as $item) {
 *     var_dump($item);
 * }
 * ```
 */
class Sequencer implements \IteratorAggregate, \Countable
{
    use IteratorTrait;

    /** @var QueryBuilder クエリビルダ */
    private $builder;

    /** @var array 検索カラム */
    private $condition;

    /** @var int 取得件数 */
    private $count;

    /** @var bool 昇順/降順 */
    private $order;

    /** @var bool 双方向サポート */
    private $bidirection;

    /** @var mixed 前の要素 */
    private $prev;

    /** @var mixed 次の要素 */
    private $next;

    /**
     * コンストラクタ
     *
     * @param QueryBuilder $builder ページングに使用するクエリビルダ
     */
    public function __construct(QueryBuilder $builder)
    {
        $this->builder = $builder;
        $this->setProvider(function () {
            $keys = array_keys($this->condition);
            $vals = array_values($this->condition);

            $key = count($keys) > 1 ? '(' . implode(',', $keys) . ')' : reset($keys);
            $val = count($vals) > 1 ? '(' . implode(',', array_fill(0, count($vals), '?')) . ')' : '?';
            $bind = count($vals) > 1 ? $vals : (intval(abs(reset($vals))) ?: '');

            $order = !(reset($vals) >= 0 xor $this->order);
            $currentby = $this->builder->getQueryPart('orderBy');

            // 補足行を取得
            $appendix = false;
            if ($this->bidirection === true) {
                $appender = clone $this->builder;
                $appender->andWhere(["!$key " . (!$order ? ">= $val" : "<= $val") => $bind]);
                $appender->orderBy(array_fill_keys($keys, !$order) + $currentby);
                $appender->limit(1, 0);
                $appendix = $appender->array();
            }

            // アイテムを取得
            $provider = clone $this->builder;
            $provider->andWhere(["!$key " . ($order ? "> $val" : "< $val") => $bind]);
            $provider->orderBy(array_fill_keys($keys, $order) + $currentby);
            $provider->limit($this->count + ($this->bidirection === null ? 0 : 1), 0);
            $items = $provider->array();

            // 昇順・降順・正順・逆順に基づいて prev/next を設定
            if ($this->bidirection !== null && count($this->condition) === 1) {
                $value = reset($this->condition);
                $garbage = count($items) > $this->count ? array_pop($items) : false;
                if ($value >= 0) {
                    // $items が無い場合は 0 になって頭から始まってしまうので $appendix + 1 にする
                    $last = end($items);
                    $last = $last === false ? [] : $last;
                    $this->prev = $appendix && $value ? [$key => (reset($items)[$key] ?: reset($appendix)[$key] + 1) * -1] : false;
                    $this->next = $garbage ? [$key => $last[$key] ?? null] : false;
                }
                else {
                    $items = array_reverse($items);
                    $last = end($items);
                    $last = $last === false ? [] : $last;
                    $this->prev = $garbage && $value ? [$key => reset($items)[$key] * -1] : false;
                    $this->next = $appendix ? [$key => $last[$key] ?? null] : false;
                }
            }

            return $items;
        });
    }

    /**
     * 読み取り範囲を設定する
     *
     * $condition は初期条件を渡す。この条件は1つの時と2つ以上の時で挙動が全く異なるため注意すること。
     * 具体的には2つ以上の場合は行値式となり、負数という概念が一切通用しなくなる。
     *
     * @param array $condition シーク条件として使用する [カラム => 値]（大抵は主キー、あるいはインデックスカラム）
     * @param int $count 読み取り行数
     * @param bool|null $orderbyasc 昇順/降順。 null を渡すと $condition の符号に応じて自動判別する
     * @param bool|null $bidirection 双方向サポート。双方向だと2クエリ投げられる。 null を渡すと指定数以上取らない（ただし内部仕様）
     * @return $this 自分自身
     */
    public function sequence($condition, $count, $orderbyasc = null, $bidirection = true)
    {
        // 再生成のために null っとく
        $this->prev = false;
        $this->next = false;
        $this->resetResult();

        if (count($condition) === 0) {
            throw new \InvalidArgumentException('$condition\'s length must be > 0.');
        }

        $count = intval($count);
        if ($count <= 0) {
            throw new \InvalidArgumentException('$count must be positive number.');
        }

        $this->condition = $condition;
        $this->count = $count;
        $this->order = $orderbyasc === null ? reset($condition) >= 0 : !!$orderbyasc;
        $this->bidirection = $bidirection;

        // トランザクション中はいつ呼ばれるかわからないので強制的に呼んでおかなければならない
        if ($this->builder->getDatabase()->getConnection()->getTransactionNestingLevel()) {
            $this->getResult();
        }

        return $this;
    }

    /**
     * 現在アイテムを取得する
     *
     * @return array 現在ページ内のアイテム配列
     */
    public function getItems()
    {
        return $this->getResult();
    }

    /**
     * 前以前が存在するならそれを、無いなら false を返す
     *
     * @return array|false 前レコードが存在するならその配列、無いなら false
     */
    public function getPrev()
    {
        // クエリを投げないと分からないため、呼んでおく必要がある
        $this->getItems();

        return $this->prev;
    }

    /**
     * 次以降が存在するならそれを、無いなら false を返す
     *
     * @return array|false 次レコードが存在するならその配列、無いなら false
     */
    public function getNext()
    {
        // クエリを投げないと分からないため、呼んでおく必要がある
        $this->getItems();

        return $this->next;
    }
}
