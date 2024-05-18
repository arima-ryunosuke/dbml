<?php

namespace ryunosuke\dbml\Query\Pagination;

use ryunosuke\dbml\Exception\InvalidCountException;
use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Query\SelectBuilder;

/**
 * クエリビルダを渡して paginate するとページングしてくれるクラス
 *
 * Sequencer と比較して下記の特徴がある。
 *
 * - ページ指定で一気に読み飛ばせる
 *     - 1ページ100件なら11ページへ行くことで一気に1000件飛ばすことができる
 *     - これは逆にデメリットでもあり、あまりに先まで読み飛ばすとその分パフォーマンスは低下する（9999ページとか）
 * - 全件数表示できる
 *     - 「1001～1100 件目/3000 件中」のような表示
 * - 件数取得を伴うので遅い
 *     - ↑のような表示のためではなく「何ページあるか？」の計算にどうしても必要
 *     - ただし mysql の場合は SQL_CALC_FOUND_ROWS + FOUND_ROWS() を用いて高速化される
 * - 「ページ」という概念上、行の増減があると不整合が発生する
 *     - 2ページを見ている時に2ページ目以内の行が削除されると、3ページへ遷移した場合に見落としが発生する（逆に、追加されると同じ行が出現したりする）
 *
 * 要するに普通のページネータである。いわゆるページング（件数少なめ）として使用する。
 *
 * ```php
 * $paginator = new Paginator($db->select('table_name', 'other where'));
 * // 2ページ目のレコードを取得する
 * $paginator->paginate(2, '1ページ内のアイテム数' [, '表示するページ数']);
 * // ページ内アイテムを表示
 * var_dump($paginator->getItems());
 * // IteratorAggregate を実装してるので foreach でも回せる
 * foreach ($paginator as $item) {
 *     var_dump($item);
 * }
 * ```
 */
class Paginator implements \IteratorAggregate, \Countable
{
    use IteratorTrait;

    private SelectBuilder $builder;

    private int $page;

    private int $total;

    /**
     * コンストラクタ
     */
    public function __construct(SelectBuilder $builder)
    {
        $this->builder = $builder;
        $this->setProvider(function () {
            return $this->builder->array();
        });
    }

    /**
     * 現在ページとページ内アイテム数を設定する
     */
    public function paginate(int $currentpage, int $countperpage): static
    {
        $this->resetResult();

        if ($countperpage <= 0) {
            throw new \InvalidArgumentException('$countperpage must be positive number.');
        }

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        $this->page = $currentpage - 1;

        $this->builder->limit($countperpage, $this->page * $countperpage);

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
     * 現在ページを返す
     */
    public function getPage(): int
    {
        return ($this->page ?? 0) + 1;
    }

    /**
     * 最初のインデックスを返す
     *
     * 総数が0の時はnullを返す
     */
    public function getFirst(): ?int
    {
        if ($this->getTotal() === 0) {
            return null;
        }
        return $this->builder->getQueryPart('offset') + 1;
    }

    /**
     * 最後のインデックスを返す
     *
     * 総数が0の時はnullを返す
     */
    public function getLast(): ?int
    {
        if ($this->getTotal() === 0) {
            return null;
        }
        return $this->getFirst() + count($this->getItems()) - 1;
    }

    /**
     * 全アイテム数を返す
     */
    public function getTotal(): int
    {
        return $this->total ??= (int) $this->builder->countize()->value();
    }

    /**
     * 表示ページを配列で返す
     *
     * $shownPage 表示するページ数。奇数が望ましい。省略時全ページ表示。
     */
    public function getPageRange(?int $shownPage = null): array
    {
        $pagecount = $this->getPageCount();
        if ($shownPage === null) {
            return range(1, $pagecount);
        }
        if ($pagecount === 0) {
            return [];
        }
        $offset = $this->getPage() - intval($shownPage / 2);
        $min = 1;
        $max = $pagecount - $shownPage + 1;
        $offset = max($min, min($max, $offset));
        return range($offset, $offset + min($pagecount, $shownPage) - 1);
    }

    /**
     * 全ページ数を返す
     */
    public function getPageCount(): int
    {
        // paginate が呼ばれていない時は 0 を返す（=ページングを行わない）
        if ($this->builder->getQueryPart('limit') === null) {
            return 0;
        }

        $pagecount = intval(ceil($this->getTotal() / $this->builder->getQueryPart('limit')));

        // 最大ページ数を超えているならこのメソッドで例外を投げる
        if ($pagecount !== 0 && $pagecount <= $this->page) {
            throw new InvalidCountException("page length is too long (page: {$this->page}, length: $pagecount).");
        }

        return $pagecount;
    }

    /**
     * 前ページが存在するかを返す
     */
    public function hasPrev(): bool
    {
        return $this->getPage() > 1;
    }

    /**
     * 次ページが存在するかを返す
     */
    public function hasNext(): bool
    {
        return $this->getPage() < $this->getPageCount();
    }
}
