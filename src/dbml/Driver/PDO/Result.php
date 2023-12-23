<?php

namespace ryunosuke\dbml\Driver\PDO;

use Doctrine\DBAL\Driver\PDO\DbalResult;
use PDOStatement;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;

require_once __DIR__ . '/../../../dbal/Driver/PDO/Result.php';

final class Result extends DbalResult implements ResultInterface
{
    use ResultTrait;

    public static function getMetadataFrom(PDOStatement $statement)
    {
        // 参考: `SELECT column_name AS C FROM table_name AS T` の結果
        /** sqlite
         * native_type: "string",
         * sqlite:decl_type: "VARCHAR(32)",
         * table: "table_name",
         * flags: [],
         * name: "C",
         * len: -1,
         * precision: 0,
         * pdo_type: 2,
         */
        /** mysql
         * native_type: "VAR_STRING",
         * pdo_type: 2,
         * flags: ["not_null"],
         * table: "T",
         * name: "C",
         * len: 128,
         * precision: 0,
         */
        /** pgsql
         * pgsql:oid: 1043,
         * pgsql:table_oid: 827281,
         * table: "table_name",
         * native_type: "varchar",
         * name: "c",
         * len: -1,
         * precision: 36,
         * pdo_type: 2,
         */
        /** sqlsrv
         * flags: 0,
         * sqlsrv:decl_type: "nvarchar",
         * native_type: "string",
         * table: "",
         * pdo_type: 2,
         * name: "C",
         * len: 32,
         * precision: 0,
         */

        $metadata = [];
        for ($i = 0, $l = $statement->columnCount(); $i < $l; $i++) {
            $meta = $statement->getColumnMeta($i);
            $metadata[$i] = [
                'actualTableName'  => $meta['table'] ?? "",
                'actualColumnName' => null,
                'aliasTableName'   => null,
                'aliasColumnName'  => $meta['name'] ?? "",
                'nativeType'       => $meta['native_type'] ?? "",
            ];
        }
        return $metadata;
    }

    public function getMetadata(): array
    {
        return self::getMetadataFrom($this->statement);
    }
}
