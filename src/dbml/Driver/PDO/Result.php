<?php

namespace ryunosuke\dbml\Driver\PDO;

use Doctrine\DBAL\Types\Types;
use PDOStatement;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;

final class Result extends \ryunosuke\dbal\Driver\PDO\Result implements ResultInterface
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
            $driverName = null;
            foreach ($meta as $key => $value) {
                if (preg_match('#(.+):(.+)#', $key, $m)) {
                    $meta[$m[2]] = $value;
                    $driverName = $m[1];
                }
            }
            $metadata[$i] = [
                'actualTableName'  => $meta['table'] ?? "",
                'actualColumnName' => null,
                'aliasTableName'   => null,
                'aliasColumnName'  => $meta['name'] ?? "",
                'nativeType'       => $meta['decl_type'] ?? $meta['native_type'] ?? "",
                'doctrineType'     => self::doctrineType($meta['decl_type'] ?? $meta['native_type'] ?? "", $driverName),
            ];
        }
        return $metadata;
    }

    public static function doctrineType(int|string $nativeType, ?string $driverName = null): ?string
    {
        [$type,] = explode('(', strtolower($nativeType), 2);
        switch ($type) {
            case 'bit':
            case 'bool':
            case 'boolean':
            case 'tiny':
                return Types::BOOLEAN;

            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'short':
            case 'smallint':
            case 'long':
            case 'longlong':
            case 'integer':
            case 'int identity':
            case 'bigint identity':
                return Types::INTEGER;

            case 'float':
            case 'float4':
            case 'float8':
            case 'double':
            case 'double precision':
                return Types::FLOAT;

            case 'numeric':
            case 'newdecimal':
                return Types::DECIMAL;

            case 'date':
                return Types::DATE_MUTABLE;

            case 'time':
                return Types::TIME_MUTABLE;

            case 'datetime':
            case 'datetime2':
            case 'timestamp':
                return Types::DATETIME_MUTABLE;

            case 'timestamptz':
            case 'datetimeoffset':
                return Types::DATETIMETZ_MUTABLE;

            case 'nvarchar':
            case 'var_string':
            case 'string':
                return Types::STRING;

            case 'bytea':
            case 'varbinary':
                return Types::BINARY;

            case 'varchar':
                return match ($driverName) {
                    'sqlsrv' => Types::TEXT,
                    default  => Types::STRING,
                };

            case 'text':
            case 'clob':
                return Types::TEXT;

            case 'blob':
                return Types::BLOB;

            case 'json':
                return Types::JSON;

            default:
                return null;
        }
    }

    public function getMetadata(): array
    {
        return self::getMetadataFrom($this->statement);
    }
}
