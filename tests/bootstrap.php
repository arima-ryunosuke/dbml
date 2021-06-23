<?php

error_reporting(-1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/annotation.php';
require_once __DIR__ . '/classess.php';

function var_pretty($v, ...$args)
{
    return \ryunosuke\dbml\var_pretty($v, [
        'return' => false,
        'trace'  => true,
    ]);
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

    if (DIRECTORY_SEPARATOR === '\\') {
        setlocale(LC_CTYPE, 'C');
    }
})();
