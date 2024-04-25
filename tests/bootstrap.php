<?php

use function ryunosuke\dbml\class_aliases;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/ryunosuke/phpunit-extension/inc/bootstrap.php';

require_once __DIR__ . '/classess.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class_aliases([
    \Doctrine\DBAL\Driver\SQLite3\Result::class => \ryunosuke\dbml\Driver\SQLite3\Result::class,
    \Doctrine\DBAL\Driver\Mysqli\Result::class  => \ryunosuke\dbml\Driver\Mysqli\Result::class,
    \Doctrine\DBAL\Driver\PgSQL\Result::class   => \ryunosuke\dbml\Driver\PgSQL\Result::class,
    \Doctrine\DBAL\Driver\PDO\Result::class     => \ryunosuke\dbml\Driver\PDO\Result::class,
]);

function var_pretty(...$args)
{
    foreach ($args as $n => $arg) {
        \ryunosuke\dbml\var_pretty($arg, [
            'return'    => false,
            'trace'     => $n === 0,
            'maxcolumn' => 110,
        ]);
    }
}

(function () {
    if (DIRECTORY_SEPARATOR === '\\') {
        $tmpdir = $_SERVER['TMP'] ?? $_SERVER['TEMP'] ?? null;
        if ($tmpdir) {
            @mkdir("$tmpdir\\dbml", 0777, true);
            putenv("TMP=$tmpdir\\dbml");
        }
    }
    else {
        $tmpdir = $_SERVER['TMPDIR'] ?? '/tmp';
        if ($tmpdir) {
            @mkdir("$tmpdir/dbml", 0777, true);
            putenv("TMPDIR=$tmpdir/dbml");
        }
    }
    ryunosuke\dbml\rm_rf("$tmpdir/dbml", false);

    if (DIRECTORY_SEPARATOR === '\\') {
        setlocale(LC_CTYPE, 'C');
    }
})();
