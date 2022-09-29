<?php /** @noinspection PhpDeprecationInspection */

namespace ryunosuke\dbml\Transaction;

/**
 * @see Logger
 * @deprecated doctrine 側で SQLLogger が非推奨になったのでこちらも非推奨化
 */
class Logger extends \ryunosuke\dbml\Logging\Logger implements \Doctrine\DBAL\Logging\SQLLogger
{
    /** @var array ログ用の実行中クエリ */
    private $lastquery;

    public static function getDefaultOptions()
    {
        $parent = parent::getDefaultOptions();
        $parent['metadata'] = [
            'time'    => $parent['metadata']['time'],
            'elapsed' => function ($metadata) {
                return number_format(microtime(true) - $metadata['microtime'], 3);
            },
            'traces'  => $parent['metadata']['traces'],
        ];
        return $parent;
    }

    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->lastquery = [
            $sql,
            $params ?? [],
            $types ?? [],
            [
                'microtime' => microtime(true),
            ],
        ];
    }

    public function stopQuery()
    {
        [$sql, $params, $types, $initdata] = $this->lastquery;
        $sql = trim($sql, '"'); // DBAL\Connection がクォートするので外す
        $this->log(\Psr\Log\LogLevel::DEBUG, '', [
            'sql'      => $sql,
            'params'   => $params,
            'types'    => $types,
            'metadata' => array_map(fn($v) => $v($initdata), $this->getUnsafeOption('metadata')),
        ]);
    }
}
