<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\dbml\Query\TableDescriptor;

trait SubAggregateTrait
{
    /**
     * 相関サブクエリの EXISTS を表すビルダを返す
     *
     * ```php
     * // SELECT 句での使用例
     * $db->select([
     *     't_article' => [
     *         // 各 t_article に紐づく t_comment にレコードを持つなら true が返される
     *         'has_comment'     => $db->subexists('t_comment'),
     *         // 各 t_article に紐づく t_comment delete_flg = 0 なレコードを持たないなら true が返される
     *         'has_not_comment' => $db->notSubexists('t_comment', ['delete_flg' => 0]),
     *     ],
     * ]);
     * // SELECT
     * //   EXISTS (SELECT * FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS has_comment,
     * //   NOT EXISTS (SELECT * FROM t_comment WHERE (delete_flg = '0') AND (t_comment.article_id = t_article.article_id)) AS has_not_comment
     * // FROM t_article
     *
     * // WHERE 句での使用例
     * $db->select('t_article', [
     *     // 「各記事でコメントを持つ記事」を表す WHERE EXISTS になる
     *     $db->subexists('t_comment'),
     * ]);
     * // SELECT
     * //   t_article.*
     * // FROM t_article
     * // WHERE (EXISTS (SELECT * FROM t_comment WHERE t_comment.article_id = t_article.article_id))
     *
     * // JOIN も含めて複数テーブルがあり、明確に「t_article と t_comment で」結びたい場合はキーで明示する
     * $db->select('t_article, t_something', [
     *     // 「何と？」をキーで明示できる
     *     't_article'          => $db->subexists('t_comment'),
     *     // これだと t_something と t_comment での結合となる（外部キーがあれば、だが）
     *     't_something'        => $db->subexists('t_comment'),
     *     // さらに t_something に複数の外部キーがある場合は:で明示できる
     *     't_something:fkname' => $db->subexists('t_comment'),
     * ]);
     * ```
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     * @param array|string $where WHERE 条件（{@link SelectBuilder::where()}）
     * @return SelectBuilder クエリビルダオブジェクト
     */
    public function subexists($tableDescriptor = [], $where = [])
    {
        return $this->subquery($tableDescriptor, $where)->setSubmethod(true);
    }

    /**
     * {@link subexists()} の否定版
     *
     * @inheritdoc subexists()
     */
    public function notSubexists($tableDescriptor = [], $where = [])
    {
        return $this->subquery($tableDescriptor, $where)->setSubmethod(false);
    }

    /**
     * 相関サブクエリの COUNT を表すビルダを返す（{@uses Database::subaggregate()} を参照）
     *
     * @inheritdoc Database::subaggregate()
     */
    public function subcount($column = [], $where = [])
    {
        return $this->subaggregate('count', $column, $where);
    }

    /**
     * 相関サブクエリの MIN を表すビルダを返す（{@uses Database::subaggregate()} を参照）
     *
     * @inheritdoc Database::subaggregate()
     */
    public function submin($column = [], $where = [])
    {
        return $this->subaggregate('min', $column, $where);
    }

    /**
     * 相関サブクエリの MAX を表すビルダを返す（{@uses Database::subaggregate()} を参照）
     *
     * @inheritdoc Database::subaggregate()
     */
    public function submax($column = [], $where = [])
    {
        return $this->subaggregate('max', $column, $where);
    }

    /**
     * 相関サブクエリの SUM を表すビルダを返す（{@uses Database::subaggregate()} を参照）
     *
     * @inheritdoc Database::subaggregate()
     */
    public function subsum($column = [], $where = [])
    {
        return $this->subaggregate('sum', $column, $where);
    }

    /**
     * 相関サブクエリの AVG を表すビルダを返す（{@uses Database::subaggregate()} を参照）
     *
     * @inheritdoc Database::subaggregate()
     */
    public function subavg($column = [], $where = [])
    {
        return $this->subaggregate('avg', $column, $where);
    }

    /**
     * 相関サブクエリの MEDIAN を表すビルダを返す（{@uses Database::subaggregate()} を参照）
     *
     * @inheritdoc Database::subaggregate()
     */
    public function submedian($column = [], $where = [])
    {
        throw new \LogicException('Not implemented');
    }
}
