<?php

namespace ryunosuke\dbml\Driver\SQLite3;

use Doctrine\DBAL\Driver\SQLite3\DbalResult;
use ryunosuke\dbml\Driver\ResultInterface;
use ryunosuke\dbml\Driver\ResultTrait;
use SQLite3Result;

require_once __DIR__ . '/../../../dbal/Driver/SQLite3/Result.php';

final class Result extends DbalResult implements ResultInterface
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
