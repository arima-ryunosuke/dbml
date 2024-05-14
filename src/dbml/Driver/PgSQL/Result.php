<?php

namespace ryunosuke\dbml\Driver\PgSQL;

use Doctrine\DBAL\Types\Types;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;
use function pg_field_name;
use function pg_field_type;
use function pg_num_fields;

final class Result extends \ryunosuke\dbal\Driver\PgSQL\Result implements ResultInterface
{
    use ResultTrait;

    public static function getMetadataFrom($statement)
    {
        // 参考: `SELECT column_name AS C FROM table_name AS T` の結果
        /**
         * pg_field_table: 'table_name',
         * pg_field_name: 'c',
         */

        $metadata = [];
        for ($i = 0, $l = pg_num_fields($statement); $i < $l; ++$i) {
            $metadata[$i] = [
                'actualTableName'  => pg_field_table($statement, $i),
                'actualColumnName' => null,
                'aliasTableName'   => null,
                'aliasColumnName'  => pg_field_name($statement, $i),
                'nativeType'       => pg_field_type($statement, $i),
                'doctrineType'     => self::doctrineType(pg_field_type($statement, $i)),
            ];
        }
        return $metadata;
    }

    public static function doctrineType(int|string $nativeType): ?string
    {
        switch ($nativeType) {
            case 'bool':
                return Types::BOOLEAN;

            case 'int2':
            case 'int4':
            case 'int8':
                return Types::INTEGER;

            case 'float4':
            case 'float8':
                return Types::FLOAT;

            case 'numeric':
                return Types::DECIMAL;

            case 'date':
                return Types::DATE_MUTABLE;

            case 'time':
                return Types::TIME_MUTABLE;

            case 'timestamp':
                return Types::DATETIME_MUTABLE;

            case 'timestamptz':
                return Types::DATETIMETZ_MUTABLE;

            case 'varchar':
                return Types::STRING;

            case 'bytea':
                return Types::BINARY;

            case 'text':
                return Types::TEXT;

            case 'json':
                return Types::JSON;

            default:
                return null;
        }
    }

    public function getMetadata(): array
    {
        return self::getMetadataFrom($this->result);
    }
}
