<?php

namespace ryunosuke\dbml\Driver\Mysqli;

use mysqli_result;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;

final class Result extends \ryunosuke\dbal\Driver\Mysqli\Result implements ResultInterface
{
    use ResultTrait;

    public static function getMetadataFrom(mysqli_result $result)
    {
        $metadata = [];
        foreach ($result->fetch_fields() as $n => $field) {
            $metadata[$n] = [
                'actualTableName'  => $field->orgtable,
                'actualColumnName' => $field->orgname,
                'aliasTableName'   => $field->table,
                'aliasColumnName'  => $field->name,
                'nativeType'       => $field->type,
            ];
        }
        return $metadata;
    }

    /** @return string|int|float|bool|null */
    public static function mapType(int $mysqlType, ?string $value)
    {
        switch ($mysqlType) {
            case MYSQLI_TYPE_CHAR: // CHAR same as TINY
            case MYSQLI_TYPE_BIT:
            case MYSQLI_TYPE_YEAR:

            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_INT24:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_LONGLONG:
                return (int) $value;

            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
                return (float) $value;

            case MYSQLI_TYPE_DECIMAL:
            case MYSQLI_TYPE_NEWDECIMAL:

            case MYSQLI_TYPE_DATE:
            case MYSQLI_TYPE_TIME:
            case MYSQLI_TYPE_INTERVAL:
            case MYSQLI_TYPE_TIMESTAMP:
            case MYSQLI_TYPE_DATETIME:
            case MYSQLI_TYPE_NEWDATE:

            case MYSQLI_TYPE_STRING:
            case MYSQLI_TYPE_VAR_STRING:
            case MYSQLI_TYPE_TINY_BLOB:
            case MYSQLI_TYPE_BLOB:
            case MYSQLI_TYPE_MEDIUM_BLOB:
            case MYSQLI_TYPE_LONG_BLOB:

            case MYSQLI_TYPE_ENUM:
            case MYSQLI_TYPE_SET:
            case MYSQLI_TYPE_JSON:
            case MYSQLI_TYPE_GEOMETRY:
            default:
                return (string) $value;
            case MYSQLI_TYPE_NULL:
                return $value;
        }
    }

    public function storeResult()
    {
        $this->statement->store_result();
    }

    public function getMetadata(): array
    {
        try {
            $result = $this->statement->result_metadata();
            return self::getMetadataFrom($result);
        }
        finally {
            $result->free();
        }
    }
}
