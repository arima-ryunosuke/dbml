<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function ryunosuke\dbml\file_set_contents;
use function ryunosuke\dbml\str_diff;
use function ryunosuke\dbml\str_patch;

function patch($targets)
{
    foreach ($targets as $target) {
        fwrite(STDOUT, "patch vendor/doctrine/dbal/src/$target.php src/patch/$target.patch > src/dbal/$target.php\n");

        $src = __DIR__ . "/../vendor/doctrine/dbal/src/$target.php";
        $dst = __DIR__ . "/../src/dbal/$target.php";
        $patch = __DIR__ . "/../src/patch/$target.patch";

        $contents = file_get_contents($src);
        if (file_exists($patch)) {
            $contents = str_patch($contents, file_get_contents($patch));
        }
        file_set_contents($dst, $contents);
    }
}

function diff($targets)
{
    foreach ($targets as $target) {
        fwrite(STDOUT, "diff vendor/doctrine/dbal/src/$target.php src/dbal/$target.php > src/patch/$target.patch\n");

        $src = __DIR__ . "/../vendor/doctrine/dbal/src/$target.php";
        $dst = __DIR__ . "/../src/dbal/$target.php";
        $patch = __DIR__ . "/../src/patch/$target.patch";

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
]);
