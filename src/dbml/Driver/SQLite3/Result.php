<?php

namespace ryunosuke\dbml\Driver\SQLite3;

use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;
use SQLite3Result;

final class Result extends \ryunosuke\dbal\Driver\SQLite3\Result implements ResultInterface
{
    use ResultTrait;

    public static function getMetadataFrom(SQLite3Result $result)
    {
        $metadata = [];
        for ($i = 0, $l = $result->numColumns(); $i < $l; ++$i) {
            $metadata[$i] = [
                'actualTableName'  => null,
                'actualColumnName' => null,
                'aliasTableName'   => null,
                'aliasColumnName'  => $result->columnName($i),
                'nativeType'       => (string) $result->columnType($i),
            ];
        }
        return $metadata;
    }

    public function getMetadata(): array
    {
        return self::getMetadataFrom($this->result);
    }
}
