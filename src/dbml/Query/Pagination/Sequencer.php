<?php

namespace ryunosuke\dbml\Query\Pagination;

use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Query\SelectBuilder;
use function ryunosuke\dbml\first_keyvalue;

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
            [$key, $value] = first_keyvalue($this->condition);
            $currentby = $this->builder->getQueryPart('orderBy');

            // アイテムを取得
            $provider = clone $this->builder;
            $provider->andWhere(["!$key " . ($this->order ? '> ?' : '< ?') => $value]);
            $provider->orderBy([$key => $this->order] + $currentby);
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
     * $condition は SIGNED な INT カラムを1つだけ含む配列である必要がある。なぜならば
     *
     * - 2つ以上のタプルの大小関係定義が困難
     *
     * が理由（大抵の場合 AUTO INCREMENT だろうから負数だったりタプルだったりは考慮しないことにする）。
     * もっとも大抵は主キー、あるいはインデックスカラムでほぼ固定だろう。
     */
    public function sequence(array $condition, int $count, bool $orderbyasc = true): static
    {
        // 再生成のために null っとく
        $this->more = false;
        $this->resetResult();

        // シーク条件は1つしかサポートしない(タプルの大小比較は動的生成がとてつもなくめんどくさい。行値式が使えれば別だが…)
        if (count($condition) !== 1) {
            throw new \InvalidArgumentException('$condition\'s length must be 1.');
        }

        if ($count <= 0) {
            throw new \InvalidArgumentException('$count must be positive number.');
        }

        $this->condition = $condition;
        $this->count = $count;
        $this->order = $orderbyasc;

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
