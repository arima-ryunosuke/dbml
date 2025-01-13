<?php
$lock = json_decode(file_get_contents(__DIR__ . '/composer.lock'), true);
$version = array_column($lock['packages'], 'version', 'name');

return [
    'directory'       => ['src'],
    'contain'         => ['ryunosuke\\dbml', 'Doctrine\\DBAL'],
    'except'          => ['ryunosuke\\dbml\\Utility'],
    'exclude'         => ['src/dbal/*'],
    'no-constant'     => true,
    'no-function'     => true,
    'no-private'      => true,
    'recursive'       => true,
    'template'        => 'markdown',
    'template-config' => [
        'extension'  => 'html',
        'source-map' => [
            '.*/Doctrine/DBAL/' => static function ($m) use ($version) {
                return "https://github.com/doctrine/dbal/blob/{$version['doctrine/dbal']}/lib/Doctrine/DBAL/";
            },
            '.*/dbml/'          => 'https://github.com/arima-ryunosuke/dbml/blob/master/src/dbml/',
            '.*'                => '',
        ],
    ],
];
