<?php

namespace ryunosuke\dbml\Query\Pagination;

use ryunosuke\dbml\Exception\InvalidCountException;
use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Query\QueryBuilder;

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

    /** @var QueryBuilder クエリビルダ */
    private $builder;

    /** @var int 現在ページ */
    private $page;

    /** @var int 全アイテム数 */
    private $total;

    /** @var int 表示するページ数 */
    private $shownPage;

    /** @var array ページ配列 */
    private $pageRange;

    /**
     * コンストラクタ
     *
     * @param QueryBuilder $builder ページングに使用するクエリビルダ
     */
    public function __construct(QueryBuilder $builder)
    {
        $option = $builder->getDatabase()->getCompatiblePlatform()->getFoundRowsOption();
        $this->builder = $builder->addSelectOption($option)->setRowCountable(true);
        $this->setProvider(function () {
            return $this->builder->array();
        });
    }

    /**
     * 現在ページとページ内アイテム数を設定する
     *
     * @param int $currentpage 現在ページ数。1ベース
     * @param int $countperpage 1ページ内のアイテム数
     * @param ?int $shownpage 表示するページ数。奇数が望ましい。省略時全ページ表示
     * @return $this 自分自身
     */
    public function paginate($currentpage, $countperpage, $shownpage = null)
    {
        $this->total = null;
        $this->pageRange = null;
        $this->resetResult();

        $countperpage = intval($countperpage);
        if ($countperpage <= 0) {
            throw new \InvalidArgumentException('$countperpage must be positive number.');
        }

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        $this->page = intval($currentpage - 1);

        $this->builder->limit($countperpage, $this->page * $countperpage);

        $this->shownPage = $shownpage;

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
     * 現在ページを返す
     *
     * @return int 現在ページ
     */
    public function getPage()
    {
        return $this->page + 1;
    }

    /**
     * 最初のインデックスを返す
     *
     * 総数が0の時は0を返す
     *
     * @return int 最初のインデックス
     */
    public function getFirst()
    {
        if ($this->getTotal() === 0) {
            return 0;
        }
        return $this->builder->getQueryPart('offset') + 1;
    }

    /**
     * 最後のインデックスを返す
     *
     * 総数が0の時は0を返す
     *
     * @return int 最後のインデックス
     */
    public function getLast()
    {
        if ($this->getTotal() === 0) {
            return 0;
        }
        return $this->getFirst() + count($this->getItems()) - 1;
    }

    /**
     * 全アイテム数を返す
     *
     * @return int 全アイテム数
     */
    public function getTotal()
    {
        if (!isset($this->total)) {
            // for mysql
            $this->getItems();

            $this->total = $this->builder->getRowCount();
        }

        return $this->total;
    }

    /**
     * 表示ページを配列で返す
     *
     * @return array 表示ページ配列
     */
    public function getPageRange()
    {
        // カスみたいな負荷だと思うが一応キャッシュ
        if (!isset($this->pageRange)) {
            $pagecount = $this->getPageCount();
            if ($this->shownPage === null) {
                $this->pageRange = range(1, $pagecount);
            }
            elseif ($pagecount === 0) {
                $this->pageRange = [];
            }
            else {
                $offset = $this->getPage() - intval($this->shownPage / 2);
                $min = 1;
                $max = $pagecount - $this->shownPage + 1;
                $offset = max($min, min($max, $offset));
                $this->pageRange = range($offset, $offset + min($pagecount, $this->shownPage) - 1);
            }
        }

        return $this->pageRange;
    }

    /**
     * 全ページ数を返す
     *
     * @return int 全ページ数
     */
    public function getPageCount()
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
     *
     * @return bool 前ページが存在するか
     */
    public function hasPrev()
    {
        return $this->getPage() > 1;
    }

    /**
     * 次ページが存在するかを返す
     *
     * @return bool 次ページが存在するか
     */
    public function hasNext()
    {
        return $this->getPage() < $this->getPageCount();
    }
}
