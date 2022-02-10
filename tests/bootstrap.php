<?php

error_reporting(~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/classess.php';

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
