<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Attribute\AssumeType;
use ryunosuke\dbml\Database;
use function ryunosuke\dbml\first_value;

trait AggregateTrait
{
    /**
     * レコードが子行などで使用されているかを返す
     *
     * このメソッドは本質的には「エラーなく削除できるか？（RESTRICT な外部キーで使用されているか？）」を前提にしている。
     * 後ろ3つの引数を true にするとそれぞれ（RESTRICT/CASCADE/SET NULL）の外部キーの場合も使用されているとみなす。
     * これは CASCADE/SET NULL の場合も注意喚起したいことはあるかもしれないのでフラグ化してある。
     * この3つの引数を渡す場合は必ず名前付き引数で呼び出さなければならない。
     *
     * 返り値は bool なので大前提としてレコードは1件だけ返すような WHERE でなければならない。
     * （assoc や pairs のような形式で返す案もあったが複雑になりそうだし用途がないのでやめた）。
     *
     * そもそもこのメソッドは subusing の対となるために生まれたもので、直値を得たいケースも少なく、あまり使用を想定していない。
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     * @param array|string $where WHERE 条件（{@link SelectBuilder::where()}）
     * @param bool $restrict RESTRICT 外部キーを見るか
     * @param bool $cascade CASCADE 外部キーを見るか
     * @param bool $setnull SET NULL 外部キーを見るか
     */
    private function usingWithTable($tableDescriptor, $where = [], bool $restrict = true, bool $cascade = false, bool $setnull = false): bool
    {
        $select = $this->select($tableDescriptor, $where);
        $from = first_value($select->getFromPart());

        $actions = [
            'restrict' => $restrict,
            'cascade'  => $cascade,
            'setnull'  => $setnull,
        ];
        $subwheres = $select->foreignWheres($from['table'], $from['alias'], [
            'update' => $actions,
            'delete' => $actions,
        ], true);

        // 外部キーがない/CASCADE 条件などで subwhere がない ならクエリを投げるまでもない
        if (!$subwheres) {
            return false;
        }

        $column = $this->getDatabase()->operator(...$subwheres);
        $column = $this->getDatabase()->getCompatiblePlatform()->convertSelectExistsQuery($column);

        // bool を value(単値) で返すのはなんとなく怖いので tuple で得る
        $tuple = $select->select($column)->detectAutoOrder(false)->tuple();

        // 存在しないなら使われていないとみなす（null とかでも面白いかもしれない）
        if (!$tuple) {
            return false;
        }
        return !!first_value($tuple);
    }

    /**
     * レコードが子行などで使用されているかを返す
     *
     * @see usingWithTable()
     * @inheritdoc usingWithTable()
     */
    private function usingWithoutTable(
        #[AssumeType('shape')] $where = [],
        bool $restrict = true,
        bool $cascade = false,
        bool $setnull = false
    ): bool {
        return $this->usingWithTable([], $where, $restrict, $cascade, $setnull);
    }

    /**
     * レコードの存在を返す
     *
     * ```php
     * # 単純に t_article が存在するか bool で返す
     * $db->exists('t_article');
     * // SELECT EXISTS (SELECT * FROM t_article)
     *
     * # 有効な t_article が存在するか bool で返す
     * $db->exists('t_article', ['delete_flg' => 0]);
     * // SELECT EXISTS (SELECT * FROM t_article WHERE t_article.delete_flg = 0)
     *
     * # 有効な t_article が存在するかロックしつつ bool で返す
     * $db->exists('t_article', ['delete_flg' => 0], true);
     * // SELECT EXISTS (SELECT * FROM t_article WHERE t_article.delete_flg = 0 FOR UPDATE)
     * ```
     *
     * @inheritdoc Database::select()
     *
     * @param bool $for_update EXISTS チェックはしばしばロックを伴うのでそのフラグ
     * @return bool レコードが存在するなら true
     */
    public function exists($tableDescriptor = [], $where = [], $for_update = false)
    {
        $builder = $this->selectExists($tableDescriptor, $where, $for_update);
        $exists = $this->getDatabase()->getCompatiblePlatform()->convertSelectExistsQuery($builder);
        return !!$this->getDatabase()->fetchValue("SELECT $exists", $exists->getParams());
    }

    /**
     * COUNT クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function count($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('count', $column, $where, $groupBy, $having);
    }

    /**
     * MIN クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function min($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('min', $column, $where, $groupBy, $having);
    }

    /**
     * MAX クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function max($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('max', $column, $where, $groupBy, $having);
    }

    /**
     * SUM クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function sum($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('sum', $column, $where, $groupBy, $having);
    }

    /**
     * AVG クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function avg($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('avg', $column, $where, $groupBy, $having);
    }

    /**
     * MEDIAN クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function median($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('median', $column, $where, $groupBy, $having);
    }

    /**
     * JSON 集約クエリを実行する（{@uses Database::aggregate()} を参照）
     *
     * @inheritdoc Database::aggregate()
     */
    public function json($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->aggregate('jsonAgg', $column, $where, $groupBy, $having);
    }
}
