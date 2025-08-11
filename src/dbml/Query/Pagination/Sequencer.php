<?php

namespace ryunosuke\dbml\Query\Pagination;

use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Query\SelectBuilder;

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

    private SelectBuilder $builder;

    private array $condition;

    private int $count;

    private bool $order;

    private bool $more;

    /**
     * コンストラクタ
     */
    public function __construct(SelectBuilder $builder)
    {
        $this->builder = $builder;
        $this->setProvider(function () {
            $keys = array_keys($this->condition);
            $vals = array_values($this->condition);

            $key = count($keys) > 1 ? '(' . implode(',', $keys) . ')' : reset($keys);
            $val = count($vals) > 1 ? '(' . implode(',', array_fill(0, count($vals), '?')) . ')' : '?';
            $bind = count($vals) > 1 ? $vals : (intval(abs(reset($vals))) ?: '');

            $currentby = $this->builder->getQueryPart('orderBy');

            // アイテムを取得
            $provider = clone $this->builder;
            $provider->andWhere(["!$key " . ($this->order ? "> $val" : "< $val") => $bind]);
            $provider->orderBy(array_fill_keys($keys, $this->order) + $currentby);
            $provider->limit($this->count + 1, 0);
            $items = $provider->array();

            // 1件多く取っているので指定件数以上なら「次がある」になる
            $this->more = false;
            if ($this->count < count($items)) {
                array_pop($items);
                $this->more = true;
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
    public function sequence(array $condition, int $count, bool $orderbyasc = true): static
    {
        // 再生成のために null っとく
        $this->more = false;
        $this->resetResult();

        if (count($condition) === 0) {
            throw new \InvalidArgumentException('$condition\'s length must be > 0.');
        }

        if ($count <= 0) {
            throw new \InvalidArgumentException('$count must be positive number.');
        }

        $this->condition = $condition;
        $this->count = $count;
        $this->order = $orderbyasc;

        // トランザクション中はいつ呼ばれるかわからないので強制的に呼んでおかなければならない
        if ($this->builder->getDatabase()->getConnection()->getTransactionNestingLevel()) {
            $this->getResult();
        }

        return $this;
    }

    /**
     * 現在アイテムを取得する
     */
    public function getItems(): array
    {
        return $this->getResult();
    }

    /**
     * 次アイテムが存在するかを返す
     */
    public function hasMore(): bool
    {
        // クエリを投げないと分からないため、呼んでおく必要がある
        $this->getItems();

        return $this->more;
    }
}
