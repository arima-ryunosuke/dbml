<?php

namespace ryunosuke\dbml\Driver\SQLSrv;

use Doctrine\DBAL\Types\Types;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;

final class Result extends \ryunosuke\dbal\Driver\SQLSrv\Result implements ResultInterface
{
    use ResultTrait;

    public static function getMetadataFrom($statement)
    {
        $metadata = [];
        foreach (sqlsrv_field_metadata($statement) as $i => $meta) {
            $metadata[$i] = [
                'actualTableName'  => null,
                'actualColumnName' => null,
                'aliasTableName'   => null,
                'aliasColumnName'  => $meta['Name'],
                'nativeType'       => $meta['Type'],
                'doctrineType'     => self::doctrineType($meta['Type']),
            ];
        }
        return $metadata;
    }

    public static function doctrineType(int|string $nativeType): ?string
    {
        // @todo DATE 系定数が毎回変わってる…？

        switch ($nativeType) {
            case SQLSRV_SQLTYPE_BIT:
                return Types::BOOLEAN;

            case SQLSRV_SQLTYPE_TINYINT:
            case SQLSRV_SQLTYPE_SMALLINT:
            case SQLSRV_SQLTYPE_INT:
            case SQLSRV_SQLTYPE_BIGINT:
                return Types::INTEGER;

            case SQLSRV_SQLTYPE_REAL:
            case SQLSRV_SQLTYPE_FLOAT:
                return Types::FLOAT;

            case SQLSRV_SQLTYPE_DECIMAL:
            case SQLSRV_SQLTYPE_NUMERIC:
                return Types::DECIMAL;

            case 91:
                return Types::DATE_MUTABLE;

            case -154:
                return Types::TIME_MUTABLE;

            case 93:
            case SQLSRV_SQLTYPE_DATETIME2:
                return Types::DATETIME_MUTABLE;

            case -155:
            case SQLSRV_SQLTYPE_DATETIMEOFFSET:
                return Types::DATETIMETZ_MUTABLE;

            case SQLSRV_SQLTYPE_NVARCHAR:
                return Types::STRING;

            case SQLSRV_SQLTYPE_VARBINARY:
                return Types::BINARY;

            case SQLSRV_SQLTYPE_VARCHAR:
                return Types::TEXT;

            default:
                return null;
        }
    }

    public function getMetadata(): array
    {
        return self::getMetadataFrom($this->statement);
    }
}
