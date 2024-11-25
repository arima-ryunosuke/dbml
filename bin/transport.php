<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function ryunosuke\dbml\file_set_contents;
use function ryunosuke\dbml\str_diff;
use function ryunosuke\dbml\str_patch;

if (interface_exists(\Doctrine\DBAL\Exception::class)) {
    define('DBAL_VERSION', 'v4');
}
else {
    define('DBAL_VERSION', 'v3');
}

function patch($targets)
{
    $v = DBAL_VERSION;
    foreach ($targets as $target) {
        fwrite(STDOUT, "patch vendor/doctrine/dbal/src/$target.php src/dbal/$target.$v.patch > src/dbal/$target.$v.php\n");

        $src = __DIR__ . "/../vendor/doctrine/dbal/src/$target.php";
        $dst = __DIR__ . "/../src/dbal/$target.$v.php";
        $patch = __DIR__ . "/../src/dbal/$target.$v.patch";

        $contents = file_get_contents($src);
        if (file_exists($patch)) {
            $contents = str_patch($contents, file_get_contents($patch));
        }
        file_set_contents($dst, $contents);
    }
}

function diff($targets)
{
    $v = DBAL_VERSION;
    foreach ($targets as $target) {
        fwrite(STDOUT, "diff vendor/doctrine/dbal/src/$target.php src/dbal/$target.$v.php > src/dbal/$target.$v.patch\n");

        $src = __DIR__ . "/../vendor/doctrine/dbal/src/$target.php";
        $dst = __DIR__ . "/../src/dbal/$target.$v.php";
        $patch = __DIR__ . "/../src/dbal/$target.$v.patch";

        $diff = str_diff(file_get_contents($src), file_get_contents($dst), ['stringify' => 'unified=3']);
        file_set_contents($patch, $diff);
    }
}

$subcommand = $argv[1] ?? 'undefined';
$subcommand([
    'Driver/PDO/Result',
    'Driver/PgSQL/Result',
    'Driver/Mysqli/Result',
    'Driver/SQLite3/Result',
    'Driver/SQLSrv/Result',
]);
