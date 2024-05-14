<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Query\SelectBuilder;

trait JoinTrait
{
    /**
     * 結合方法が INNER で結合条件指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function joinOn($table, $on, $from = null)
    {
        return $this->join('inner', $table, $on, '', $from);
    }

    /**
     * 結合方法が INNER で結合条件指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function innerJoinOn($table, $on, $from = null)
    {
        return $this->join('inner', $table, $on, '', $from);
    }

    /**
     * 結合方法が LEFT で結合条件指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function leftJoinOn($table, $on, $from = null)
    {
        return $this->join('left', $table, $on, '', $from);
    }

    /**
     * 結合方法が RIGHT で結合条件指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function rightJoinOn($table, $on, $from = null)
    {
        return $this->join('right', $table, $on, '', $from);
    }

    /**
     * 結合方法が AUTO で外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function joinForeign($table, $fkeyname = null, $from = null)
    {
        return $this->join('auto', $table, [], $fkeyname, $from);
    }

    /**
     * 結合方法が AUTO で外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function autoJoinForeign($table, $fkeyname = null, $from = null)
    {
        return $this->join('auto', $table, [], $fkeyname, $from);
    }

    /**
     * 結合方法が INNER で外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function innerJoinForeign($table, $fkeyname = null, $from = null)
    {
        return $this->join('inner', $table, [], $fkeyname, $from);
    }

    /**
     * 結合方法が LEFT で外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function leftJoinForeign($table, $fkeyname = null, $from = null)
    {
        return $this->join('left', $table, [], $fkeyname, $from);
    }

    /**
     * 結合方法が RIGHT で外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function rightJoinForeign($table, $fkeyname = null, $from = null)
    {
        return $this->join('right', $table, [], $fkeyname, $from);
    }

    /**
     * 結合方法が AUTO で結合条件・外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc TableGateway::join()
     */
    public function joinForeignOn($table, $on, $fkeyname = null, $from = null)
    {
        return $this->join('auto', $table, $on, $fkeyname, $from);
    }

    /**
     * 結合方法が AUTO で結合条件・外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function autoJoinForeignOn($table, $on, $fkeyname = null, $from = null)
    {
        return $this->join('auto', $table, $on, $fkeyname, $from);
    }

    /**
     * 結合方法が INNER で結合条件・外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function innerJoinForeignOn($table, $on, $fkeyname = null, $from = null)
    {
        return $this->join('inner', $table, $on, $fkeyname, $from);
    }

    /**
     * 結合方法が LEFT で結合条件・外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function leftJoinForeignOn($table, $on, $fkeyname = null, $from = null)
    {
        return $this->join('left', $table, $on, $fkeyname, $from);
    }

    /**
     * 結合方法が RIGHT で結合条件・外部キー指定の {@uses SelectBuilder::join()}
     *
     * @inheritdoc SelectBuilder::join()
     */
    public function rightJoinForeignOn($table, $on, $fkeyname = null, $from = null)
    {
        return $this->join('right', $table, $on, $fkeyname, $from);
    }
}
