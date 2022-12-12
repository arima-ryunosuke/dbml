<?php

namespace ryunosuke\dbml\Mixin;

use ryunosuke\dbml\Database;

trait ExportTrait
{
    /**
     * レコード群を php 配列でエクスポートする（{@uses Database::exportArray()} を参照）
     *
     * @inheritdoc Database::exportArray()
     */
    public function exportArray($config = [], $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->export('array', $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having), [], $config, $config['file']);
    }

    /**
     * レコード群を CSV でエクスポートする（{@uses Database::exportCsv()} を参照）
     *
     * @inheritdoc Database::exportCsv()
     */
    public function exportCsv($config = [], $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->export('csv', $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having), [], $config, $config['file']);
    }

    /**
     * レコード群を JSON でエクスポートする（{@uses Database::exportJson()} を参照）
     *
     * @inheritdoc Database::exportJson()
     */
    public function exportJson($config = [], $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->getDatabase()->export('json', $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having), [], $config, $config['file']);
    }
}
