<?php

namespace ryunosuke\dbml\Driver\PgSQL;

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
            ];
        }
        return $metadata;
    }

    public function getMetadata(): array
    {
        return self::getMetadataFrom($this->result);
    }
}
