<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait AggregateTrait
{
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
}
