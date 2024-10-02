<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\SelectBuilder;

trait SelectAggregateTrait
{
    /**
     * EXISTS クエリビルダを返す
     *
     * ```php
     * // EXISTS (SELECT * FROM t_table)
     * $db->selectExists('t_table');
     *
     * // NOT EXISTS (SELECT * FROM t_table WHERE delete_flg = 0)
     * $db->selectNotExists('t_table', ['delete_flg' => 0]);
     * ```
     *
     * @inheritdoc Database::exists()
     *
     * @return SelectBuilder EXISTS クエリビルダ
     */
    public function selectExists($tableDescriptor = [], $where = [], $for_update = false)
    {
        $builder = $this->select($tableDescriptor, $where)->exists();

        if ($for_update) {
            $builder->lockForUpdate();
        }

        return $builder;
    }

    /**
     * {@link selectExists()} の NOT 版
     *
     * @inheritdoc Database::exists()
     *
     * @return SelectBuilder NOT EXISTS クエリビルダ
     */
    public function selectNotExists($tableDescriptor = [], $where = [], $for_update = false)
    {
        $builder = $this->select($tableDescriptor, $where)->notExists();

        if ($for_update) {
            $builder->lockForUpdate();
        }

        return $builder;
    }

    /**
     * COUNT クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectCount($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('count', $column, $where, $groupBy, $having);
    }

    /**
     * MIN クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectMin($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('min', $column, $where, $groupBy, $having);
    }

    /**
     * MAX クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectMax($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('max', $column, $where, $groupBy, $having);
    }

    /**
     * SUM クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectSum($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('sum', $column, $where, $groupBy, $having);
    }

    /**
     * AVG クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectAvg($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('avg', $column, $where, $groupBy, $having);
    }

    /**
     * MEDIAN クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectMedian($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('median', $column, $where, $groupBy, $having);
    }

    /**
     * JSON 集約クエリを返す（{@uses Database::selectAggregate()} を参照）
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectJson($column, $where = [], $groupBy = [], $having = [])
    {
        return $this->selectAggregate('jsonAgg', $column, $where, $groupBy, $having);
    }

}
