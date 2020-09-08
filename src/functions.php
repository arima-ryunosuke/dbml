<?php

# Don't touch this code. This is auto generated.

namespace ryunosuke\dbml;

# constants
if (!defined("ryunosuke\\dbml\\KEYWORDS")) {
    /** SQL キーワード（全 RDBMS ごちゃまぜ） */
    define("ryunosuke\\dbml\\KEYWORDS", [
        ""  => "",
        0   => "ACCESSIBLE",
        1   => "ACTION",
        2   => "ADD",
        3   => "AFTER",
        4   => "AGAINST",
        5   => "AGGREGATE",
        6   => "ALGORITHM",
        7   => "ALL",
        8   => "ALTER",
        9   => "ALTER TABLE",
        10  => "ANALYSE",
        11  => "ANALYZE",
        12  => "AND",
        13  => "AS",
        14  => "ASC",
        15  => "AUTOCOMMIT",
        16  => "AUTO_INCREMENT",
        17  => "BACKUP",
        18  => "BEGIN",
        19  => "BETWEEN",
        20  => "BINLOG",
        21  => "BOTH",
        22  => "CASCADE",
        23  => "CASE",
        24  => "CHANGE",
        25  => "CHANGED",
        26  => "CHARACTER SET",
        27  => "CHARSET",
        28  => "CHECK",
        29  => "CHECKSUM",
        30  => "COLLATE",
        31  => "COLLATION",
        32  => "COLUMN",
        33  => "COLUMNS",
        34  => "COMMENT",
        35  => "COMMIT",
        36  => "COMMITTED",
        37  => "COMPRESSED",
        38  => "CONCURRENT",
        39  => "CONSTRAINT",
        40  => "CONTAINS",
        41  => "CONVERT",
        42  => "CREATE",
        43  => "CROSS",
        44  => "CURRENT_TIMESTAMP",
        45  => "DATABASE",
        46  => "DATABASES",
        47  => "DAY",
        48  => "DAY_HOUR",
        49  => "DAY_MINUTE",
        50  => "DAY_SECOND",
        51  => "DEFAULT",
        52  => "DEFINER",
        53  => "DELAYED",
        54  => "DELETE",
        55  => "DELETE FROM",
        56  => "DESC",
        57  => "DESCRIBE",
        58  => "DETERMINISTIC",
        59  => "DISTINCT",
        60  => "DISTINCTROW",
        61  => "DIV",
        62  => "DO",
        63  => "DROP",
        64  => "DUMPFILE",
        65  => "DUPLICATE",
        66  => "DYNAMIC",
        67  => "ELSE",
        68  => "ENCLOSED",
        69  => "END",
        70  => "ENGINE",
        71  => "ENGINES",
        72  => "ENGINE_TYPE",
        73  => "ESCAPE",
        74  => "ESCAPED",
        75  => "EVENTS",
        76  => "EXCEPT",
        77  => "EXECUTE",
        78  => "EXISTS",
        79  => "EXPLAIN",
        80  => "EXTENDED",
        81  => "FAST",
        82  => "FIELDS",
        83  => "FILE",
        84  => "FIRST",
        85  => "FIXED",
        86  => "FLUSH",
        87  => "FOR",
        88  => "FORCE",
        89  => "FOREIGN",
        90  => "FROM",
        91  => "FULL",
        92  => "FULLTEXT",
        93  => "FUNCTION",
        94  => "GLOBAL",
        95  => "GRANT",
        96  => "GRANTS",
        97  => "GROUP",
        98  => "GROUP_CONCAT",
        99  => "HAVING",
        100 => "HEAP",
        101 => "HIGH_PRIORITY",
        102 => "HOSTS",
        103 => "HOUR",
        104 => "HOUR_MINUTE",
        105 => "HOUR_SECOND",
        106 => "IDENTIFIED",
        107 => "IF",
        108 => "IFNULL",
        109 => "IGNORE",
        110 => "IN",
        111 => "INDEX",
        112 => "INDEXES",
        113 => "INFILE",
        114 => "INNER",
        115 => "INSERT",
        116 => "INSERT_ID",
        117 => "INSERT_METHOD",
        118 => "INTERSECT",
        119 => "INTERVAL",
        120 => "INTO",
        121 => "INVOKER",
        122 => "IS",
        123 => "ISOLATION",
        124 => "JOIN",
        125 => "JSON_ARRAY",
        126 => "JSON_ARRAY_APPEND",
        127 => "JSON_ARRAY_INSERT",
        128 => "JSON_CONTAINS",
        129 => "JSON_CONTAINS_PATH",
        130 => "JSON_DEPTH",
        131 => "JSON_EXTRACT",
        132 => "JSON_INSERT",
        133 => "JSON_KEYS",
        134 => "JSON_LENGTH",
        135 => "JSON_MERGE_PATCH",
        136 => "JSON_MERGE_PRESERVE",
        137 => "JSON_OBJECT",
        138 => "JSON_PRETTY",
        139 => "JSON_QUOTE",
        140 => "JSON_REMOVE",
        141 => "JSON_REPLACE",
        142 => "JSON_SEARCH",
        143 => "JSON_SET",
        144 => "JSON_STORAGE_SIZE",
        145 => "JSON_TYPE",
        146 => "JSON_UNQUOTE",
        147 => "JSON_VALID",
        148 => "KEY",
        149 => "KEYS",
        150 => "KILL",
        151 => "LAST_INSERT_ID",
        152 => "LEADING",
        153 => "LEFT",
        154 => "LEVEL",
        155 => "LIKE",
        156 => "LIMIT",
        157 => "LINEAR",
        158 => "LINES",
        159 => "LOAD",
        160 => "LOCAL",
        161 => "LOCK",
        162 => "LOCKS",
        163 => "LOGS",
        164 => "LOW_PRIORITY",
        165 => "MARIA",
        166 => "MASTER",
        167 => "MASTER_CONNECT_RETRY",
        168 => "MASTER_HOST",
        169 => "MASTER_LOG_FILE",
        170 => "MATCH",
        171 => "MAX_CONNECTIONS_PER_HOUR",
        172 => "MAX_QUERIES_PER_HOUR",
        173 => "MAX_ROWS",
        174 => "MAX_UPDATES_PER_HOUR",
        175 => "MAX_USER_CONNECTIONS",
        176 => "MEDIUM",
        177 => "MERGE",
        178 => "MINUTE",
        179 => "MINUTE_SECOND",
        180 => "MIN_ROWS",
        181 => "MODE",
        182 => "MODIFY",
        183 => "MONTH",
        184 => "MRG_MYISAM",
        185 => "MYISAM",
        186 => "NAMES",
        187 => "NATURAL",
        188 => "NOT",
        189 => "NOW()",
        190 => "NULL",
        191 => "OFFSET",
        192 => "ON",
        193 => "ON DELETE",
        194 => "ON UPDATE",
        195 => "OPEN",
        196 => "OPTIMIZE",
        197 => "OPTION",
        198 => "OPTIONALLY",
        199 => "OR",
        200 => "ORDER",
        201 => "BY",
        202 => "OUTER",
        203 => "OUTFILE",
        204 => "PACK_KEYS",
        205 => "PAGE",
        206 => "PARTIAL",
        207 => "PARTITION",
        208 => "PARTITIONS",
        209 => "PASSWORD",
        210 => "PRIMARY",
        211 => "PRIVILEGES",
        212 => "PROCEDURE",
        213 => "PROCESS",
        214 => "PROCESSLIST",
        215 => "PURGE",
        216 => "QUICK",
        217 => "RAID0",
        218 => "RAID_CHUNKS",
        219 => "RAID_CHUNKSIZE",
        220 => "RAID_TYPE",
        221 => "RANGE",
        222 => "READ",
        223 => "READ_ONLY",
        224 => "READ_WRITE",
        225 => "REFERENCES",
        226 => "REGEXP",
        227 => "RELOAD",
        228 => "RENAME",
        229 => "REPAIR",
        230 => "REPEATABLE",
        231 => "REPLACE",
        232 => "REPLICATION",
        233 => "RESET",
        234 => "RESTORE",
        235 => "RESTRICT",
        236 => "RETURN",
        237 => "RETURNS",
        238 => "REVOKE",
        239 => "RIGHT",
        240 => "RLIKE",
        241 => "ROLLBACK",
        242 => "ROLLUP",
        243 => "ROW",
        244 => "ROWS",
        245 => "ROW_FORMAT",
        246 => "SECOND",
        247 => "SECURITY",
        248 => "SELECT",
        249 => "SEPARATOR",
        250 => "SERIALIZABLE",
        251 => "SESSION",
        252 => "SET",
        253 => "SHARE",
        254 => "SHOW",
        255 => "SHUTDOWN",
        256 => "SLAVE",
        257 => "SONAME",
        258 => "SOUNDS",
        259 => "SQL",
        260 => "SQL_AUTO_IS_NULL",
        261 => "SQL_BIG_RESULT",
        262 => "SQL_BIG_SELECTS",
        263 => "SQL_BIG_TABLES",
        264 => "SQL_BUFFER_RESULT",
        265 => "SQL_CACHE",
        266 => "SQL_CALC_FOUND_ROWS",
        267 => "SQL_LOG_BIN",
        268 => "SQL_LOG_OFF",
        269 => "SQL_LOG_UPDATE",
        270 => "SQL_LOW_PRIORITY_UPDATES",
        271 => "SQL_MAX_JOIN_SIZE",
        272 => "SQL_NO_CACHE",
        273 => "SQL_QUOTE_SHOW_CREATE",
        274 => "SQL_SAFE_UPDATES",
        275 => "SQL_SELECT_LIMIT",
        276 => "SQL_SLAVE_SKIP_COUNTER",
        277 => "SQL_SMALL_RESULT",
        278 => "SQL_WARNINGS",
        279 => "START",
        280 => "STARTING",
        281 => "STATUS",
        282 => "STOP",
        283 => "STORAGE",
        284 => "STRAIGHT_JOIN",
        285 => "STRING",
        286 => "STRIPED",
        287 => "SUPER",
        288 => "TABLE",
        289 => "TABLES",
        290 => "TEMPORARY",
        291 => "TERMINATED",
        292 => "THEN",
        293 => "TO",
        294 => "TRAILING",
        295 => "TRANSACTIONAL",
        296 => "TRUE",
        297 => "TRUNCATE",
        298 => "TYPE",
        299 => "TYPES",
        300 => "UNCOMMITTED",
        301 => "UNION",
        302 => "UNION ALL",
        303 => "UNIQUE",
        304 => "UNLOCK",
        305 => "UNSIGNED",
        306 => "UPDATE",
        307 => "USAGE",
        308 => "USE",
        309 => "USING",
        310 => "VALUES",
        311 => "VARIABLES",
        312 => "VIEW",
        313 => "WHEN",
        314 => "WHERE",
        315 => "WITH",
        316 => "WORK",
        317 => "WRITE",
        318 => "XOR",
        319 => "YEAR_MONTH",
        320 => "ABS",
        321 => "ACOS",
        322 => "ADDDATE",
        323 => "ADDTIME",
        324 => "AES_DECRYPT",
        325 => "AES_ENCRYPT",
        326 => "AREA",
        327 => "ASBINARY",
        328 => "ASCII",
        329 => "ASIN",
        330 => "ASTEXT",
        331 => "ATAN",
        332 => "ATAN2",
        333 => "AVG",
        334 => "BDMPOLYFROMTEXT",
        335 => "BDMPOLYFROMWKB",
        336 => "BDPOLYFROMTEXT",
        337 => "BDPOLYFROMWKB",
        338 => "BENCHMARK",
        339 => "BIN",
        340 => "BIT_AND",
        341 => "BIT_COUNT",
        342 => "BIT_LENGTH",
        343 => "BIT_OR",
        344 => "BIT_XOR",
        345 => "BOUNDARY",
        346 => "BUFFER",
        347 => "CAST",
        348 => "CEIL",
        349 => "CEILING",
        350 => "CENTROID",
        351 => "CHAR",
        352 => "CHARACTER_LENGTH",
        353 => "CHARSET",
        354 => "CHAR_LENGTH",
        355 => "COALESCE",
        356 => "COERCIBILITY",
        357 => "COLLATION",
        358 => "COMPRESS",
        359 => "CONCAT",
        360 => "CONCAT_WS",
        361 => "CONNECTION_ID",
        362 => "CONTAINS",
        363 => "CONV",
        364 => "CONVERT",
        365 => "CONVERT_TZ",
        366 => "CONVEXHULL",
        367 => "COS",
        368 => "COT",
        369 => "COUNT",
        370 => "CRC32",
        371 => "CROSSES",
        372 => "CURDATE",
        373 => "CURRENT_DATE",
        374 => "CURRENT_TIME",
        375 => "CURRENT_TIMESTAMP",
        376 => "CURRENT_USER",
        377 => "CURTIME",
        378 => "DATABASE",
        379 => "DATE",
        380 => "DATEDIFF",
        381 => "DATE_ADD",
        382 => "DATE_DIFF",
        383 => "DATE_FORMAT",
        384 => "DATE_SUB",
        385 => "DAY",
        386 => "DAYNAME",
        387 => "DAYOFMONTH",
        388 => "DAYOFWEEK",
        389 => "DAYOFYEAR",
        390 => "DECODE",
        391 => "DEFAULT",
        392 => "DEGREES",
        393 => "DES_DECRYPT",
        394 => "DES_ENCRYPT",
        395 => "DIFFERENCE",
        396 => "DIMENSION",
        397 => "DISJOINT",
        398 => "DISTANCE",
        399 => "ELT",
        400 => "ENCODE",
        401 => "ENCRYPT",
        402 => "ENDPOINT",
        403 => "ENVELOPE",
        404 => "EQUALS",
        405 => "EXP",
        406 => "EXPORT_SET",
        407 => "EXTERIORRING",
        408 => "EXTRACT",
        409 => "EXTRACTVALUE",
        410 => "FIELD",
        411 => "FIND_IN_SET",
        412 => "FLOOR",
        413 => "FORMAT",
        414 => "FOUND_ROWS",
        415 => "FROM_DAYS",
        416 => "FROM_UNIXTIME",
        417 => "GEOMCOLLFROMTEXT",
        418 => "GEOMCOLLFROMWKB",
        419 => "GEOMETRYCOLLECTION",
        420 => "GEOMETRYCOLLECTIONFROMTEXT",
        421 => "GEOMETRYCOLLECTIONFROMWKB",
        422 => "GEOMETRYFROMTEXT",
        423 => "GEOMETRYFROMWKB",
        424 => "GEOMETRYN",
        425 => "GEOMETRYTYPE",
        426 => "GEOMFROMTEXT",
        427 => "GEOMFROMWKB",
        428 => "GET_FORMAT",
        429 => "GET_LOCK",
        430 => "GLENGTH",
        431 => "GREATEST",
        432 => "GROUP_CONCAT",
        433 => "GROUP_UNIQUE_USERS",
        434 => "HEX",
        435 => "HOUR",
        436 => "IF",
        437 => "IFNULL",
        438 => "INET_ATON",
        439 => "INET_NTOA",
        440 => "INSERT",
        441 => "INSTR",
        442 => "INTERIORRINGN",
        443 => "INTERSECTION",
        444 => "INTERSECTS",
        445 => "INTERVAL",
        446 => "ISCLOSED",
        447 => "ISEMPTY",
        448 => "ISNULL",
        449 => "ISRING",
        450 => "ISSIMPLE",
        451 => "IS_FREE_LOCK",
        452 => "IS_USED_LOCK",
        453 => "LAST_DAY",
        454 => "LAST_INSERT_ID",
        455 => "LCASE",
        456 => "LEAST",
        457 => "LEFT",
        458 => "LENGTH",
        459 => "LINEFROMTEXT",
        460 => "LINEFROMWKB",
        461 => "LINESTRING",
        462 => "LINESTRINGFROMTEXT",
        463 => "LINESTRINGFROMWKB",
        464 => "LN",
        465 => "LOAD_FILE",
        466 => "LOCALTIME",
        467 => "LOCALTIMESTAMP",
        468 => "LOCATE",
        469 => "LOG",
        470 => "LOG10",
        471 => "LOG2",
        472 => "LOWER",
        473 => "LPAD",
        474 => "LTRIM",
        475 => "MAKEDATE",
        476 => "MAKETIME",
        477 => "MAKE_SET",
        478 => "MASTER_POS_WAIT",
        479 => "MAX",
        480 => "MBRCONTAINS",
        481 => "MBRDISJOINT",
        482 => "MBREQUAL",
        483 => "MBRINTERSECTS",
        484 => "MBROVERLAPS",
        485 => "MBRTOUCHES",
        486 => "MBRWITHIN",
        487 => "MD5",
        488 => "MICROSECOND",
        489 => "MID",
        490 => "MIN",
        491 => "MINUTE",
        492 => "MLINEFROMTEXT",
        493 => "MLINEFROMWKB",
        494 => "MOD",
        495 => "MONTH",
        496 => "MONTHNAME",
        497 => "MPOINTFROMTEXT",
        498 => "MPOINTFROMWKB",
        499 => "MPOLYFROMTEXT",
        500 => "MPOLYFROMWKB",
        501 => "MULTILINESTRING",
        502 => "MULTILINESTRINGFROMTEXT",
        503 => "MULTILINESTRINGFROMWKB",
        504 => "MULTIPOINT",
        505 => "MULTIPOINTFROMTEXT",
        506 => "MULTIPOINTFROMWKB",
        507 => "MULTIPOLYGON",
        508 => "MULTIPOLYGONFROMTEXT",
        509 => "MULTIPOLYGONFROMWKB",
        510 => "NAME_CONST",
        511 => "NULLIF",
        512 => "NUMGEOMETRIES",
        513 => "NUMINTERIORRINGS",
        514 => "NUMPOINTS",
        515 => "OCT",
        516 => "OCTET_LENGTH",
        517 => "OLD_PASSWORD",
        518 => "ORD",
        519 => "OVERLAPS",
        520 => "PASSWORD",
        521 => "PERIOD_ADD",
        522 => "PERIOD_DIFF",
        523 => "PI",
        524 => "POINT",
        525 => "POINTFROMTEXT",
        526 => "POINTFROMWKB",
        527 => "POINTN",
        528 => "POINTONSURFACE",
        529 => "POLYFROMTEXT",
        530 => "POLYFROMWKB",
        531 => "POLYGON",
        532 => "POLYGONFROMTEXT",
        533 => "POLYGONFROMWKB",
        534 => "POSITION",
        535 => "POW",
        536 => "POWER",
        537 => "QUARTER",
        538 => "QUOTE",
        539 => "RADIANS",
        540 => "RAND",
        541 => "RELATED",
        542 => "RELEASE_LOCK",
        543 => "REPEAT",
        544 => "REPLACE",
        545 => "REVERSE",
        546 => "RIGHT",
        547 => "ROUND",
        548 => "ROW_COUNT",
        549 => "RPAD",
        550 => "RTRIM",
        551 => "SCHEMA",
        552 => "SECOND",
        553 => "SEC_TO_TIME",
        554 => "SESSION_USER",
        555 => "SHA",
        556 => "SHA1",
        557 => "SIGN",
        558 => "SIN",
        559 => "SLEEP",
        560 => "SOUNDEX",
        561 => "SPACE",
        562 => "SQRT",
        563 => "SRID",
        564 => "STARTPOINT",
        565 => "STD",
        566 => "STDDEV",
        567 => "STDDEV_POP",
        568 => "STDDEV_SAMP",
        569 => "STRCMP",
        570 => "STR_TO_DATE",
        571 => "SUBDATE",
        572 => "SUBSTR",
        573 => "SUBSTRING",
        574 => "SUBSTRING_INDEX",
        575 => "SUBTIME",
        576 => "SUM",
        577 => "SYMDIFFERENCE",
        578 => "SYSDATE",
        579 => "SYSTEM_USER",
        580 => "TAN",
        581 => "TIME",
        582 => "TIMEDIFF",
        583 => "TIMESTAMP",
        584 => "TIMESTAMPADD",
        585 => "TIMESTAMPDIFF",
        586 => "TIME_FORMAT",
        587 => "TIME_TO_SEC",
        588 => "TOUCHES",
        589 => "TO_DAYS",
        590 => "TRIM",
        591 => "TRUNCATE",
        592 => "UCASE",
        593 => "UNCOMPRESS",
        594 => "UNCOMPRESSED_LENGTH",
        595 => "UNHEX",
        596 => "UNIQUE_USERS",
        597 => "UNIX_TIMESTAMP",
        598 => "UPDATEXML",
        599 => "UPPER",
        600 => "USER",
        601 => "UTC_DATE",
        602 => "UTC_TIME",
        603 => "UTC_TIMESTAMP",
        604 => "UUID",
        605 => "VARIANCE",
        606 => "VAR_POP",
        607 => "VAR_SAMP",
        608 => "VERSION",
        609 => "WEEK",
        610 => "WEEKDAY",
        611 => "WEEKOFYEAR",
        612 => "WITHIN",
        613 => "X",
        614 => "Y",
        615 => "YEAR",
        616 => "YEARWEEK",
    ]);
}


# functions
if (!isset($excluded_functions["arrayize"]) && (!function_exists("ryunosuke\\dbml\\arrayize") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\arrayize"))->isInternal()))) {
    /**
     * 引数の配列を生成する。
     *
     * 配列以外を渡すと配列化されて追加される。
     * 連想配列は未対応。あくまで普通の配列化のみ。
     * iterable や Traversable は考慮せずあくまで「配列」としてチェックする。
     *
     * Example:
     * ```php
     * that(arrayize(1, 2, 3))->isSame([1, 2, 3]);
     * that(arrayize([1], [2], [3]))->isSame([1, 2, 3]);
     * $object = new \stdClass();
     * that(arrayize($object, false, [1, 2, 3]))->isSame([$object, false, 1, 2, 3]);
     * ```
     *
     * @param mixed $variadic 生成する要素（可変引数）
     * @return array 引数を配列化したもの
     */
    function arrayize(...$variadic)
    {
        $result = [];
        foreach ($variadic as $arg) {
            if (!is_array($arg)) {
                $result[] = $arg;
            }
            elseif (!is_hasharray($arg)) {
                $result = array_merge($result, $arg);
            }
            else {
                $result += $arg;
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\arrayize") && !defined("ryunosuke\\dbml\\arrayize")) {
    define("ryunosuke\\dbml\\arrayize", "ryunosuke\\dbml\\arrayize");
}

if (!isset($excluded_functions["is_hasharray"]) && (!function_exists("ryunosuke\\dbml\\is_hasharray") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\is_hasharray"))->isInternal()))) {
    /**
     * 配列が連想配列か調べる
     *
     * 空の配列は普通の配列とみなす。
     *
     * Example:
     * ```php
     * that(is_hasharray([]))->isFalse();
     * that(is_hasharray([1, 2, 3]))->isFalse();
     * that(is_hasharray(['x' => 'X']))->isTrue();
     * ```
     *
     * @param array $array 調べる配列
     * @return bool 連想配列なら true
     */
    function is_hasharray(array $array)
    {
        $i = 0;
        foreach ($array as $k => $dummy) {
            if ($k !== $i++) {
                return true;
            }
        }
        return false;
    }
}
if (function_exists("ryunosuke\\dbml\\is_hasharray") && !defined("ryunosuke\\dbml\\is_hasharray")) {
    define("ryunosuke\\dbml\\is_hasharray", "ryunosuke\\dbml\\is_hasharray");
}

if (!isset($excluded_functions["first_key"]) && (!function_exists("ryunosuke\\dbml\\first_key") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\first_key"))->isInternal()))) {
    /**
     * 配列の最初のキーを返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(first_key(['a', 'b', 'c']))->isSame(0);
     * that(first_key([], 999))->isSame(999);
     * ```
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 最初のキー
     */
    function first_key($array, $default = null)
    {
        if (is_empty($array)) {
            return $default;
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$k, $v] = first_keyvalue($array);
        return $k;
    }
}
if (function_exists("ryunosuke\\dbml\\first_key") && !defined("ryunosuke\\dbml\\first_key")) {
    define("ryunosuke\\dbml\\first_key", "ryunosuke\\dbml\\first_key");
}

if (!isset($excluded_functions["first_value"]) && (!function_exists("ryunosuke\\dbml\\first_value") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\first_value"))->isInternal()))) {
    /**
     * 配列の最初の値を返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(first_value(['a', 'b', 'c']))->isSame('a');
     * that(first_value([], 999))->isSame(999);
     * ```
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 最初の値
     */
    function first_value($array, $default = null)
    {
        if (is_empty($array)) {
            return $default;
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$k, $v] = first_keyvalue($array);
        return $v;
    }
}
if (function_exists("ryunosuke\\dbml\\first_value") && !defined("ryunosuke\\dbml\\first_value")) {
    define("ryunosuke\\dbml\\first_value", "ryunosuke\\dbml\\first_value");
}

if (!isset($excluded_functions["first_keyvalue"]) && (!function_exists("ryunosuke\\dbml\\first_keyvalue") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\first_keyvalue"))->isInternal()))) {
    /**
     * 配列の最初のキー/値ペアをタプルで返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(first_keyvalue(['a', 'b', 'c']))->isSame([0, 'a']);
     * that(first_keyvalue([], 999))->isSame(999);
     * ```
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return array [最初のキー, 最初の値]
     */
    function first_keyvalue($array, $default = null)
    {
        foreach ($array as $k => $v) {
            return [$k, $v];
        }
        return $default;
    }
}
if (function_exists("ryunosuke\\dbml\\first_keyvalue") && !defined("ryunosuke\\dbml\\first_keyvalue")) {
    define("ryunosuke\\dbml\\first_keyvalue", "ryunosuke\\dbml\\first_keyvalue");
}

if (!isset($excluded_functions["last_key"]) && (!function_exists("ryunosuke\\dbml\\last_key") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\last_key"))->isInternal()))) {
    /**
     * 配列の最後のキーを返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(last_key(['a', 'b', 'c']))->isSame(2);
     * that(last_key([], 999))->isSame(999);
     * ```
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 最後のキー
     */
    function last_key($array, $default = null)
    {
        if (is_empty($array)) {
            return $default;
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$k, $v] = last_keyvalue($array);
        return $k;
    }
}
if (function_exists("ryunosuke\\dbml\\last_key") && !defined("ryunosuke\\dbml\\last_key")) {
    define("ryunosuke\\dbml\\last_key", "ryunosuke\\dbml\\last_key");
}

if (!isset($excluded_functions["last_value"]) && (!function_exists("ryunosuke\\dbml\\last_value") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\last_value"))->isInternal()))) {
    /**
     * 配列の最後の値を返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(last_value(['a', 'b', 'c']))->isSame('c');
     * that(last_value([], 999))->isSame(999);
     * ```
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 最後の値
     */
    function last_value($array, $default = null)
    {
        if (is_empty($array)) {
            return $default;
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$k, $v] = last_keyvalue($array);
        return $v;
    }
}
if (function_exists("ryunosuke\\dbml\\last_value") && !defined("ryunosuke\\dbml\\last_value")) {
    define("ryunosuke\\dbml\\last_value", "ryunosuke\\dbml\\last_value");
}

if (!isset($excluded_functions["last_keyvalue"]) && (!function_exists("ryunosuke\\dbml\\last_keyvalue") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\last_keyvalue"))->isInternal()))) {
    /**
     * 配列の最後のキー/値ペアをタプルで返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(last_keyvalue(['a', 'b', 'c']))->isSame([2, 'c']);
     * that(last_keyvalue([], 999))->isSame(999);
     * ```
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return array [最後のキー, 最後の値]
     */
    function last_keyvalue($array, $default = null)
    {
        if (is_empty($array)) {
            return $default;
        }
        if (is_array($array)) {
            $v = end($array);
            $k = key($array);
            return [$k, $v];
        }
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        foreach ($array as $k => $v) {
            // dummy
        }
        // $k がセットされてるなら「ループが最低でも1度回った（≠空）」とみなせる
        if (isset($k)) {
            /** @noinspection PhpUndefinedVariableInspection */
            return [$k, $v];
        }
        return $default;
    }
}
if (function_exists("ryunosuke\\dbml\\last_keyvalue") && !defined("ryunosuke\\dbml\\last_keyvalue")) {
    define("ryunosuke\\dbml\\last_keyvalue", "ryunosuke\\dbml\\last_keyvalue");
}

if (!isset($excluded_functions["array_implode"]) && (!function_exists("ryunosuke\\dbml\\array_implode") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_implode"))->isInternal()))) {
    /**
     * 配列の各要素の間に要素を差し込む
     *
     * 歴史的な理由はないが、引数をどちらの順番でも受けつけることが可能。
     * ただし、$glue を先に渡すパターンの場合は配列指定が可変引数渡しになる。
     *
     * 文字キーは保存されるが数値キーは再割り振りされる。
     *
     * Example:
     * ```php
     * // (配列, 要素) の呼び出し
     * that(array_implode(['a', 'b', 'c'], 'X'))->isSame(['a', 'X', 'b', 'X', 'c']);
     * // (要素, ...配列) の呼び出し
     * that(array_implode('X', 'a', 'b', 'c'))->isSame(['a', 'X', 'b', 'X', 'c']);
     * ```
     *
     * @param iterable|string $array 対象配列
     * @param string $glue 差し込む要素
     * @return array 差し込まれた配列
     */
    function array_implode($array, $glue)
    {
        // 第1引数が回せない場合は引数を入れ替えて可変引数パターン
        if (!is_array($array) && !$array instanceof \Traversable) {
            return array_implode(array_slice(func_get_args(), 1), $array);
        }

        $result = [];
        foreach ($array as $k => $v) {
            if (is_int($k)) {
                $result[] = $v;
            }
            else {
                $result[$k] = $v;
            }
            $result[] = $glue;
        }
        array_pop($result);
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_implode") && !defined("ryunosuke\\dbml\\array_implode")) {
    define("ryunosuke\\dbml\\array_implode", "ryunosuke\\dbml\\array_implode");
}

if (!isset($excluded_functions["array_sprintf"]) && (!function_exists("ryunosuke\\dbml\\array_sprintf") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_sprintf"))->isInternal()))) {
    /**
     * キーと値で sprintf する
     *
     * 配列の各要素を文字列化して返すイメージ。
     * $glue を与えるとさらに implode して返す（返り値が文字列になる）。
     *
     * $format は書式文字列（$v, $k）。
     * callable を与えると sprintf ではなくコールバック処理になる（$v, $k）。
     * 省略（null）するとキーを format 文字列、値を引数として **vsprintf** する。
     *
     * Example:
     * ```php
     * $array = ['key1' => 'val1', 'key2' => 'val2'];
     * // key, value を利用した sprintf
     * that(array_sprintf($array, '%2$s=%1$s'))->isSame(['key1=val1', 'key2=val2']);
     * // 第3引数を与えるとさらに implode される
     * that(array_sprintf($array, '%2$s=%1$s', ' '))->isSame('key1=val1 key2=val2');
     * // クロージャを与えるとコールバック動作になる
     * $closure = function($v, $k){return "$k=" . strtoupper($v);};
     * that(array_sprintf($array, $closure, ' '))->isSame('key1=VAL1 key2=VAL2');
     * // 省略すると vsprintf になる
     * that(array_sprintf([
     *     'str:%s,int:%d' => ['sss', '3.14'],
     *     'single:%s'     => 'str',
     * ], null, '|'))->isSame('str:sss,int:3|single:str');
     * ```
     *
     * @param iterable $array 対象配列
     * @param string|callable $format 書式文字列あるいはクロージャ
     * @param string $glue 結合文字列。未指定時は implode しない
     * @return array|string sprintf された配列
     */
    function array_sprintf($array, $format = null, $glue = null)
    {
        if (is_callable($format)) {
            $callback = func_user_func_array($format);
        }
        elseif ($format === null) {
            $callback = function ($v, $k) { return vsprintf($k, is_array($v) ? $v : [$v]); };
        }
        else {
            $callback = function ($v, $k) use ($format) { return sprintf($format, $v, $k); };
        }

        $result = [];
        foreach ($array as $k => $v) {
            $result[] = $callback($v, $k);
        }

        if ($glue !== null) {
            return implode($glue, $result);
        }

        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_sprintf") && !defined("ryunosuke\\dbml\\array_sprintf")) {
    define("ryunosuke\\dbml\\array_sprintf", "ryunosuke\\dbml\\array_sprintf");
}

if (!isset($excluded_functions["array_strpad"]) && (!function_exists("ryunosuke\\dbml\\array_strpad") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_strpad"))->isInternal()))) {
    /**
     * 配列のキー・要素に文字列を付加する
     *
     * $key_prefix, $val_prefix でそれぞれ「キーに付与する文字列」「値に付与する文字列」が指定できる。
     * 配列を与えると [サフィックス, プレフィックス] という意味になる。
     * デフォルト（ただの文字列）はプレフィックス（値だけに付与したいなら array_map で十分なので）。
     *
     * Example:
     * ```php
     * $array = ['key1' => 'val1', 'key2' => 'val2'];
     * // キーにプレフィックス付与
     * that(array_strpad($array, 'prefix-'))->isSame(['prefix-key1' => 'val1', 'prefix-key2' => 'val2']);
     * // 値にサフィックス付与
     * that(array_strpad($array, '', ['-suffix']))->isSame(['key1' => 'val1-suffix', 'key2' => 'val2-suffix']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param string|array $key_prefix キー側の付加文字列
     * @param string|array $val_prefix 値側の付加文字列
     * @return array 文字列付与された配列
     */
    function array_strpad($array, $key_prefix, $val_prefix = '')
    {
        $key_suffix = '';
        if (is_array($key_prefix)) {
            [$key_suffix, $key_prefix] = $key_prefix + [1 => ''];
        }
        $val_suffix = '';
        if (is_array($val_prefix)) {
            [$val_suffix, $val_prefix] = $val_prefix + [1 => ''];
        }

        $enable_key = strlen($key_prefix) || strlen($key_suffix);
        $enable_val = strlen($val_prefix) || strlen($val_suffix);

        $result = [];
        foreach ($array as $key => $val) {
            if ($enable_key) {
                $key = $key_prefix . $key . $key_suffix;
            }
            if ($enable_val) {
                $val = $val_prefix . $val . $val_suffix;
            }
            $result[$key] = $val;
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_strpad") && !defined("ryunosuke\\dbml\\array_strpad")) {
    define("ryunosuke\\dbml\\array_strpad", "ryunosuke\\dbml\\array_strpad");
}

if (!isset($excluded_functions["array_get"]) && (!function_exists("ryunosuke\\dbml\\array_get") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_get"))->isInternal()))) {
    /**
     * デフォルト値付きの配列値取得
     *
     * 存在しない場合は $default を返す。
     *
     * $key に配列を与えるとそれらの値の配列を返す（lookup 的な動作）。
     * その場合、$default が活きるのは「全て無かった場合」となる。
     *
     * さらに $key が配列の場合に限り、 $default を省略すると空配列として動作する。
     *
     * 同様に、$key にクロージャを与えると、その返り値が true 相当のものを返す。
     * その際、 $default が配列なら一致するものを配列で返し、配列でないなら単値で返す。
     *
     * Example:
     * ```php
     * // 単純取得
     * that(array_get(['a', 'b', 'c'], 1))->isSame('b');
     * // 単純デフォルト
     * that(array_get(['a', 'b', 'c'], 9, 999))->isSame(999);
     * // 配列取得
     * that(array_get(['a', 'b', 'c'], [0, 2]))->isSame([0 => 'a', 2 => 'c']);
     * // 配列部分取得
     * that(array_get(['a', 'b', 'c'], [0, 9]))->isSame([0 => 'a']);
     * // 配列デフォルト（null ではなく [] を返す）
     * that(array_get(['a', 'b', 'c'], [9]))->isSame([]);
     * // クロージャ指定＆単値（コールバックが true を返す最初の要素）
     * that(array_get(['a', 'b', 'c'], function($v){return in_array($v, ['b', 'c']);}))->isSame('b');
     * // クロージャ指定＆配列（コールバックが true を返すもの）
     * that(array_get(['a', 'b', 'c'], function($v){return in_array($v, ['b', 'c']);}, []))->isSame([1 => 'b', 2 => 'c']);
     * ```
     *
     * @param array $array 配列
     * @param string|int|array $key 取得したいキー。配列を与えると全て返す。クロージャの場合は true 相当を返す
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 指定したキーの値
     */
    function array_get($array, $key, $default = null)
    {
        if (is_array($key)) {
            $result = [];
            foreach ($key as $k) {
                // 深遠な事情で少しでも高速化したかったので isset || array_keys_exist にしてある
                if (isset($array[$k]) || array_keys_exist($k, $array)) {
                    $result[$k] = $array[$k];
                }
            }
            if (!$result) {
                // 明示的に与えられていないなら [] を使用する
                if (func_num_args() === 2) {
                    $default = [];
                }
                return $default;
            }
            return $result;
        }

        if ($key instanceof \Closure) {
            $result = [];
            foreach ($array as $k => $v) {
                if ($key($v, $k)) {
                    if (func_num_args() === 2) {
                        return $v;
                    }
                    $result[$k] = $v;
                }
            }
            if (!$result) {
                return $default;
            }
            return $result;
        }

        if (array_keys_exist($key, $array)) {
            return $array[$key];
        }
        return $default;
    }
}
if (function_exists("ryunosuke\\dbml\\array_get") && !defined("ryunosuke\\dbml\\array_get")) {
    define("ryunosuke\\dbml\\array_get", "ryunosuke\\dbml\\array_get");
}

if (!isset($excluded_functions["array_set"]) && (!function_exists("ryunosuke\\dbml\\array_set") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_set"))->isInternal()))) {
    /**
     * キー指定の配列値設定
     *
     * 第3引数を省略すると（null を与えると）言語機構を使用して配列の最後に設定する（$array[] = $value）。
     * 第3引数に配列を指定すると潜って設定する。
     *
     * Example:
     * ```php
     * $array = ['a' => 'A', 'B'];
     * // 第3引数省略（最後に連番キーで設定）
     * that(array_set($array, 'Z'))->isSame(1);
     * that($array)->isSame(['a' => 'A', 'B', 'Z']);
     * // 第3引数でキーを指定
     * that(array_set($array, 'Z', 'z'))->isSame('z');
     * that($array)->isSame(['a' => 'A', 'B', 'Z', 'z' => 'Z']);
     * that(array_set($array, 'Z', 'z'))->isSame('z');
     * // 第3引数で配列を指定
     * that(array_set($array, 'Z', ['x', 'y', 'z']))->isSame('z');
     * that($array)->isSame(['a' => 'A', 'B', 'Z', 'z' => 'Z', 'x' => ['y' => ['z' => 'Z']]]);
     * ```
     *
     * @param array $array 配列
     * @param mixed $value 設定する値
     * @param array|string|int|null $key 設定するキー
     * @param bool $require_return 返り値が不要なら false を渡す
     * @return string|int 設定したキー
     */
    function array_set(&$array, $value, $key = null, $require_return = true)
    {
        if (is_array($key)) {
            $k = array_shift($key);
            if ($key) {
                if (is_array($array) && array_key_exists($k, $array) && !is_array($array[$k])) {
                    throw new \InvalidArgumentException('$array[$k] is not array.');
                }
                return array_set(...[&$array[$k], $value, $key, $require_return]);
            }
            else {
                return array_set(...[&$array, $value, $k, $require_return]);
            }
        }

        if ($key === null) {
            $array[] = $value;
            if ($require_return === true) {
                $key = last_key($array);
            }
        }
        else {
            $array[$key] = $value;
        }
        return $key;
    }
}
if (function_exists("ryunosuke\\dbml\\array_set") && !defined("ryunosuke\\dbml\\array_set")) {
    define("ryunosuke\\dbml\\array_set", "ryunosuke\\dbml\\array_set");
}

if (!isset($excluded_functions["array_put"]) && (!function_exists("ryunosuke\\dbml\\array_put") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_put"))->isInternal()))) {
    /**
     * キー指定の配列値設定
     *
     * array_set とほとんど同じ。
     * 第3引数を省略すると（null を与えると）言語機構を使用して配列の最後に設定する（$array[] = $value）。
     * また、**int を与えても同様の動作**となる。
     * 第3引数に配列を指定すると潜って設定する。
     *
     * 第4引数で追加する条件クロージャを指定できる。
     * クロージャには `(追加する要素, 追加するキー, 追加される元配列)` が渡ってくる。
     * このクロージャが false 相当を返した時は追加されないようになる。
     *
     * array_set における $require_return は廃止している。
     * これはもともと end や last_key が遅かったのでオプショナルにしていたが、もう改善しているし、7.3 から array_key_last があるので、呼び元で適宜使えば良い。
     *
     * Example:
     * ```php
     * $array = ['a' => 'A', 'B'];
     * // 第3引数 int
     * that(array_put($array, 'Z', 999))->isSame(1);
     * that($array)->isSame(['a' => 'A', 'B', 'Z']);
     * // 第3引数省略（最後に連番キーで設定）
     * that(array_put($array, 'Z'))->isSame(2);
     * that($array)->isSame(['a' => 'A', 'B', 'Z', 'Z']);
     * // 第3引数でキーを指定
     * that(array_put($array, 'Z', 'z'))->isSame('z');
     * that($array)->isSame(['a' => 'A', 'B', 'Z', 'Z', 'z' => 'Z']);
     * that(array_put($array, 'Z', 'z'))->isSame('z');
     * // 第3引数で配列を指定
     * that(array_put($array, 'Z', ['x', 'y', 'z']))->isSame('z');
     * that($array)->isSame(['a' => 'A', 'B', 'Z', 'Z', 'z' => 'Z', 'x' => ['y' => ['z' => 'Z']]]);
     * // 第4引数で条件を指定（キーが存在するなら追加しない）
     * that(array_put($array, 'Z', 'z', function ($v, $k, $array){return !isset($array[$k]);}))->isSame(false);
     * // 第4引数で条件を指定（値が存在するなら追加しない）
     * that(array_put($array, 'Z', null, function ($v, $k, $array){return !in_array($v, $array);}))->isSame(false);
     * ```
     *
     * @param array $array 配列
     * @param mixed $value 設定する値
     * @param array|string|int|null $key 設定するキー
     * @param callable|null $condition 追加する条件
     * @return string|int|false 設定したキー
     */
    function array_put(&$array, $value, $key = null, $condition = null)
    {
        if (is_array($key)) {
            $k = array_shift($key);
            if ($key) {
                if (is_array($array) && array_key_exists($k, $array) && !is_array($array[$k])) {
                    throw new \InvalidArgumentException('$array[$k] is not array.');
                }
                return array_put(...[&$array[$k], $value, $key, $condition]);
            }
            else {
                return array_put(...[&$array, $value, $k, $condition]);
            }
        }

        if ($condition !== null) {
            if (!$condition($value, $key, $array)) {
                return false;
            }
        }

        if ($key === null || is_int($key)) {
            $array[] = $value;
            // compatible array_key_last under 7.3
            end($array);
            $key = key($array);
        }
        else {
            $array[$key] = $value;
        }
        return $key;
    }
}
if (function_exists("ryunosuke\\dbml\\array_put") && !defined("ryunosuke\\dbml\\array_put")) {
    define("ryunosuke\\dbml\\array_put", "ryunosuke\\dbml\\array_put");
}

if (!isset($excluded_functions["array_unset"]) && (!function_exists("ryunosuke\\dbml\\array_unset") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_unset"))->isInternal()))) {
    /**
     * 伏せると同時にその値を返す
     *
     * $key に配列を与えると全て伏せて配列で返す。
     * その場合、$default が活きるのは「全て無かった場合」となる。
     *
     * さらに $key が配列の場合に限り、 $default を省略すると空配列として動作する。
     *
     * 配列を与えた場合の返り値は与えた配列の順番・キーが活きる。
     * これを利用すると list の展開の利便性が上がったり、連想配列で返すことができる。
     *
     * 同様に、$key にクロージャを与えると、その返り値が true 相当のものを伏せて配列で返す。
     * callable ではなくクロージャのみ対応する。
     *
     * Example:
     * ```php
     * $array = ['a' => 'A', 'b' => 'B'];
     * // ない場合は $default を返す
     * that(array_unset($array, 'x', 'X'))->isSame('X');
     * // 指定したキーを返す。そのキーは伏せられている
     * that(array_unset($array, 'a'))->isSame('A');
     * that($array)->isSame(['b' => 'B']);
     *
     * $array = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
     * // 配列を与えるとそれらを返す。そのキーは全て伏せられている
     * that(array_unset($array, ['a', 'b', 'x']))->isSame(['A', 'B']);
     * that($array)->isSame(['c' => 'C']);
     *
     * $array = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
     * // 配列のキーは返されるキーを表す。順番も維持される
     * that(array_unset($array, ['x2' => 'b', 'x1' => 'a']))->isSame(['x2' => 'B', 'x1' => 'A']);
     *
     * $array = ['hoge' => 'HOGE', 'fuga' => 'FUGA', 'piyo' => 'PIYO'];
     * // 値に "G" を含むものを返す。その要素は伏せられている
     * that(array_unset($array, function($v){return strpos($v, 'G') !== false;}))->isSame(['hoge' => 'HOGE', 'fuga' => 'FUGA']);
     * that($array)->isSame(['piyo' => 'PIYO']);
     * ```
     *
     * @param array $array 配列
     * @param string|int|array|callable $key 伏せたいキー。配列を与えると全て伏せる。クロージャの場合は true 相当を伏せる
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 指定したキーの値
     */
    function array_unset(&$array, $key, $default = null)
    {
        if (is_array($key)) {
            $result = [];
            foreach ($key as $rk => $ak) {
                if (array_keys_exist($ak, $array)) {
                    $result[$rk] = $array[$ak];
                    unset($array[$ak]);
                }
            }
            if (!$result) {
                // 明示的に与えられていないなら [] を使用する
                if (func_num_args() === 2) {
                    $default = [];
                }
                return $default;
            }
            return $result;
        }

        if ($key instanceof \Closure) {
            $result = [];
            foreach ($array as $k => $v) {
                if ($key($v, $k)) {
                    $result[$k] = $v;
                    unset($array[$k]);
                }
            }
            if (!$result) {
                return $default;
            }
            return $result;
        }

        if (array_keys_exist($key, $array)) {
            $result = $array[$key];
            unset($array[$key]);
            return $result;
        }
        return $default;
    }
}
if (function_exists("ryunosuke\\dbml\\array_unset") && !defined("ryunosuke\\dbml\\array_unset")) {
    define("ryunosuke\\dbml\\array_unset", "ryunosuke\\dbml\\array_unset");
}

if (!isset($excluded_functions["array_keys_exist"]) && (!function_exists("ryunosuke\\dbml\\array_keys_exist") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_keys_exist"))->isInternal()))) {
    /**
     * array_key_exists の複数版
     *
     * 指定キーが全て存在するなら true を返す。
     * 配列ではなく単一文字列を与えても動作する（array_key_exists と全く同じ動作になる）。
     *
     * $keys に空を与えると例外を投げる。
     * $keys に配列を与えるとキーで潜ってチェックする（Example 参照）。
     *
     * Example:
     * ```php
     * // すべて含むので true
     * that(array_keys_exist(['a', 'b', 'c'], ['a' => 'A', 'b' => 'B', 'c' => 'C']))->isTrue();
     * // N は含まないので false
     * that(array_keys_exist(['a', 'b', 'N'], ['a' => 'A', 'b' => 'B', 'c' => 'C']))->isFalse();
     * // 配列を与えると潜る（日本語で言えば「a というキーと、x というキーとその中に x1, x2 というキーがあるか？」）
     * that(array_keys_exist(['a', 'x' => ['x1', 'x2']], ['a' => 'A', 'x' => ['x1' => 'X1', 'x2' => 'X2']]))->isTrue();
     * ```
     *
     * @param array|string $keys 調べるキー
     * @param array|\ArrayAccess $array 調べる配列
     * @return bool 指定キーが全て存在するなら true
     */
    function array_keys_exist($keys, $array)
    {
        $keys = is_iterable($keys) ? $keys : [$keys];
        if (is_empty($keys)) {
            throw new \InvalidArgumentException('$keys is empty.');
        }

        $is_arrayaccess = $array instanceof \ArrayAccess;

        foreach ($keys as $k => $key) {
            if (is_array($key)) {
                // まずそのキーをチェックして
                if (!array_keys_exist($k, $array)) {
                    return false;
                }
                // あるなら再帰する
                if (!array_keys_exist($key, $array[$k])) {
                    return false;
                }
            }
            elseif ($is_arrayaccess) {
                if (!$array->offsetExists($key)) {
                    return false;
                }
            }
            elseif (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }
}
if (function_exists("ryunosuke\\dbml\\array_keys_exist") && !defined("ryunosuke\\dbml\\array_keys_exist")) {
    define("ryunosuke\\dbml\\array_keys_exist", "ryunosuke\\dbml\\array_keys_exist");
}

if (!isset($excluded_functions["array_find"]) && (!function_exists("ryunosuke\\dbml\\array_find") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_find"))->isInternal()))) {
    /**
     * array_search のクロージャ版のようなもの
     *
     * コールバックの返り値が true 相当のものを返す。
     * $is_key に true を与えるとそのキーを返す（デフォルトの動作）。
     * $is_key に false を与えるとコールバックの結果を返す。
     *
     * この関数は論理値 FALSE を返す可能性がありますが、FALSE として評価される値を返す可能性もあります。
     *
     * Example:
     * ```php
     * // 最初に見つかったキーを返す
     * that(array_find(['a', 'b', '9'], 'ctype_digit'))->isSame(2);
     * that(array_find(['a', 'b', '9'], function($v){return $v === 'b';}))->isSame(1);
     * // 最初に見つかったコールバック結果を返す（最初の数字の2乗を返す）
     * $ifnumeric2power = function($v){return ctype_digit($v) ? $v * $v : false;};
     * that(array_find(['a', 'b', '9'], $ifnumeric2power, false))->isSame(81);
     * ```
     *
     * @param iterable $array 調べる配列
     * @param callable $callback 評価コールバック
     * @param bool $is_key キーを返すか否か
     * @return mixed コールバックが true を返した最初のキー。存在しなかったら false
     */
    function array_find($array, $callback, $is_key = true)
    {
        $callback = func_user_func_array($callback);

        foreach ($array as $k => $v) {
            $result = $callback($v, $k);
            if ($result) {
                if ($is_key) {
                    return $k;
                }
                return $result;
            }
        }
        return false;
    }
}
if (function_exists("ryunosuke\\dbml\\array_find") && !defined("ryunosuke\\dbml\\array_find")) {
    define("ryunosuke\\dbml\\array_find", "ryunosuke\\dbml\\array_find");
}

if (!isset($excluded_functions["array_rekey"]) && (!function_exists("ryunosuke\\dbml\\array_rekey") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_rekey"))->isInternal()))) {
    /**
     * キーをマップ配列・callable で置換する
     *
     * 変換先・返り値が null だとその要素は取り除かれる。
     * callable 指定時の引数は `(キー, 値, 連番インデックス, 対象配列そのもの)` が渡ってくる。
     *
     * Example:
     * ```php
     * $array = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
     * // a は x に c は z に置換される
     * that(array_rekey($array, ['a' => 'x', 'c' => 'z']))->isSame(['x' => 'A', 'b' => 'B', 'z' => 'C']);
     * // b は削除され c は z に置換される
     * that(array_rekey($array, ['b' => null, 'c' => 'z']))->isSame(['a' => 'A', 'z' => 'C']);
     * // キーの交換にも使える（a ⇔ c）
     * that(array_rekey($array, ['a' => 'c', 'c' => 'a']))->isSame(['c' => 'A', 'b' => 'B', 'a' => 'C']);
     * // callable
     * that(array_rekey($array, 'strtoupper'))->isSame(['A' => 'A', 'B' => 'B', 'C' => 'C']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param array|callable $keymap マップ配列かキーを返すクロージャ
     * @return array キーが置換された配列
     */
    function array_rekey($array, $keymap)
    {
        // 互換性のため callable は配列以外に限定する
        $callable = ($keymap instanceof \Closure) || (!is_array($keymap) && is_callable($keymap));
        if ($callable) {
            $keymap = func_user_func_array($keymap);
        }

        $result = [];
        $n = 0;
        foreach ($array as $k => $v) {
            if ($callable) {
                $k = $keymap($k, $v, $n, $array);
                // null は突っ込まない（除去）
                if ($k !== null) {
                    $result[$k] = $v;
                }
            }
            elseif (array_key_exists($k, $keymap)) {
                // null は突っ込まない（除去）
                if ($keymap[$k] !== null) {
                    $result[$keymap[$k]] = $v;
                }
            }
            else {
                $result[$k] = $v;
            }
            $n++;
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_rekey") && !defined("ryunosuke\\dbml\\array_rekey")) {
    define("ryunosuke\\dbml\\array_rekey", "ryunosuke\\dbml\\array_rekey");
}

if (!isset($excluded_functions["array_map_key"]) && (!function_exists("ryunosuke\\dbml\\array_map_key") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_map_key"))->isInternal()))) {
    /**
     * キーをマップして変換する
     *
     * $callback が null を返すとその要素は取り除かれる。
     *
     * Example:
     * ```php
     * that(array_map_key(['a' => 'A', 'b' => 'B'], 'strtoupper'))->isSame(['A' => 'A', 'B' => 'B']);
     * that(array_map_key(['a' => 'A', 'b' => 'B'], function(){}))->isSame([]);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ
     * @return array キーが変換された新しい配列
     */
    function array_map_key($array, $callback)
    {
        $callback = func_user_func_array($callback);
        $result = [];
        foreach ($array as $k => $v) {
            $k2 = $callback($k, $v);
            if ($k2 !== null) {
                $result[$k2] = $v;
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_map_key") && !defined("ryunosuke\\dbml\\array_map_key")) {
    define("ryunosuke\\dbml\\array_map_key", "ryunosuke\\dbml\\array_map_key");
}

if (!isset($excluded_functions["array_map_filter"]) && (!function_exists("ryunosuke\\dbml\\array_map_filter") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_map_filter"))->isInternal()))) {
    /**
     * array_map + array_filter する
     *
     * コールバックを適用して、結果が true 相当の要素のみ取り出す。
     * $strict に true を与えると「null でない」要素のみ返される。
     *
     * $callback が要求するならキーも渡ってくる。
     *
     * Example:
     * ```php
     * that(array_map_filter([' a ', ' b ', ''], 'trim'))->isSame(['a', 'b']);
     * that(array_map_filter([' a ', ' b ', ''], 'trim', true))->isSame(['a', 'b', '']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ
     * @param bool $strict 厳密比較フラグ。 true だと null のみが偽とみなされる
     * @return array $callback が真を返した新しい配列
     */
    function array_map_filter($array, $callback, $strict = false)
    {
        $callback = func_user_func_array($callback);
        $result = [];
        foreach ($array as $k => $v) {
            $vv = $callback($v, $k);
            if (($strict && $vv !== null) || (!$strict && $vv)) {
                $result[$k] = $vv;
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_map_filter") && !defined("ryunosuke\\dbml\\array_map_filter")) {
    define("ryunosuke\\dbml\\array_map_filter", "ryunosuke\\dbml\\array_map_filter");
}

if (!isset($excluded_functions["array_maps"]) && (!function_exists("ryunosuke\\dbml\\array_maps") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_maps"))->isInternal()))) {
    /**
     * 複数コールバックを指定できる array_map
     *
     * 指定したコールバックで複数回回してマップする。
     * `array_maps($array, $f, $g)` は `array_map($g, array_map($f, $array))` とほぼ等しい。
     * ただし、引数は順番が違う（可変引数のため）し、コールバックが要求するならキーも渡ってくる。
     *
     * 少し変わった仕様として、コールバックに [$method => $args] を付けるとそれはメソッド呼び出しになる。
     * つまり各要素 $v に対して `$v->$method(...$args)` がマップ結果になる。
     * さらに引数が不要なら `@method` とするだけで良い。
     *
     * Example:
     * ```php
     * // 値を3乗したあと16進表記にして大文字化する
     * that(array_maps([1, 2, 3, 4, 5], rbind('pow', 3), 'dechex', 'strtoupper'))->isSame(['1', '8', '1B', '40', '7D']);
     * // キーも渡ってくる
     * that(array_maps(['a' => 'A', 'b' => 'B'], function($v, $k){return "$k:$v";}))->isSame(['a' => 'a:A', 'b' => 'b:B']);
     * // メソッドコールもできる（引数不要なら `@method` でも同じ）
     * that(array_maps([new \Exception('a'), new \Exception('b')], ['getMessage' => []]))->isSame(['a', 'b']);
     * that(array_maps([new \Exception('a'), new \Exception('b')], '@getMessage'))->isSame(['a', 'b']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable[] $callbacks 評価クロージャ配列
     * @return array 評価クロージャを通した新しい配列
     */
    function array_maps($array, ...$callbacks)
    {
        $result = arrayval($array, false);
        foreach ($callbacks as $callback) {
            if (is_string($callback) && $callback[0] === '@') {
                $margs = [];
                $callback = substr($callback, 1);
            }
            elseif (is_array($callback) && count($callback) === 1) {
                $margs = reset($callback);
                $callback = key($callback);
            }
            else {
                $margs = null;
                $callback = func_user_func_array($callback);
            }
            foreach ($result as $k => $v) {
                if (isset($margs)) {
                    $result[$k] = ([$v, $callback])(...$margs);
                }
                else {
                    $result[$k] = $callback($v, $k);
                }
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_maps") && !defined("ryunosuke\\dbml\\array_maps")) {
    define("ryunosuke\\dbml\\array_maps", "ryunosuke\\dbml\\array_maps");
}

if (!isset($excluded_functions["array_kvmap"]) && (!function_exists("ryunosuke\\dbml\\array_kvmap") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_kvmap"))->isInternal()))) {
    /**
     * 配列の各キー・値にコールバックを適用する
     *
     * $callback は (キー, 値, $callback) が渡ってくるので 「その位置に配置したい配列」を返せばそこに置換される。
     * つまり、空配列を返せばそのキー・値は消えることになるし、複数の配列を返せば要素が増えることになる。
     * ただし、数値キーは新しく採番される。
     * null を返すと特別扱いで、そのキー・値をそのまま維持する。
     * iterable を返す必要があるが、もし iterable でない場合は配列キャストされる。
     *
     * 「map も filter も可能でキー変更可能」というとてもマッチョな関数。
     * 実質的には「数値キーが再採番される再帰的でない array_convert」のように振る舞う。
     * ただし、再帰処理はないので自前で管理する必要がある。
     *
     * Example:
     * ```php
     * $array = [
     *    'a' => 'A',
     *    'b' => 'B',
     *    'c' => 'C',
     *    'd' => 'D',
     * ];
     * // キーに '_' 、値に 'prefix-' を付与。'b' は一切何もしない。'c' は値のみ。'd' はそれ自体伏せる
     * that(array_kvmap($array, function($k, $v){
     *     if ($k === 'b') return null;
     *     if ($k === 'd') return [];
     *     if ($k !== 'c') $k = "_$k";
     *     return [$k => "prefix-$v"];
     * }))->isSame([
     *     '_a' => 'prefix-A',
     *     'b'  => 'B',
     *     'c'  => 'prefix-C',
     * ]);
     *
     * // 複数返せばその分増える（要素の水増し）
     * that(array_kvmap($array, function($k, $v){
     *     return [
     *         "{$k}1" => "{$v}1",
     *         "{$k}2" => "{$v}2",
     *     ];
     * }))->isSame([
     *    'a1' => 'A1',
     *    'a2' => 'A2',
     *    'b1' => 'B1',
     *    'b2' => 'B2',
     *    'c1' => 'C1',
     *    'c2' => 'C2',
     *    'd1' => 'D1',
     *    'd2' => 'D2',
     * ]);
     *
     * // $callback には $callback 自体も渡ってくるので再帰も比較的楽に書ける
     * that(array_kvmap([
     *     'x' => [
     *         'X',
     *         'y' => [
     *             'Y',
     *             'z' => ['Z'],
     *         ],
     *     ],
     * ], function($k, $v, $callback){
     *     // 配列だったら再帰する
     *     return ["_$k" => is_array($v) ? array_kvmap($v, $callback) : "prefix-$v"];
     * }))->isSame([
     *     "_x" => [
     *         "_0" => "prefix-X",
     *         "_y" => [
     *             "_0" => "prefix-Y",
     *             "_z" => [
     *                 "_0" => "prefix-Z",
     *             ],
     *         ],
     *     ],
     * ]);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 適用するコールバック
     * @return array 変換された配列
     */
    function array_kvmap($array, $callback)
    {
        $result = [];
        foreach ($array as $k => $v) {
            $kv = $callback($k, $v, $callback) ?? [$k => $v];
            if (!is_iterable($kv)) {
                $kv = [$kv];
            }
            // $result = array_merge($result, $kv); // 遅すぎる
            foreach ($kv as $k2 => $v2) {
                if (is_int($k2)) {
                    $result[] = $v2;
                }
                else {
                    $result[$k2] = $v2;
                }
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_kvmap") && !defined("ryunosuke\\dbml\\array_kvmap")) {
    define("ryunosuke\\dbml\\array_kvmap", "ryunosuke\\dbml\\array_kvmap");
}

if (!isset($excluded_functions["array_kmap"]) && (!function_exists("ryunosuke\\dbml\\array_kmap") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_kmap"))->isInternal()))) {
    /**
     * キーも渡ってくる array_map
     *
     * `array_map($callback, $array, array_keys($array))` とほとんど変わりはない。
     * 違いは下記。
     *
     * - 引数の順番が異なる（$array が先）
     * - キーが死なない（array_map は複数配列を与えるとキーが死ぬ）
     * - 配列だけでなく Traversable も受け入れる
     * - callback の第3引数に 0 からの連番が渡ってくる
     *
     * Example:
     * ```php
     * // キー・値をくっつけるシンプルな例
     * that(array_kmap([
     *     'k1' => 'v1',
     *     'k2' => 'v2',
     *     'k3' => 'v3',
     * ], function($v, $k){return "$k:$v";}))->isSame([
     *     'k1' => 'k1:v1',
     *     'k2' => 'k2:v2',
     *     'k3' => 'k3:v3',
     * ]);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ
     * @return array $callback を通した新しい配列
     */
    function array_kmap($array, $callback)
    {
        $callback = func_user_func_array($callback);

        $n = 0;
        $result = [];
        foreach ($array as $k => $v) {
            $result[$k] = $callback($v, $k, $n++);
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_kmap") && !defined("ryunosuke\\dbml\\array_kmap")) {
    define("ryunosuke\\dbml\\array_kmap", "ryunosuke\\dbml\\array_kmap");
}

if (!isset($excluded_functions["array_nmap"]) && (!function_exists("ryunosuke\\dbml\\array_nmap") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_nmap"))->isInternal()))) {
    /**
     * 要素値を $callback の n 番目(0ベース)に適用して array_map する
     *
     * 引数 $n に配列を与えると [キー番目 => 値番目] とみなしてキー・値も渡される（Example 参照）。
     * その際、「挿入後の番目」ではなく、単純に「元の引数の番目」であることに留意。キー・値が同じ位置を指定している場合はキーが先にくる。
     *
     * Example:
     * ```php
     * // 1番目に値を渡して map
     * $sprintf = function(){return vsprintf('%s%s%s', func_get_args());};
     * that(array_nmap(['a', 'b'], $sprintf, 1, 'prefix-', '-suffix'))->isSame(['prefix-a-suffix', 'prefix-b-suffix']);
     * // 1番目にキー、2番目に値を渡して map
     * $sprintf = function(){return vsprintf('%s %s %s %s %s', func_get_args());};
     * that(array_nmap(['k' => 'v'], $sprintf, [1 => 2], 'a', 'b', 'c'))->isSame(['k' => 'a k b v c']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ
     * @param int|array $n 要素値を入れる引数番目。配列を渡すとキー・値の両方を指定でき、両方が渡ってくる
     * @param mixed $variadic $callback に渡され、改変される引数（可変引数）
     * @return array 評価クロージャを通した新しい配列
     */
    function array_nmap($array, $callback, $n, ...$variadic)
    {
        /** @var $kn */
        /** @var $vn */

        $is_array = is_array($n);
        $args = $variadic;

        // 配列が来たら [キー番目 => 値番目] とみなす
        if ($is_array) {
            if (empty($n)) {
                throw new \InvalidArgumentException('array $n is empty.');
            }
            [$kn, $vn] = first_keyvalue($n);

            // array_insert は負数も受け入れられるが、それを考慮しだすともう収拾がつかない
            if ($kn < 0 || $vn < 0) {
                throw new \InvalidArgumentException('$kn, $vn must be positive.');
            }

            // どちらが大きいかで順番がズレるので分岐しなければならない
            if ($kn <= $vn) {
                $args = array_insert($args, null, $kn);
                $args = array_insert($args, null, ++$vn);// ↑で挿入してるので+1
            }
            else {
                $args = array_insert($args, null, $vn);
                $args = array_insert($args, null, ++$kn);// ↑で挿入してるので+1
            }
        }
        else {
            $args = array_insert($args, null, $n);
        }

        $result = [];
        foreach ($array as $k => $v) {
            // キー値モードなら両方埋める
            if ($is_array) {
                $args[$kn] = $k;
                $args[$vn] = $v;
            }
            // 値のみなら値だけ
            else {
                $args[$n] = $v;
            }
            $result[$k] = $callback(...$args);
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_nmap") && !defined("ryunosuke\\dbml\\array_nmap")) {
    define("ryunosuke\\dbml\\array_nmap", "ryunosuke\\dbml\\array_nmap");
}

if (!isset($excluded_functions["array_rmap"]) && (!function_exists("ryunosuke\\dbml\\array_rmap") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_rmap"))->isInternal()))) {
    /**
     * 要素値を $callback の最右に適用して array_map する
     *
     * Example:
     * ```php
     * $sprintf = function(){return vsprintf('%s%s', func_get_args());};
     * that(array_rmap(['a', 'b'], $sprintf, 'prefix-'))->isSame(['prefix-a', 'prefix-b']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ
     * @param mixed $variadic $callback に渡され、改変される引数（可変引数）
     * @return array 評価クロージャを通した新しい配列
     */
    function array_rmap($array, $callback, ...$variadic)
    {
        return array_nmap(...array_insert(func_get_args(), func_num_args() - 2, 2));
    }
}
if (function_exists("ryunosuke\\dbml\\array_rmap") && !defined("ryunosuke\\dbml\\array_rmap")) {
    define("ryunosuke\\dbml\\array_rmap", "ryunosuke\\dbml\\array_rmap");
}

if (!isset($excluded_functions["array_each"]) && (!function_exists("ryunosuke\\dbml\\array_each") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_each"))->isInternal()))) {
    /**
     * array_reduce の参照版（のようなもの）
     *
     * 配列をループで回し、その途中経過、値、キー、連番をコールバック引数で渡して最終的な結果を返り値として返す。
     * array_reduce と少し似てるが、下記の点が異なる。
     *
     * - いわゆる $carry は返り値で表すのではなく、参照引数で表す
     * - 値だけでなくキー、連番も渡ってくる
     * - 巨大配列の場合でも速度劣化が少ない（array_reduce に巨大配列を渡すと実用にならないレベルで遅くなる）
     *
     * $callback の引数は `($value, $key, $n)` （$n はキーとは関係がない 0 ～ 要素数-1 の通し連番）。
     *
     * 返り値ではなく参照引数なので return する必要はない（ワンライナーが書きやすくなる）。
     * 返り値が空くのでループ制御に用いる。
     * 今のところ $callback が false を返すとそこで break するのみ。
     *
     * 第3引数を省略した場合、**クロージャの第1引数のデフォルト値が使われる**。
     * これは特筆すべき動作で、不格好な第3引数を完全に省略することができる（サンプルコードを参照）。
     * ただし「php の文法違反（今のところエラーにはならないし、全てにデフォルト値をつければ一応回避可能）」「リフレクションを使う（ほんの少し遅くなる）」などの弊害が有るので推奨はしない。
     * （ただ、「意図していることをコードで表す」といった観点ではこの記法の方が正しいとも思う）。
     *
     * Example:
     * ```php
     * // 全要素を文字列的に足し合わせる
     * that(array_each([1, 2, 3, 4, 5], function(&$carry, $v){$carry .= $v;}, ''))->isSame('12345');
     * // 値をキーにして要素を2乗値にする
     * that(array_each([1, 2, 3, 4, 5], function(&$carry, $v){$carry[$v] = $v * $v;}, []))->isSame([
     *     1 => 1,
     *     2 => 4,
     *     3 => 9,
     *     4 => 16,
     *     5 => 25,
     * ]);
     * // 上記と同じ。ただし、3 で break する
     * that(array_each([1, 2, 3, 4, 5], function(&$carry, $v, $k){
     *     if ($k === 3) return false;
     *     $carry[$v] = $v * $v;
     * }, []))->isSame([
     *     1 => 1,
     *     2 => 4,
     *     3 => 9,
     * ]);
     *
     * // 下記は完全に同じ（第3引数の代わりにデフォルト引数を使っている）
     * that(array_each([1, 2, 3], function(&$carry = [], $v) {
     *         $carry[$v] = $v * $v;
     *     }))->isSame(array_each([1, 2, 3], function(&$carry, $v) {
     *         $carry[$v] = $v * $v;
     *     }, [])
     *     // 個人的に↑のようなぶら下がり引数があまり好きではない（クロージャを最後の引数にしたい）
     * );
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ。(&$carry, $key, $value) を受ける
     * @param mixed $default ループの最初や空の場合に適用される値
     * @return mixed each した結果
     */
    function array_each($array, $callback, $default = null)
    {
        if (func_num_args() === 2) {
            /** @var \ReflectionFunction $ref */
            $ref = reflect_callable($callback);
            $params = $ref->getParameters();
            if ($params[0]->isDefaultValueAvailable()) {
                $default = $params[0]->getDefaultValue();
            }
        }

        $n = 0;
        foreach ($array as $k => $v) {
            $return = $callback($default, $v, $k, $n++);
            if ($return === false) {
                break;
            }
        }
        return $default;
    }
}
if (function_exists("ryunosuke\\dbml\\array_each") && !defined("ryunosuke\\dbml\\array_each")) {
    define("ryunosuke\\dbml\\array_each", "ryunosuke\\dbml\\array_each");
}

if (!isset($excluded_functions["array_depth"]) && (!function_exists("ryunosuke\\dbml\\array_depth") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_depth"))->isInternal()))) {
    /**
     * 配列の次元数を返す
     *
     * フラット配列は 1 と定義する。
     * つまり、配列を与える限りは 0 以下を返すことはない。
     *
     * 第2引数 $max_depth を与えるとその階層になった時点で走査を打ち切る。
     * 「1階層のみか？」などを調べるときは指定したほうが高速に動作する。
     *
     * Example:
     * ```php
     * that(array_depth([]))->isSame(1);
     * that(array_depth(['hoge']))->isSame(1);
     * that(array_depth([['nest1' => ['nest2']]]))->isSame(3);
     * ```
     *
     * @param array $array 調べる配列
     * @param int|null $max_depth 最大階層数
     * @return int 次元数。素のフラット配列は 1
     */
    function array_depth($array, $max_depth = null)
    {
        assert((is_null($max_depth)) || $max_depth > 0);

        $main = function ($array, $depth) use (&$main, $max_depth) {
            // $max_depth を超えているなら打ち切る
            if ($max_depth !== null && $depth >= $max_depth) {
                return 1;
            }

            // 配列以外に興味はない
            $arrays = array_filter($array, 'is_array');

            // ネストしない配列は 1 と定義
            if (!$arrays) {
                return 1;
            }

            // 配下の内で最大を返す
            return 1 + max(array_map(function ($v) use ($main, $depth) { return $main($v, $depth + 1); }, $arrays));
        };

        return $main($array, 1);
    }
}
if (function_exists("ryunosuke\\dbml\\array_depth") && !defined("ryunosuke\\dbml\\array_depth")) {
    define("ryunosuke\\dbml\\array_depth", "ryunosuke\\dbml\\array_depth");
}

if (!isset($excluded_functions["array_insert"]) && (!function_exists("ryunosuke\\dbml\\array_insert") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_insert"))->isInternal()))) {
    /**
     * 配列・連想配列を問わず任意の位置に値を挿入する
     *
     * $position を省略すると最後に挿入される（≒ array_push）。
     * $position に負数を与えると後ろから数えられる。
     * $value には配列も与えられるが、その場合数値キーは振り直される
     *
     * Example:
     * ```php
     * that(array_insert([1, 2, 3], 'x'))->isSame([1, 2, 3, 'x']);
     * that(array_insert([1, 2, 3], 'x', 1))->isSame([1, 'x', 2, 3]);
     * that(array_insert([1, 2, 3], 'x', -1))->isSame([1, 2, 'x', 3]);
     * that(array_insert([1, 2, 3], ['a' => 'A', 'b' => 'B'], 1))->isSame([1, 'a' => 'A', 'b' => 'B', 2, 3]);
     * ```
     *
     * @param array $array 対象配列
     * @param mixed $value 挿入値
     * @param int|null $position 挿入位置
     * @return array 挿入された新しい配列
     */
    function array_insert($array, $value, $position = null)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $position = is_null($position) ? count($array) : intval($position);

        $sarray = array_splice($array, 0, $position);
        return array_merge($sarray, $value, $array);
    }
}
if (function_exists("ryunosuke\\dbml\\array_insert") && !defined("ryunosuke\\dbml\\array_insert")) {
    define("ryunosuke\\dbml\\array_insert", "ryunosuke\\dbml\\array_insert");
}

if (!isset($excluded_functions["array_all"]) && (!function_exists("ryunosuke\\dbml\\array_all") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_all"))->isInternal()))) {
    /**
     * 全要素が true になるなら true を返す（1つでも false なら false を返す）
     *
     * $callback が要求するならキーも渡ってくる。
     *
     * Example:
     * ```php
     * that(array_all([true, true]))->isTrue();
     * that(array_all([true, false]))->isFalse();
     * that(array_all([false, false]))->isFalse();
     * ```
     *
     * @param iterable $array 対象配列
     * @param callable $callback 評価クロージャ。 null なら値そのもので評価
     * @param bool|mixed $default 空配列の場合のデフォルト値
     * @return bool 全要素が true なら true
     */
    function array_all($array, $callback = null, $default = true)
    {
        if (is_empty($array)) {
            return $default;
        }

        $callback = func_user_func_array($callback);

        foreach ($array as $k => $v) {
            if (!$callback($v, $k)) {
                return false;
            }
        }
        return true;
    }
}
if (function_exists("ryunosuke\\dbml\\array_all") && !defined("ryunosuke\\dbml\\array_all")) {
    define("ryunosuke\\dbml\\array_all", "ryunosuke\\dbml\\array_all");
}

if (!isset($excluded_functions["array_order"]) && (!function_exists("ryunosuke\\dbml\\array_order") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_order"))->isInternal()))) {
    /**
     * 配列を $orders に従って並べ替える
     *
     * データベースからフェッチしたような連想配列の配列を想定しているが、スカラー配列(['key' => 'value'])にも対応している。
     * その場合 $orders に配列ではなく直値を渡せば良い。
     *
     * $orders には下記のような配列を渡す。
     * キーに空文字を渡すとそれは「キー自体」を意味する。
     *
     * ```php
     * $orders = [
     *     'col1' => true,                               // true: 昇順, false: 降順。照合は型に依存
     *     'col2' => SORT_NATURAL,                       // SORT_NATURAL, SORT_REGULAR などで照合。正数で昇順、負数で降順
     *     'col3' => ['sort', 'this', 'order'],          // 指定した配列順で昇順
     *     'col4' => function($v) {return $v;},          // クロージャを通した値で昇順。照合は返り値の型に依存
     *     'col5' => function($a, $b) {return $a - $b;}, // クロージャで比較して昇順（いわゆる比較関数を渡す）
     * ];
     * ```
     *
     * Example:
     * ```php
     * $v1 = ['id' => '1', 'no' => 'a03', 'name' => 'yyy'];
     * $v2 = ['id' => '2', 'no' => 'a4',  'name' => 'yyy'];
     * $v3 = ['id' => '3', 'no' => 'a12', 'name' => 'xxx'];
     * // name 昇順, no 自然降順
     * that(array_order([$v1, $v2, $v3], ['name' => true, 'no' => -SORT_NATURAL]))->isSame([$v3, $v2, $v1]);
     * ```
     *
     * @param array $array 対象配列
     * @param mixed $orders ソート順
     * @param bool $preserve_keys キーを保存するか。 false の場合数値キーは振り直される
     * @return array 並び替えられた配列
     */
    function array_order(array $array, $orders, $preserve_keys = false)
    {
        if (count($array) <= 1) {
            return $array;
        }

        if (!is_array($orders) || !is_hasharray($orders)) {
            $orders = [$orders];
        }

        // 配列内の位置をマップして返すクロージャ
        $position = function ($columns, $order) {
            return array_map(function ($v) use ($order) {
                $ndx = array_search($v, $order, true);
                return $ndx === false ? count($order) : $ndx;
            }, $columns);
        };

        // 全要素は舐めてられないので最初の要素を代表選手としてピックアップ
        $first = reset($array);
        $is_scalar = is_scalar($first) || is_null($first);

        // array_multisort 用の配列を生成
        $args = [];
        foreach ($orders as $key => $order) {
            if ($is_scalar) {
                $firstval = reset($array);
                $columns = $array;
            }
            else {
                if ($key !== '' && !array_key_exists($key, $first)) {
                    throw new \InvalidArgumentException("$key is undefined.");
                }
                if ($key === '') {
                    $columns = array_keys($array);
                    $firstval = reset($columns);
                }
                else {
                    $firstval = $first[$key];
                    $columns = array_column($array, $key);
                }
            }

            // bool は ASC, DESC
            if (is_bool($order)) {
                $args[] = $columns;
                $args[] = $order ? SORT_ASC : SORT_DESC;
                $args[] = is_string($firstval) ? SORT_STRING : SORT_NUMERIC;
            }
            // int は SORT_*****
            elseif (is_int($order)) {
                $args[] = $columns;
                $args[] = $order > 0 ? SORT_ASC : SORT_DESC;
                $args[] = abs($order);
            }
            // 配列はその並び
            elseif (is_array($order)) {
                $args[] = $position($columns, $order);
                $args[] = SORT_ASC;
                $args[] = SORT_NUMERIC;
            }
            // クロージャは色々
            elseif ($order instanceof \Closure) {
                $ref = new \ReflectionFunction($order);
                // 引数2個なら比較関数
                if ($ref->getNumberOfRequiredParameters() === 2) {
                    $map = $columns;
                    usort($map, $order);
                    $args[] = $position($columns, $map);
                    $args[] = SORT_ASC;
                    $args[] = SORT_NUMERIC;
                }
                // でないなら通した値で比較
                else {
                    $arg = array_map($order, $columns);
                    /** @var \ReflectionNamedType $rtype */
                    $rtype = $ref->getReturnType();
                    $type = $rtype ? $rtype->getName() : gettype(reset($arg));
                    $args[] = $arg;
                    $args[] = SORT_ASC;
                    $args[] = $type === 'string' ? SORT_STRING : SORT_NUMERIC;
                }
            }
            else {
                throw new \DomainException('$order is invalid.');
            }
        }

        // array_multisort はキーを保持しないので、ソートされる配列にキー配列を加えて後で combine する
        if ($preserve_keys) {
            $keys = array_keys($array);
            $args[] = &$array;
            $args[] = &$keys;
            array_multisort(...$args);
            return array_combine($keys, $array);
        }
        // キーを保持しないなら単純呼び出しで OK
        else {
            $args[] = &$array;
            array_multisort(...$args);
            return $array;
        }
    }
}
if (function_exists("ryunosuke\\dbml\\array_order") && !defined("ryunosuke\\dbml\\array_order")) {
    define("ryunosuke\\dbml\\array_order", "ryunosuke\\dbml\\array_order");
}

if (!isset($excluded_functions["array_pickup"]) && (!function_exists("ryunosuke\\dbml\\array_pickup") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_pickup"))->isInternal()))) {
    /**
     * キーを指定してそれだけの配列にする
     *
     * `array_intersect_key($array, array_flip($keys))` とほぼ同義。
     * 違いは Traversable を渡せることと、結果配列の順番が $keys に従うこと。
     *
     * $keys に連想配列を渡すとキーを読み替えて動作する（Example を参照）。
     *
     * Example:
     * ```php
     * $array = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
     * // a と c を取り出す
     * that(array_pickup($array, ['a', 'c']))->isSame(['a' => 'A', 'c' => 'C']);
     * // 順番は $keys 基準になる
     * that(array_pickup($array, ['c', 'a']))->isSame(['c' => 'C', 'a' => 'A']);
     * // 連想配列を渡すと読み替えて返す
     * that(array_pickup($array, ['c' => 'cX', 'a' => 'aX']))->isSame(['cX' => 'C', 'aX' => 'A']);
     * ```
     *
     * @param iterable $array 対象配列
     * @param array $keys 取り出すキー
     * @return array 新しい配列
     */
    function array_pickup($array, $keys)
    {
        $array = arrayval($array, false);

        $result = [];
        foreach (arrayval($keys, false) as $k => $key) {
            if (is_int($k)) {
                if (array_key_exists($key, $array)) {
                    $result[$key] = $array[$key];
                }
            }
            else {
                if (array_key_exists($k, $array)) {
                    $result[$key] = $array[$k];
                }
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_pickup") && !defined("ryunosuke\\dbml\\array_pickup")) {
    define("ryunosuke\\dbml\\array_pickup", "ryunosuke\\dbml\\array_pickup");
}

if (!isset($excluded_functions["array_lookup"]) && (!function_exists("ryunosuke\\dbml\\array_lookup") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_lookup"))->isInternal()))) {
    /**
     * キー保存可能な array_column
     *
     * array_column は キーを保存することが出来ないが、この関数は引数を2つだけ与えるとキーはそのままで array_column 相当の配列を返す。
     *
     * Example:
     * ```php
     * $array = [
     *     11 => ['id' => 1, 'name' => 'name1'],
     *     12 => ['id' => 2, 'name' => 'name2'],
     *     13 => ['id' => 3, 'name' => 'name3'],
     * ];
     * // 第3引数を渡せば array_column と全く同じ
     * that(array_lookup($array, 'name', 'id'))->isSame(array_column($array, 'name', 'id'));
     * that(array_lookup($array, 'name', null))->isSame(array_column($array, 'name', null));
     * // 省略すればキーが保存される
     * that(array_lookup($array, 'name'))->isSame([
     *     11 => 'name1',
     *     12 => 'name2',
     *     13 => 'name3',
     * ]);
     * ```
     *
     * @param iterable $array 対象配列
     * @param string|null $column_key 値となるキー
     * @param string|null $index_key キーとなるキー
     * @return array 新しい配列
     */
    function array_lookup($array, $column_key = null, $index_key = null)
    {
        $array = arrayval($array, false);
        if (func_num_args() === 3) {
            return array_column($array, $column_key, $index_key);
        }
        return array_combine(array_keys($array), array_column($array, $column_key));
    }
}
if (function_exists("ryunosuke\\dbml\\array_lookup") && !defined("ryunosuke\\dbml\\array_lookup")) {
    define("ryunosuke\\dbml\\array_lookup", "ryunosuke\\dbml\\array_lookup");
}

if (!isset($excluded_functions["array_uncolumns"]) && (!function_exists("ryunosuke\\dbml\\array_uncolumns") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_uncolumns"))->isInternal()))) {
    /**
     * array_columns のほぼ逆で [キー => [要素]] 配列から連想配列の配列を生成する
     *
     * $template を指定すると「それに含まれる配列かつ値がデフォルト」になる（要するに $default みたいなもの）。
     * キーがバラバラな配列を指定する場合は指定したほうが良い。が、null を指定すると最初の要素が使われるので大抵の場合は null で良い。
     *
     * Example:
     * ```php
     * that(array_uncolumns([
     *     'id'   => [1, 2],
     *     'name' => ['A', 'B'],
     * ]))->isSame([
     *     ['id' => 1, 'name' => 'A'],
     *     ['id' => 2, 'name' => 'B'],
     * ]);
     * ```
     *
     * @param array $array 対象配列
     * @param array $template 抽出要素とそのデフォルト値
     * @return array 新しい配列
     */
    function array_uncolumns($array, $template = null)
    {
        // 指定されていないなら生のまま
        if (func_num_args() === 1) {
            $template = false;
        }
        // null なら最初の要素のキー・null
        if ($template === null) {
            $template = array_fill_keys(array_keys(first_value($array)), null);
        }

        $result = [];
        foreach ($array as $key => $vals) {
            if ($template !== false) {
                $vals = array_intersect_key($vals + $template, $template);
            }
            foreach ($vals as $n => $val) {
                $result[$n][$key] = $val;
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_uncolumns") && !defined("ryunosuke\\dbml\\array_uncolumns")) {
    define("ryunosuke\\dbml\\array_uncolumns", "ryunosuke\\dbml\\array_uncolumns");
}

if (!isset($excluded_functions["array_convert"]) && (!function_exists("ryunosuke\\dbml\\array_convert") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_convert"))->isInternal()))) {
    /**
     * 配列の各要素に再帰的にコールバックを適用して変換する
     *
     * $callback は下記の仕様。
     *
     * 引数は (キー, 値, 今まで処理したキー配列) で渡ってくる。
     * 返り値は新しいキーを返す。
     *
     * - 文字列や数値を返すとそれがキーとして使われる
     * - null を返すと元のキーがそのまま使われる
     * - true を返すと数値連番が振られる
     * - false を返すとその要素は無かったことになる
     * - 配列を返すとその配列で完全に置換される
     *
     * $apply_array=false で要素が配列の場合は再帰され、コールバックが適用されない（array_walk_recursive と同じ仕様）。
     *
     * $apply_array=true だと配列かは問わず全ての要素にコールバックが適用される。
     * 配列も渡ってきてしまうのでコールバック内部で is_array 判定が必要になる場合がある。
     *
     * 「map も filter も可能でキー変更可能かつ再帰的」というとてもマッチョな関数。
     * 複雑だが実質的には「キーも設定できる array_walk_recursive」のように振る舞う（そしてそのような使い方を想定している）。
     *
     * Example:
     * ```php
     * $array = [
     *    'k1' => 'v1',
     *    'k2' => [
     *        'k21' => 'v21',
     *        'k22' => [
     *            'k221' => 'v221',
     *            'k222' => 'v222',
     *        ],
     *        'k23' => 'v23',
     *    ],
     * ];
     * // 全要素に 'prefix-' を付与する。キーには '_' をつける。ただし 'k21' はそのままとする。さらに 'k22' はまるごと伏せる。 'k23' は数値キーになる
     * $callback = function($k, &$v){
     *     if ($k === 'k21') return null;
     *     if ($k === 'k22') return false;
     *     if ($k === 'k23') return true;
     *     if (!is_array($v)) $v = "prefix-$v";
     *     return "_$k";
     * };
     * that(array_convert($array, $callback, true))->isSame([
     *     '_k1' => 'prefix-v1',
     *     '_k2' => [
     *         'k21' => 'v21',
     *         0     => 'v23',
     *     ],
     * ]);
     * ```
     *
     * @param array $array 対象配列
     * @param callable $callback 適用するコールバック
     * @param bool $apply_array 配列要素にもコールバックを適用するか
     * @return array 変換された配列
     */
    function array_convert($array, $callback, $apply_array = false)
    {
        $recursive = function (&$result, $array, $history, $callback) use (&$recursive, $apply_array) {
            $sequences = [];
            foreach ($array as $key => $value) {
                $is_array = is_array($value);
                $newkey = $key;
                // 配列で $apply_array あるいは非配列の場合にコールバック適用
                if (($is_array && $apply_array) || !$is_array) {
                    $newkey = $callback($key, $value, $history);
                }
                // 配列は置換
                if (is_array($newkey)) {
                    foreach ($newkey as $k => $v) {
                        $result[$k] = $v;
                    }
                    continue;
                }
                // false はスルー
                if ($newkey === false) {
                    continue;
                }
                // true は数値連番
                if ($newkey === true) {
                    if ($is_array) {
                        $sequences["_$key"] = $value;
                    }
                    else {
                        $sequences[] = $value;
                    }
                    continue;
                }
                // null は元のキー
                if ($newkey === null) {
                    $newkey = $key;
                }
                // 配列と非配列で代入の仕方が異なる
                if ($is_array) {
                    $history[] = $key;
                    $result[$newkey] = [];
                    $recursive($result[$newkey], $value, $history, $callback);
                    array_pop($history);
                }
                else {
                    $result[$newkey] = $value;
                }
            }
            // 数値連番は上書きを防ぐためにあとでやる
            foreach ($sequences as $key => $value) {
                if (is_string($key)) {
                    $history[] = substr($key, 1);
                    $v = [];
                    $result[] = &$v;
                    $recursive($v, $value, $history, $callback);
                    array_pop($history);
                    unset($v);
                }
                else {
                    $result[] = $value;
                }
            }
        };

        $result = [];
        $recursive($result, $array, [], $callback);
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_convert") && !defined("ryunosuke\\dbml\\array_convert")) {
    define("ryunosuke\\dbml\\array_convert", "ryunosuke\\dbml\\array_convert");
}

if (!isset($excluded_functions["array_flatten"]) && (!function_exists("ryunosuke\\dbml\\array_flatten") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_flatten"))->isInternal()))) {
    /**
     * 多階層配列をフラットに展開する
     *
     * 巷にあふれている実装と違って、 ["$pkey.$ckey" => $value] 形式の配列でも返せる。
     * $delimiter で区切り文字を指定した場合にそのようになる。
     * $delimiter = null の場合に本当の配列で返す（巷の実装と同じ）。
     *
     * Example:
     * ```php
     * $array = [
     *    'k1' => 'v1',
     *    'k2' => [
     *        'k21' => 'v21',
     *        'k22' => [
     *            'k221' => 'v221',
     *            'k222' => 'v222',
     *            'k223' => [1, 2, 3],
     *        ],
     *    ],
     * ];
     * // 区切り文字指定なし
     * that(array_flatten($array))->isSame([
     *    0 => 'v1',
     *    1 => 'v21',
     *    2 => 'v221',
     *    3 => 'v222',
     *    4 => 1,
     *    5 => 2,
     *    6 => 3,
     * ]);
     * // 区切り文字指定
     * that(array_flatten($array, '.'))->isSame([
     *    'k1'            => 'v1',
     *    'k2.k21'        => 'v21',
     *    'k2.k22.k221'   => 'v221',
     *    'k2.k22.k222'   => 'v222',
     *    'k2.k22.k223.0' => 1,
     *    'k2.k22.k223.1' => 2,
     *    'k2.k22.k223.2' => 3,
     * ]);
     * ```
     *
     * @param iterable $array 対象配列
     * @param string|\Closure|null $delimiter キーの区切り文字。 null を与えると連番になる
     * @return array フラット化された配列
     */
    function array_flatten($array, $delimiter = null)
    {
        $result = [];
        $core = function ($array, $delimiter, $parents) use (&$core, &$result) {
            foreach ($array as $k => $v) {
                $keys = $parents;
                $keys[] = $k;
                if (is_iterable($v)) {
                    $core($v, $delimiter, $keys);
                }
                else {
                    if ($delimiter === null) {
                        $result[] = $v;
                    }
                    elseif ($delimiter instanceof \Closure) {
                        $result[$delimiter($keys)] = $v;
                    }
                    else {
                        $result[implode($delimiter, $keys)] = $v;
                    }
                }
            }
        };

        $core($array, $delimiter, []);
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_flatten") && !defined("ryunosuke\\dbml\\array_flatten")) {
    define("ryunosuke\\dbml\\array_flatten", "ryunosuke\\dbml\\array_flatten");
}

if (!isset($excluded_functions["array_nest"]) && (!function_exists("ryunosuke\\dbml\\array_nest") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\array_nest"))->isInternal()))) {
    /**
     * シンプルな [キー => 値] な配列から階層配列を生成する
     *
     * 定義的に array_flatten の逆関数のような扱いになる。
     * $delimiter で階層を表現する。
     *
     * 同名とみなされるキーは上書きされるか例外が飛ぶ。具体的には Example を参照。
     *
     * Example:
     * ```php
     * // 単純な階層展開
     * $array = [
     *    'k1'            => 'v1',
     *    'k2.k21'        => 'v21',
     *    'k2.k22.k221'   => 'v221',
     *    'k2.k22.k222'   => 'v222',
     *    'k2.k22.k223.0' => 1,
     *    'k2.k22.k223.1' => 2,
     *    'k2.k22.k223.2' => 3,
     * ];
     * that(array_nest($array))->isSame([
     *    'k1' => 'v1',
     *    'k2' => [
     *        'k21' => 'v21',
     *        'k22' => [
     *            'k221' => 'v221',
     *            'k222' => 'v222',
     *            'k223' => [1, 2, 3],
     *        ],
     *    ],
     * ]);
     * // 同名になるようなキーは上書きされる
     * $array = [
     *    'k1.k2' => 'v1', // この時点で 'k1' は配列になるが・・・
     *    'k1'    => 'v2', // この時点で 'k1' は文字列として上書きされる
     * ];
     * that(array_nest($array))->isSame([
     *    'k1' => 'v2',
     * ]);
     * // 上書きすら出来ない場合は例外が飛ぶ
     * $array = [
     *    'k1'    => 'v1', // この時点で 'k1' は文字列になるが・・・
     *    'k1.k2' => 'v2', // この時点で 'k1' にインデックスアクセスすることになるので例外が飛ぶ
     * ];
     * try {
     *     array_nest($array);
     * }
     * catch (\Exception $e) {
     *     that($e)->isInstanceOf(\InvalidArgumentException::class);
     * }
     * ```
     *
     * @param iterable $array 対象配列
     * @param string $delimiter キーの区切り文字
     * @return array 階層化された配列
     */
    function array_nest($array, $delimiter = '.')
    {
        $result = [];
        foreach ($array as $k => $v) {
            $keys = explode($delimiter, $k);
            $rkeys = [];
            $tmp = &$result;
            foreach ($keys as $key) {
                $rkeys[] = $key;
                if (isset($tmp[$key]) && !is_array($tmp[$key])) {
                    throw new \InvalidArgumentException("'" . implode($delimiter, $rkeys) . "' of '$k' is already exists.");
                }
                $tmp = &$tmp[$key];
            }
            $tmp = $v;
            unset($tmp);
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\array_nest") && !defined("ryunosuke\\dbml\\array_nest")) {
    define("ryunosuke\\dbml\\array_nest", "ryunosuke\\dbml\\array_nest");
}

if (!isset($excluded_functions["date_fromto"]) && (!function_exists("ryunosuke\\dbml\\date_fromto") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\date_fromto"))->isInternal()))) {
    /**
     * 日時っぽい文字列とフォーマットを与えると取りうる範囲を返す
     *
     * 与えられた日時の最大の切り捨て日時と最小の切り上げ日時の配列を返す。
     * 日付文字列はある程度よしなに補完される（例えば "2014/12" は"2014年12月01日" と解釈されるし "12/24" は "今年12月24日" と解釈される）。
     *
     * Example:
     * ```php
     * that(date_fromto('Y/m/d H:i:s', '2010/11'))->isSame(["2010/11/01 00:00:00", "2010/12/01 00:00:00"]);
     * that(date_fromto('Y/m/d H:i:s', '2010/11/24'))->isSame(["2010/11/24 00:00:00", "2010/11/25 00:00:00"]);
     * that(date_fromto('Y/m/d H:i:s', '2010/11/24 13'))->isSame(["2010/11/24 13:00:00", "2010/11/24 14:00:00"]);
     * that(date_fromto('Y/m/d H:i:s', '2010/11/24 13:24'))->isSame(["2010/11/24 13:24:00", "2010/11/24 13:25:00"]);
     * ```
     *
     * @param string $format フォーマット。 null を与えるとタイムスタンプで返す
     * @param string $datetimestring 日時データ
     * @return array|null [from ～ to] な配列。解釈できない場合は null
     */
    function date_fromto($format, $datetimestring)
    {
        $parsed = date_parse($datetimestring);
        if (true
            && $parsed['year'] === false
            && $parsed['month'] === false
            && $parsed['day'] === false
            && $parsed['hour'] === false
            && $parsed['minute'] === false
            && $parsed['second'] === false) {
            return null;
        }

        [$date, $time] = preg_split('#[T\s　]#u', $datetimestring, -1, PREG_SPLIT_NO_EMPTY) + [0 => '', 1 => ''];
        [$y, $m, $d] = preg_split('#[^\d]+#u', $date, -1, PREG_SPLIT_NO_EMPTY) + [0 => null, 1 => null, 2 => null];
        [$h, $i, $s] = preg_split('#[^\d]+#u', $time, -1, PREG_SPLIT_NO_EMPTY) + [0 => null, 1 => null, 2 => null];

        // "2014/12" と "12/24" の区別はつかないので字数で判断
        if (strlen($y) <= 2) {
            [$y, $m, $d] = [null, $y, $m];
        }
        // 時刻区切りなし
        if (strlen($h) > 2) {
            [$h, $i, $s] = str_split($h, 2) + [0 => null, 1 => null, 2 => null];
        }

        // 文字列表現で妥当性を検証
        $strtime = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $y ?? 1000, $m ?? 1, $d ?? 1, $h ?? 1, $i ?? 1, $s ?? 1);
        $datetime = date_create_from_format('Y-m-d H:i:s', $strtime);
        if (!$datetime || $datetime->format('Y-m-d H:i:s') !== $strtime) {
            return null;
        }

        $y = $y ?? idate('Y');
        $ld = $d ?? idate('t', mktime(0, 0, 0, $m ?? 12, 1, $y));

        $min = mktime($h ?? 0, $i ?? 0, $s ?? 0, $m ?? 1, $d ?? 1, $y);
        $max = mktime($h ?? 23, $i ?? 59, $s ?? 59, $m ?? 12, $d ?? $ld, $y) + 1;
        if ($format === null) {
            return [$min, $max];
        }
        return [date($format, $min), date($format, $max)];
    }
}
if (function_exists("ryunosuke\\dbml\\date_fromto") && !defined("ryunosuke\\dbml\\date_fromto")) {
    define("ryunosuke\\dbml\\date_fromto", "ryunosuke\\dbml\\date_fromto");
}

if (!isset($excluded_functions["file_set_contents"]) && (!function_exists("ryunosuke\\dbml\\file_set_contents") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\file_set_contents"))->isInternal()))) {
    /**
     * ディレクトリも掘る file_put_contents
     *
     * 書き込みは一時ファイルと rename を使用してアトミックに行われる。
     *
     * Example:
     * ```php
     * file_set_contents(sys_get_temp_dir() . '/not/filename.ext', 'hoge');
     * that(file_get_contents(sys_get_temp_dir() . '/not/filename.ext'))->isSame('hoge');
     * ```
     *
     * @param string $filename 書き込むファイル名
     * @param string $data 書き込む内容
     * @param int $umask ディレクトリを掘る際の umask
     * @return int 書き込まれたバイト数
     */
    function file_set_contents($filename, $data, $umask = 0002)
    {
        if (func_num_args() === 2) {
            $umask = umask();
        }

        $filename = path_normalize($filename);

        if (!is_dir($dirname = dirname($filename))) {
            if (!@mkdir_p($dirname, $umask)) {
                throw new \RuntimeException("failed to mkdir($dirname)");
            }
        }

        $tempnam = tempnam($dirname, 'tmp');
        if (($result = file_put_contents($tempnam, $data)) !== false) {
            if (rename($tempnam, $filename)) {
                @chmod($filename, 0666 & ~$umask);
                return $result;
            }
            unlink($tempnam);
        }
        return false;
    }
}
if (function_exists("ryunosuke\\dbml\\file_set_contents") && !defined("ryunosuke\\dbml\\file_set_contents")) {
    define("ryunosuke\\dbml\\file_set_contents", "ryunosuke\\dbml\\file_set_contents");
}

if (!isset($excluded_functions["mkdir_p"]) && (!function_exists("ryunosuke\\dbml\\mkdir_p") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\mkdir_p"))->isInternal()))) {
    /**
     * ディレクトリを再帰的に掘る
     *
     * 既に存在する場合は何もしない（エラーも出さない）。
     *
     * @param string $dirname ディレクトリ名
     * @param int $umask ディレクトリを掘る際の umask
     * @return bool 作成したら true
     */
    function mkdir_p($dirname, $umask = 0002)
    {
        if (func_num_args() === 1) {
            $umask = umask();
        }

        if (file_exists($dirname)) {
            return false;
        }

        return mkdir($dirname, 0777 & (~$umask), true);
    }
}
if (function_exists("ryunosuke\\dbml\\mkdir_p") && !defined("ryunosuke\\dbml\\mkdir_p")) {
    define("ryunosuke\\dbml\\mkdir_p", "ryunosuke\\dbml\\mkdir_p");
}

if (!isset($excluded_functions["fnmatch_or"]) && (!function_exists("ryunosuke\\dbml\\fnmatch_or") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\fnmatch_or"))->isInternal()))) {
    /**
     * fnmatch の OR 版
     *
     * $patterns のうちどれか一つでもマッチしたら true を返す。
     * $patterns が空だと例外を投げる。
     *
     * Example:
     * ```php
     * // aaa にマッチするので true
     * that(fnmatch_or(['*aaa*', '*bbb*'], 'aaaX'))->isTrue();
     * // どれともマッチしないので false
     * that(fnmatch_or(['*aaa*', '*bbb*'], 'cccX'))->isFalse();
     * ```
     *
     * @param array|string $patterns パターン配列（単一文字列可）
     * @param string $string 調べる文字列
     * @param int $flags FNM_***
     * @return bool どれかにマッチしたら true
     */
    function fnmatch_or($patterns, $string, $flags = 0)
    {
        $patterns = is_iterable($patterns) ? $patterns : [$patterns];
        if (is_empty($patterns)) {
            throw new \InvalidArgumentException('$patterns must be not empty.');
        }

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $string, $flags)) {
                return true;
            }
        }
        return false;
    }
}
if (function_exists("ryunosuke\\dbml\\fnmatch_or") && !defined("ryunosuke\\dbml\\fnmatch_or")) {
    define("ryunosuke\\dbml\\fnmatch_or", "ryunosuke\\dbml\\fnmatch_or");
}

if (!isset($excluded_functions["path_normalize"]) && (!function_exists("ryunosuke\\dbml\\path_normalize") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\path_normalize"))->isInternal()))) {
    /**
     * パスを正規化する
     *
     * 具体的には ./ や ../ を取り除いたり連続したディレクトリ区切りをまとめたりする。
     * realpath ではない。のでシンボリックリンクの解決などはしない。その代わりファイルが存在しなくても使用することができる。
     *
     * Example:
     * ```php
     * $DS = DIRECTORY_SEPARATOR;
     * that(path_normalize('/path/to/something'))->isSame("{$DS}path{$DS}to{$DS}something");
     * that(path_normalize('/path/through/../something'))->isSame("{$DS}path{$DS}something");
     * that(path_normalize('./path/current/./through/../something'))->isSame("path{$DS}current{$DS}something");
     * ```
     *
     * @param string $path パス文字列
     * @return string 正規化されたパス
     */
    function path_normalize($path)
    {
        $ds = '/';
        if (DIRECTORY_SEPARATOR === '\\') {
            $ds .= '\\\\';
        }

        $result = [];
        foreach (preg_split("#[$ds]#u", $path) as $n => $part) {
            if ($n > 0 && $part === '') {
                continue;
            }
            if ($part === '.') {
                continue;
            }
            if ($part === '..') {
                if (empty($result)) {
                    throw new \InvalidArgumentException("'$path' is invalid as path string.");
                }
                array_pop($result);
                continue;
            }
            $result[] = $part;
        }
        return implode(DIRECTORY_SEPARATOR, $result);
    }
}
if (function_exists("ryunosuke\\dbml\\path_normalize") && !defined("ryunosuke\\dbml\\path_normalize")) {
    define("ryunosuke\\dbml\\path_normalize", "ryunosuke\\dbml\\path_normalize");
}

if (!isset($excluded_functions["delegate"]) && (!function_exists("ryunosuke\\dbml\\delegate") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\delegate"))->isInternal()))) {
    /**
     * 指定 callable を指定クロージャで実行するクロージャを返す
     *
     * ほぼ内部向けで外から呼ぶことはあまり想定していない。
     *
     * @param \Closure $invoker クロージャを実行するためのクロージャ（実処理）
     * @param callable $callable 最終的に実行したいクロージャ
     * @param int $arity 引数の数
     * @return callable $callable を実行するクロージャ
     */
    function delegate($invoker, $callable, $arity = null)
    {
        $arity = $arity ?? parameter_length($callable, true, true);

        if (reflect_callable($callable)->isInternal()) {
            static $cache = [];
            $cache[$arity] = $cache[$arity] ?? evaluate('return new class()
            {
                private $invoker, $callable;

                public function spawn($invoker, $callable)
                {
                    $that = clone($this);
                    $that->invoker = $invoker;
                    $that->callable = $callable;
                    return $that;
                }

                public function __invoke(' . implode(',', is_infinite($arity)
                        ? ['...$_']
                        : array_map(function ($v) { return '$_' . $v; }, array_keys(array_fill(1, $arity, null)))
                    ) . ')
                {
                    return ($this->invoker)($this->callable, func_get_args());
                }
            };');
            return $cache[$arity]->spawn($invoker, $callable);
        }

        switch (true) {
            case $arity === 0:
                return function () use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            case $arity === 1:
                return function ($_1) use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            case $arity === 2:
                return function ($_1, $_2) use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            case $arity === 3:
                return function ($_1, $_2, $_3) use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            case $arity === 4:
                return function ($_1, $_2, $_3, $_4) use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            case $arity === 5:
                return function ($_1, $_2, $_3, $_4, $_5) use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            case is_infinite($arity):
                return function (...$_) use ($invoker, $callable) { return $invoker($callable, func_get_args()); };
            default:
                $args = implode(',', array_map(function ($v) { return '$_' . $v; }, array_keys(array_fill(1, $arity, null))));
                $stmt = 'return function (' . $args . ') use ($invoker, $callable) { return $invoker($callable, func_get_args()); };';
                return eval($stmt);
        }
    }
}
if (function_exists("ryunosuke\\dbml\\delegate") && !defined("ryunosuke\\dbml\\delegate")) {
    define("ryunosuke\\dbml\\delegate", "ryunosuke\\dbml\\delegate");
}

if (!isset($excluded_functions["nbind"]) && (!function_exists("ryunosuke\\dbml\\nbind") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\nbind"))->isInternal()))) {
    /**
     * $callable の指定位置に引数を束縛したクロージャを返す
     *
     * Example:
     * ```php
     * $bind = nbind('sprintf', 2, 'X');
     * that($bind('%s%s%s', 'N', 'N'))->isSame('NXN');
     * ```
     *
     * @param callable $callable 対象 callable
     * @param int $n 挿入する引数位置
     * @param mixed $variadic 本来の引数（可変引数）
     * @return callable 束縛したクロージャ
     */
    function nbind($callable, $n, ...$variadic)
    {
        return delegate(function ($callable, $args) use ($variadic, $n) {
            return $callable(...array_insert($args, $variadic, $n));
        }, $callable, parameter_length($callable, true, true) - count($variadic));
    }
}
if (function_exists("ryunosuke\\dbml\\nbind") && !defined("ryunosuke\\dbml\\nbind")) {
    define("ryunosuke\\dbml\\nbind", "ryunosuke\\dbml\\nbind");
}

if (!isset($excluded_functions["rbind"]) && (!function_exists("ryunosuke\\dbml\\rbind") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\rbind"))->isInternal()))) {
    /**
     * $callable の最右に引数を束縛した callable を返す
     *
     * Example:
     * ```php
     * $bind = rbind('sprintf', 'X');
     * that($bind('%s%s', 'N'))->isSame('NX');
     * ```
     *
     * @param callable $callable 対象 callable
     * @param mixed $variadic 本来の引数（可変引数）
     * @return callable 束縛したクロージャ
     */
    function rbind($callable, ...$variadic)
    {
        return nbind(...array_insert(func_get_args(), null, 1));
    }
}
if (function_exists("ryunosuke\\dbml\\rbind") && !defined("ryunosuke\\dbml\\rbind")) {
    define("ryunosuke\\dbml\\rbind", "ryunosuke\\dbml\\rbind");
}

if (!isset($excluded_functions["reflect_callable"]) && (!function_exists("ryunosuke\\dbml\\reflect_callable") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\reflect_callable"))->isInternal()))) {
    /**
     * callable から ReflectionFunctionAbstract を生成する
     *
     * Example:
     * ```php
     * that(reflect_callable('sprintf'))->isInstanceOf(\ReflectionFunction::class);
     * that(reflect_callable('\Closure::bind'))->isInstanceOf(\ReflectionMethod::class);
     * ```
     *
     * @param callable $callable 対象 callable
     * @return \ReflectionFunction|\ReflectionMethod リフレクションインスタンス
     */
    function reflect_callable($callable)
    {
        // callable チェック兼 $call_name 取得
        if (!is_callable($callable, true, $call_name)) {
            throw new \InvalidArgumentException("'$call_name' is not callable");
        }

        if ($callable instanceof \Closure || strpos($call_name, '::') === false) {
            return new \ReflectionFunction($callable);
        }
        else {
            [$class, $method] = explode('::', $call_name, 2);
            // for タイプ 5: 相対指定による静的クラスメソッドのコール (PHP 5.3.0 以降)
            if (strpos($method, 'parent::') === 0) {
                [, $method] = explode('::', $method);
                return (new \ReflectionClass($class))->getParentClass()->getMethod($method);
            }
            return new \ReflectionMethod($class, $method);
        }
    }
}
if (function_exists("ryunosuke\\dbml\\reflect_callable") && !defined("ryunosuke\\dbml\\reflect_callable")) {
    define("ryunosuke\\dbml\\reflect_callable", "ryunosuke\\dbml\\reflect_callable");
}

if (!isset($excluded_functions["parameter_length"]) && (!function_exists("ryunosuke\\dbml\\parameter_length") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\parameter_length"))->isInternal()))) {
    /**
     * callable の引数の数を返す
     *
     * クロージャはキャッシュされない。毎回リフレクションを生成し、引数の数を調べてそれを返す。
     * （クロージャには一意性がないので key-value なキャッシュが適用できない）。
     * ので、ループ内で使ったりすると目に見えてパフォーマンスが低下するので注意。
     *
     * Example:
     * ```php
     * // trim の引数は2つ
     * that(parameter_length('trim'))->isSame(2);
     * // trim の必須引数は1つ
     * that(parameter_length('trim', true))->isSame(1);
     * ```
     *
     * @param callable $callable 対象 callable
     * @param bool $require_only true を渡すと必須パラメータの数を返す
     * @param bool $thought_variadic 可変引数を考慮するか。 true を渡すと可変引数の場合に無限長を返す
     * @return int 引数の数
     */
    function parameter_length($callable, $require_only = false, $thought_variadic = false)
    {
        // クロージャの $call_name には一意性がないのでキャッシュできない（spl_object_hash でもいいが、かなり重複するので完全ではない）
        if ($callable instanceof \Closure) {
            /** @var \ReflectionFunctionAbstract $ref */
            $ref = reflect_callable($callable);
            if ($thought_variadic && $ref->isVariadic()) {
                return INF;
            }
            elseif ($require_only) {
                return $ref->getNumberOfRequiredParameters();
            }
            else {
                return $ref->getNumberOfParameters();
            }
        }

        // $call_name 取得
        is_callable($callable, false, $call_name);

        $cache = cache($call_name, function () use ($callable) {
            /** @var \ReflectionFunctionAbstract $ref */
            $ref = reflect_callable($callable);
            return [
                '00' => $ref->getNumberOfParameters(),
                '01' => $ref->isVariadic() ? INF : $ref->getNumberOfParameters(),
                '10' => $ref->getNumberOfRequiredParameters(),
                '11' => $ref->isVariadic() ? INF : $ref->getNumberOfRequiredParameters(),
            ];
        }, __FUNCTION__);
        return $cache[(int) $require_only . (int) $thought_variadic];
    }
}
if (function_exists("ryunosuke\\dbml\\parameter_length") && !defined("ryunosuke\\dbml\\parameter_length")) {
    define("ryunosuke\\dbml\\parameter_length", "ryunosuke\\dbml\\parameter_length");
}

if (!isset($excluded_functions["func_user_func_array"]) && (!function_exists("ryunosuke\\dbml\\func_user_func_array") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\func_user_func_array"))->isInternal()))) {
    /**
     * パラメータ定義数に応じて呼び出し引数を可変にしてコールする
     *
     * デフォルト引数はカウントされない。必須パラメータの数で呼び出す。
     *
     * $callback に null を与えると例外的に「第1引数を返すクロージャ」を返す。
     *
     * php の標準関数は定義数より多い引数を投げるとエラーを出すのでそれを抑制したい場合に使う。
     *
     * Example:
     * ```php
     * // strlen に2つの引数を渡してもエラーにならない
     * $strlen = func_user_func_array('strlen');
     * that($strlen('abc', null))->isSame(3);
     * ```
     *
     * @param callable $callback 呼び出すクロージャ
     * @return callable 引数ぴったりで呼び出すクロージャ
     */
    function func_user_func_array($callback)
    {
        // null は第1引数を返す特殊仕様
        if ($callback === null) {
            return function ($v) { return $v; };
        }
        // クロージャはユーザ定義しかありえないので調べる必要がない
        if ($callback instanceof \Closure) {
            // と思ったが、\Closure::fromCallable で作成されたクロージャは内部属性が伝播されるようなので除外
            if (reflect_callable($callback)->isUserDefined()) {
                return $callback;
            }
        }

        // 上記以外は「引数ぴったりで削ぎ落としてコールするクロージャ」を返す
        $plength = parameter_length($callback, true, true);
        return delegate(function ($callback, $args) use ($plength) {
            if (is_infinite($plength)) {
                return $callback(...$args);
            }
            return $callback(...array_slice($args, 0, $plength));
        }, $callback, $plength);
    }
}
if (function_exists("ryunosuke\\dbml\\func_user_func_array") && !defined("ryunosuke\\dbml\\func_user_func_array")) {
    define("ryunosuke\\dbml\\func_user_func_array", "ryunosuke\\dbml\\func_user_func_array");
}

if (!isset($excluded_functions["func_method"]) && (!function_exists("ryunosuke\\dbml\\func_method") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\func_method"))->isInternal()))) {
    /**
     * 指定メソッドを呼び出すクロージャを返す
     *
     * この関数を呼ぶとメソッドのクロージャを返す。
     * そのクロージャにオブジェクトを与えて呼び出すとそれはメソッド呼び出しとなる。
     *
     * オプションでデフォルト引数を設定できる（Example を参照）。
     *
     * Example:
     * ```php
     * // 与えられた引数を結合して返すメソッド hoge を持つクラス
     * $object = new class()
     * {
     *     function hoge(...$args) { return implode(',', $args); }
     * };
     * // hoge を呼び出すクロージャ
     * $hoge = func_method('hoge');
     * // ↑を使用して $object の hoge を呼び出す
     * that($hoge($object, 1, 2, 3))->isSame('1,2,3');
     *
     * // デフォルト値付きで hoge を呼び出すクロージャ
     * $hoge789 = func_method('hoge', 7, 8, 9);
     * // ↑を使用して $object の hoge を呼び出す（引数指定してるので結果は同じ）
     * that($hoge789($object, 1, 2, 3))->isSame('1,2,3');
     * // 同上（一部デフォルト値）
     * that($hoge789($object, 1, 2))->isSame('1,2,9');
     * // 同上（全部デフォルト値）
     * that($hoge789($object))->isSame('7,8,9');
     * ```
     *
     * @param string $methodname メソッド名
     * @param array $defaultargs メソッドのデフォルト引数
     * @return \Closure メソッドを呼び出すクロージャ
     */
    function func_method($methodname, ...$defaultargs)
    {
        if ($methodname === '__construct') {
            return function ($object, ...$args) use ($defaultargs) {
                return new $object(...$args + $defaultargs);
            };
        }
        return function ($object, ...$args) use ($methodname, $defaultargs) {
            return ([$object, $methodname])(...$args + $defaultargs);
        };
    }
}
if (function_exists("ryunosuke\\dbml\\func_method") && !defined("ryunosuke\\dbml\\func_method")) {
    define("ryunosuke\\dbml\\func_method", "ryunosuke\\dbml\\func_method");
}

if (!isset($excluded_functions["sql_quote"]) && (!function_exists("ryunosuke\\dbml\\sql_quote") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\sql_quote"))->isInternal()))) {
    /**
     * ものすごく雑に値をクオートする
     *
     * 非常に荒くアドホックに実装しているのでこの関数で得られた値で**実際に実行してはならない**。
     * あくまでログ出力やデバッグ用途で視認性を高める目的である。
     *
     * - null は NULL になる
     * - 数字はそのまま数字になる
     * - bool は 0 or 1 になる
     * - それ以外は addcslashes される
     *
     * Example:
     * ```php
     * that(sql_quote(null))->isSame('NULL');
     * that(sql_quote(123))->isSame(123);
     * that(sql_quote(true))->isSame(1);
     * that(sql_quote("hoge"))->isSame("'hoge'");
     * ```
     *
     * @param mixed $value クオートする値
     * @return mixed クオートされた値
     */
    function sql_quote($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return (int) $value;
        }
        return "'" . addcslashes((string) $value, "\0\e\f\n\r\t\v'\\") . "'";
    }
}
if (function_exists("ryunosuke\\dbml\\sql_quote") && !defined("ryunosuke\\dbml\\sql_quote")) {
    define("ryunosuke\\dbml\\sql_quote", "ryunosuke\\dbml\\sql_quote");
}

if (!isset($excluded_functions["sql_bind"]) && (!function_exists("ryunosuke\\dbml\\sql_bind") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\sql_bind"))->isInternal()))) {
    /**
     * ものすごく雑に SQL に値を埋め込む
     *
     * 非常に荒くアドホックに実装しているのでこの関数で得られた SQL を**実際に実行してはならない**。
     * あくまでログ出力やデバッグ用途で視認性を高める目的である。
     *
     * プレースホルダは ? か :alnum で混在していても良い。
     *
     * Example:
     * ```php
     * that(sql_bind('select ?', 1))->isSame("select 1");
     * that(sql_bind('select :hoge', ['hoge' => 'hoge']))->isSame("select 'hoge'");
     * that(sql_bind('select ?, :hoge', [1, 'hoge' => 'hoge']))->isSame("select 1, 'hoge'");
     * ```
     *
     * @param string $sql 値を埋め込む SQL
     * @param array|mixed $values 埋め込む値
     * @return mixed 値が埋め込まれた SQL
     */
    function sql_bind($sql, $values)
    {
        $embed = [];
        foreach (arrayval($values, false) as $k => $v) {
            if (is_int($k)) {
                $embed['?'][] = sql_quote($v);
            }
            else {
                $embed[":$k"] = sql_quote($v);
            }
        }

        return str_embed($sql, $embed, [
            "'"   => "'",
            '"'   => '"',
            '-- ' => "\n",
            '/*'  => "*/",
        ]);
    }
}
if (function_exists("ryunosuke\\dbml\\sql_bind") && !defined("ryunosuke\\dbml\\sql_bind")) {
    define("ryunosuke\\dbml\\sql_bind", "ryunosuke\\dbml\\sql_bind");
}

if (!isset($excluded_functions["sql_format"]) && (!function_exists("ryunosuke\\dbml\\sql_format") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\sql_format"))->isInternal()))) {
    /**
     * ものすごく雑に SQL を整形する
     *
     * 非常に荒くアドホックに実装しているのでこの関数で得られた SQL を**実際に実行してはならない**。
     * あくまでログ出力やデバッグ用途で視認性を高める目的である。
     *
     * JOIN 句は FROM 句とみなさず、別句として処理する。
     * AND と && は微妙に処理が異なる。 AND は改行されるが && は改行されない（OR と || も同様）。
     *
     * @param string $sql 整形する SQL
     * @param array $options 整形オプション
     * @return string 整形された SQL
     */
    function sql_format($sql, $options = [])
    {
        static $keywords;
        $keywords = $keywords ?? array_flip(KEYWORDS);

        $options += [
            // インデント文字
            'indent'    => "  ",
            // 括弧の展開レベル
            'nestlevel' => 1,
            // キーワードの大文字/小文字可変換（true だと大文字化。false だと小文字化。あるいは 'ucfirst' 等の文字列関数を直接指定する。クロージャでも良い）
            'case'      => null,
            // シンタックス装飾（true だと SAPI に基づいてよしなに。"html", "cli" だと SAPI を明示的に指定。クロージャだと直接コール）
            'highlight' => null,
            // 最大折返し文字数（未実装）
            'wrapsize'  => false,
        ];
        if ($options['case'] === true) {
            $options['case'] = 'strtoupper';
        }
        elseif ($options['case'] === false) {
            $options['case'] = 'strtolower';
        }

        if ($options['highlight'] === true) {
            $options['highlight'] = php_sapi_name() === 'cli' ? 'cli' : 'html';
        }
        if (is_string($options['highlight'])) {
            $rules = [
                'cli'  => [
                    'KEYWORD' => function ($token) { return "\e[1m" . $token . "\e[m"; },
                    'COMMENT' => function ($token) { return "\e[33m" . $token . "\e[m"; },
                    'STRING'  => function ($token) { return "\e[31m" . $token . "\e[m"; },
                    'NUMBER'  => function ($token) { return "\e[36m" . $token . "\e[m"; },
                ],
                'html' => [
                    'KEYWORD' => function ($token) { return "<span style='font-weight:bold;'>" . htmlspecialchars($token) . "</span>"; },
                    'COMMENT' => function ($token) { return "<span style='color:#FF8000;'>" . htmlspecialchars($token) . "</span>"; },
                    'STRING'  => function ($token) { return "<span style='color:#DD0000;'>" . htmlspecialchars($token) . "</span>"; },
                    'NUMBER'  => function ($token) { return "<span style='color:#0000BB;'>" . htmlspecialchars($token) . "</span>"; },
                ],
            ];
            $rule = $rules[$options['highlight']] ?? throws(new \InvalidArgumentException('highlight must be "cli" or "html".'));
            $options['highlight'] = function ($token, $ttype) use ($keywords, $rule) {
                switch (true) {
                    case isset($keywords[strtoupper($token)]):
                        return $rule['KEYWORD']($token);
                    case in_array($ttype, [T_COMMENT, T_DOC_COMMENT]):
                        return $rule['COMMENT']($token);
                    case in_array($ttype, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE]):
                        return $rule['STRING']($token);
                    case in_array($ttype, [T_LNUMBER, T_DNUMBER]):
                        return $rule['NUMBER']($token);
                }
                return $token;
            };
        }
        $options['syntaxer'] = function ($token, $ttype) use ($options, $keywords) {
            if ($options['case'] && isset($keywords[strtoupper($token)])) {
                $token = $options['case']($token);
            }
            if ($options['highlight']) {
                $token = $options['highlight']($token, $ttype);
            }
            return $token;
        };

        // 構文解析も先読みもない素朴な実装なので、特定文字列をあとから置換するための目印文字列
        $MARK = "{:RM";
        while (strpos($sql, $MARK) !== false) {
            $MARK .= rand(1000, 9999);
        }
        $MARK_R = "{$MARK}_R:}";   // \r マーク
        $MARK_N = "{$MARK}_N:}";   // \n マーク
        $MARK_BR = "{$MARK}_BR:}"; // 改行マーク
        $MARK_CS = "{$MARK}_CS:}"; // コメント開始マーク
        $MARK_CE = "{$MARK}_CE:}"; // コメント終了マーク
        $MARK_NT = "{$MARK}_NT:}"; // インデントマーク
        $MARK_SP = "{$MARK}_SP:}"; // スペースマーク
        $MARK_PT = "{$MARK}_PT:}"; // 括弧ネストマーク

        // 字句にバラす（シンタックスが php に似ているので token_get_all で大幅にサボることができる）
        $tokens = [];
        $comment = '';
        foreach (token_get_all("<?php $sql") as $token) {
            // トークンは配列だったり文字列だったりするので -1 トークンとして配列に正規化
            if (is_string($token)) {
                $token = [-1, $token];
            }

            // パースのために無理やり <?php を付けているので無視
            if ($token[0] === T_OPEN_TAG) {
                continue;
            }
            // '--' は php ではデクリメントだが sql ではコメントなので特別扱いする
            elseif ($token[0] === T_DEC) {
                $comment = $token[1];
            }
            // 改行は '--' コメントの終わり
            elseif ($comment && in_array($token[0], [T_WHITESPACE, T_COMMENT], true) && strpos($token[1], "\n") !== false) {
                $tokens[] = [T_COMMENT, $comment . $token[1]];
                $comment = '';
            }
            // コメント中はコメントに格納する
            elseif ($comment) {
                $comment .= $token[1];
            }
            // 上記以外はただのトークンとして格納する
            else {
                // `string` のような文字列は T_ENCAPSED_AND_WHITESPACE として得られる（ただし ` がついていないので付与）
                if ($token[0] === T_ENCAPSED_AND_WHITESPACE) {
                    $tokens[] = [$token[0], "`{$token[1]}`"];
                }
                elseif ($token[0] !== T_WHITESPACE && $token[1] !== '`') {
                    $tokens[] = [$token[0], $token[1]];
                }
            }
        }

        // コメント以外の前後のトークンを返すクロージャ
        $seek = function ($start, $step) use ($tokens) {
            $comments = [];
            for ($n = 1; ; $n++) {
                $index = $start + $n * $step;
                if (!isset($tokens[$index])) {
                    break;
                }
                $token = $tokens[$index];
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    $comments[] = trim($token[1]);
                }
                else {
                    return [$index, trim($token[1]), $comments];
                }
            }
            return [$start, '', $comments];
        };

        $interpret = function (&$index = -1) use (&$interpret, $MARK_R, $MARK_N, $MARK_BR, $MARK_CS, $MARK_CE, $MARK_NT, $MARK_SP, $MARK_PT, $tokens, $options, $seek) {
            $index++;
            $beginning = true; // クエリの冒頭か
            $context = '';     // SELECT, INSERT などの大分類
            $subcontext = '';  // SET, VALUES などのサブ分類
            $modifier = '';    // RIGHT などのキーワード修飾語
            $firstcol = null;  // SELECT における最初の列か

            $result = [];
            for ($token_length = count($tokens); $index < $token_length; $index++) {
                $ttype = $tokens[$index][0];
                $token = trim($tokens[$index][1]);

                $virttoken = $options['syntaxer']($token, $ttype);
                $uppertoken = strtoupper($token);

                // 最終的なインデントは「改行＋スペース」で行うのでリテラル内に改行があるとそれもインデントされてしまうので置換して逃がす
                $token = strtr($token, [
                    "\r" => $MARK_R,
                    "\n" => $MARK_N,
                ]);

                // SELECT の直後には DISTINCT などのオプションが来ることがあるので特別扱い
                if ($context === 'SELECT' && $firstcol) {
                    if (!in_array($uppertoken, ['DISTINCT', 'DISTINCTROW', 'STRAIGHT_JOIN'], true) && !preg_match('#^SQL_#i', $uppertoken)) {
                        $firstcol = false;
                        $result[] = $MARK_BR;
                    }
                }

                // コメントは特別扱いでただ付け足すだけ
                if ($ttype === T_COMMENT || $ttype === T_DOC_COMMENT) {
                    $result[] = ($beginning ? '' : $MARK_CS) . $virttoken . $MARK_CE . $MARK_BR;
                    continue;
                }
                $beginning = false;

                switch ($uppertoken) {
                    default:
                        _DEFAULT:
                        $prev = $seek($index, -1)[1];
                        $next = $seek($index, +1)[1];

                        // "tablename. columnname" になってしまう
                        // "@ var" になってしまう
                        // ": holder" になってしまう
                        if ($prev !== '.' && $prev !== '@' && $prev !== ':') {
                            $result[] = $MARK_SP;
                        }

                        $result[] = $virttoken;

                        // "tablename .columnname" になってしまう
                        // "columnname ," になってしまう
                        // mysql において関数呼び出し括弧の前に空白は許されない
                        // ただし、関数呼び出しではなく記号の場合はスペースを入れたい（ colname = (SELECT ～) など）
                        if (($next !== '.' && $next !== ',' && $next !== '(') || ($next === '(' && !preg_match('#^[a-z0-9_"\'`]+$#i', $token))) {
                            $result[] = $MARK_SP;
                        }
                        break;
                    case "@":
                    case ":":
                        $result[] = $MARK_SP . $virttoken;
                        break;
                    case ";":
                        $result[] = $MARK_BR . $virttoken . $MARK_BR;
                        break;
                    case ".":
                        $result[] = $virttoken;
                        break;
                    case ",":
                        $result[] = $virttoken . $MARK_BR;
                        break;
                    case "WITH":
                        $result[] = $MARK_BR . $virttoken . $MARK_SP;
                        $subcontext = $uppertoken;
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case "BETWEEN":
                        $subcontext = $uppertoken;
                        goto _DEFAULT;
                    case "CREATE":
                    case "ALTER":
                    case "DROP":
                        $result[] = $MARK_SP . $virttoken . $MARK_SP;
                        $context = $uppertoken;
                        break;
                    case "TABLE":
                        // CREATE TABLE tablename は括弧があるので何もしなくて済むが、
                        // ALTER TABLE tablename は括弧がなく ADD などで始まるので特別分岐
                        [$index, $name, $comments] = $seek($index, +1);
                        $result[] = $MARK_SP . $virttoken . $MARK_SP . ($MARK_SP . implode('', $comments) . $MARK_CE) . $name . $MARK_SP;
                        if ($context !== 'CREATE' && $context !== 'DROP') {
                            $result[] = $MARK_BR;
                        }
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case "AND":
                        // BETWEEN A AND B と論理演算子の AND が競合するので分岐後にフォールスルー
                        if ($subcontext === 'BETWEEN') {
                            $result[] = $MARK_SP . $virttoken . $MARK_SP;
                            $subcontext = '';
                            break;
                        }
                    case "OR":
                    case "XOR":
                        $result[] = $MARK_SP . $MARK_BR . $MARK_NT . $virttoken . $MARK_SP;
                        break;
                    case "UNION":
                    case "EXCEPT":
                    case "INTERSECT":
                        $result[] = $MARK_BR . $virttoken . $MARK_SP;
                        $result[] = $MARK_BR;
                        break;
                    case "BY":
                    case "ALL":
                        $result[] = $MARK_SP . $virttoken . array_pop($result);
                        break;
                    case "SELECT":
                        if ($context === 'INSERT') {
                            $result[] = $MARK_BR;
                        }
                        $result[] = $virttoken;
                        $context = $uppertoken;
                        $firstcol = true;
                        break;
                    case "LEFT":
                        /** @noinspection PhpMissingBreakStatementInspection */
                    case "RIGHT":
                        // 例えば LEFT や RIGHT は関数呼び出しの場合もあるので分岐後にフォールスルー
                        if ($seek($index, +1)[1] === '(') {
                            goto _DEFAULT;
                        }
                    case "CROSS":
                    case "INNER":
                    case "OUTER":
                        $modifier .= $virttoken . $MARK_SP;
                        break;
                    case "FROM":
                    case "JOIN":
                    case "WHERE":
                    case "HAVING":
                    case "GROUP":
                    case "ORDER":
                    case "LIMIT":
                    case "OFFSET":
                        $result[] = $MARK_BR . $modifier . $virttoken;
                        $result[] = $MARK_BR; // のちの BY のために結合はせず後ろに入れるだけにする
                        $modifier = '';
                        break;
                    case "FOR":
                    case "LOCK":
                        $result[] = $MARK_BR . $virttoken . $MARK_SP;
                        break;
                    case "ON":
                        // ON は ON でも mysql の ON DUPLICATED かもしれない（pgsql の ON CONFLICT も似たようなコンテキスト）
                        $name = $seek($index, +1)[1];
                        if (in_array(strtoupper($name), ['DUPLICATE', 'CONFLICT'], true)) {
                            $result[] = $MARK_BR;
                            $subcontext = '';
                        }
                        else {
                            $result[] = $MARK_SP;
                        }
                        $result[] = $virttoken . $MARK_SP;
                        break;
                    case "SET":
                        $result[] = $MARK_BR . $virttoken . $MARK_BR;
                        $subcontext = $uppertoken;
                        break;
                    case "INSERT":
                    case "REPLACE":
                        $result[] = $virttoken . $MARK_SP;
                        $context = "INSERT"; // 構文的には INSERT と同じ
                        break;
                    case "INTO":
                        $result[] = $virttoken;
                        if ($context === "INSERT") {
                            $result[] = $MARK_BR;
                        }
                        break;
                    case "VALUES":
                        if ($context === "UPDATE") {
                            $result[] = $MARK_SP . $virttoken;
                        }
                        else {
                            $result[] = $MARK_BR . $virttoken . $MARK_BR;
                        }
                        break;
                    case "REFERENCES":
                        $result[] = $MARK_SP . $virttoken . $MARK_SP;
                        $subcontext = $uppertoken;
                        break;
                    case "UPDATE":
                    case "DELETE":
                        $result[] = $MARK_SP . $virttoken;
                        if ($subcontext !== 'REFERENCES') {
                            $result[] = $MARK_BR;
                            $context = $uppertoken;
                        }
                        break;
                    case "WHEN":
                    case "ELSE":
                        $result[] = $MARK_BR . $MARK_NT . $virttoken . $MARK_SP;
                        break;
                    case "CASE":
                        $parts = $interpret($index);
                        $parts = str_replace($MARK_BR, $MARK_BR . $MARK_NT, $parts);
                        $result[] = $MARK_NT . $virttoken . $MARK_SP . $parts;
                        break;
                    case "END":
                        $result[] = $MARK_BR . $virttoken;
                        break 2;
                    case "(":
                        $current = $index;
                        $parts = $MARK_BR . $interpret($index);

                        // コメントを含まない指定ネストレベル以下なら改行とインデントを吹き飛ばす
                        if (strpos($parts, $MARK_CE) === false && substr_count($parts, $MARK_PT) < $options['nestlevel']) {
                            $parts = strtr($parts, [
                                $MARK_BR => "",
                                $MARK_NT => "",
                            ]);
                            $parts = preg_replace("#^($MARK_SP)|($MARK_SP)+$#u", '', $parts);
                        }
                        elseif ($context === 'CREATE') {
                            $parts = $parts . $MARK_BR;
                        }
                        else {
                            $brnt = $MARK_BR . $MARK_NT;
                            if ($subcontext !== 'WITH' && strtoupper($seek($current, +1)[1]) === 'SELECT') {
                                $brnt .= $MARK_NT;
                            }
                            $parts = preg_replace("#($MARK_BR)+#u", $brnt, $parts) . $MARK_BR . $MARK_NT;
                            $parts = preg_replace("#$MARK_CS#u", "", $parts);
                        }

                        // IN や数式はネストとみなさない
                        $prev = $seek($current, -1)[1];
                        $suffix = $MARK_PT;
                        if (strtoupper($prev) === 'IN' || !preg_match('#^[a-z0-9_]+$#i', $prev)) {
                            $suffix = '';
                        }
                        if ($subcontext === 'WITH') {
                            $suffix .= $MARK_BR;
                        }

                        $result[] = $MARK_NT . "($parts)" . $suffix;
                        break;
                    case ")":
                        break 2;
                }
            }
            return implode('', $result);
        };

        $result = $interpret();
        $result = preg_replaces("#" . implode('|', [
                // 改行文字＋インデント文字をインデントとみなす（改行＋連続スペースもついでに）
                "(?<indent>$MARK_BR(($MARK_NT|$MARK_SP)+))",
                // 行末コメントと単一コメント
                "(?<cs1>$MARK_BR$MARK_CS)",
                "(?<cs2>$MARK_CS)",
                // 連続改行は1つに集約
                "(?<br>$MARK_BR(($MARK_NT|$MARK_SP)*)($MARK_BR)*)",
                // 連続スペースは1つに集約
                "(?<sp>($MARK_SP)+)",
                // 下記はマーカ文字が現れないように単純置換
                "(?<ce>$MARK_CE)",
                "(?<nt>$MARK_NT)",
                "(?<pt>$MARK_PT)",
                "(?<R>$MARK_R)",
                "(?<N>$MARK_N)",
            ]) . "#u", [
            'indent' => function ($str) use ($options, $MARK_NT, $MARK_SP) {
                return "\n" . str_repeat($options['indent'], (substr_count($str, $MARK_NT) + substr_count($str, $MARK_SP)));
            },
            'cs1'    => "\n" . $options['indent'],
            'cs2'    => "",
            'br'     => "\n",
            'sp'     => ' ',
            'ce'     => "",
            'nt'     => "",
            'pt'     => "",
            'R'      => "\r",
            'N'      => "\n",
        ], $result);

        return trim($result);
    }
}
if (function_exists("ryunosuke\\dbml\\sql_format") && !defined("ryunosuke\\dbml\\sql_format")) {
    define("ryunosuke\\dbml\\sql_format", "ryunosuke\\dbml\\sql_format");
}

if (!isset($excluded_functions["concat"]) && (!function_exists("ryunosuke\\dbml\\concat") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\concat"))->isInternal()))) {
    /**
     * strcat の空文字回避版
     *
     * 基本は strcat と同じ。ただし、**引数の内1つでも空文字を含むなら空文字を返す**。
     *
     * 「プレフィックスやサフィックスを付けたいんだけど、空文字の場合はそのままで居て欲しい」という状況はまれによくあるはず。
     * コードで言えば `strlen($string) ? 'prefix-' . $string : '';` のようなもの。
     * 可変引数なので 端的に言えば mysql の CONCAT みたいな動作になる（あっちは NULL だが）。
     *
     * ```php
     * that(concat('prefix-', 'middle', '-suffix'))->isSame('prefix-middle-suffix');
     * that(concat('prefix-', '', '-suffix'))->isSame('');
     * ```
     *
     * @param mixed $variadic 結合する文字列（可変引数）
     * @return string 結合した文字列
     */
    function concat(...$variadic)
    {
        $result = '';
        foreach ($variadic as $s) {
            if (strlen($s) === 0) {
                return '';
            }
            $result .= $s;
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\concat") && !defined("ryunosuke\\dbml\\concat")) {
    define("ryunosuke\\dbml\\concat", "ryunosuke\\dbml\\concat");
}

if (!isset($excluded_functions["split_noempty"]) && (!function_exists("ryunosuke\\dbml\\split_noempty") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\split_noempty"))->isInternal()))) {
    /**
     * 空文字を除外する文字列分割
     *
     * - 空文字を任意の区切り文字で分割しても常に空配列
     * - キーは連番で返す（歯抜けがないただの配列）
     *
     * $triming を指定した場合、結果配列にも影響する。
     * つまり「除外は trim したいが結果配列にはしたくない」はできない。
     *
     * Example:
     * ```php
     * that(split_noempty(',', 'a, b, c'))->isSame(['a', 'b', 'c']);
     * that(split_noempty(',', 'a, , , b, c'))->isSame(['a', 'b', 'c']);
     * that(split_noempty(',', 'a, , , b, c', false))->isSame(['a', ' ', ' ', ' b', ' c']);
     * ```
     *
     * @param string $delimiter 区切り文字
     * @param string $string 対象文字
     * @param string|bool $trimchars 指定した文字を trim する。true を指定すると trim する
     * @return array 指定文字で分割して空文字を除いた配列
     */
    function split_noempty($delimiter, $string, $trimchars = true)
    {
        // trim しないなら preg_split(PREG_SPLIT_NO_EMPTY) で十分
        if (strlen($trimchars) === 0) {
            return preg_split('#' . preg_quote($delimiter, '#') . '#u', $string, -1, PREG_SPLIT_NO_EMPTY);
        }

        // trim するなら preg_split だと無駄にややこしくなるのでベタにやる
        $trim = ($trimchars === true) ? 'trim' : rbind('trim', $trimchars);
        $parts = explode($delimiter, $string);
        $parts = array_map($trim, $parts);
        $parts = array_filter($parts, 'strlen');
        $parts = array_values($parts);
        return $parts;
    }
}
if (function_exists("ryunosuke\\dbml\\split_noempty") && !defined("ryunosuke\\dbml\\split_noempty")) {
    define("ryunosuke\\dbml\\split_noempty", "ryunosuke\\dbml\\split_noempty");
}

if (!isset($excluded_functions["quoteexplode"]) && (!function_exists("ryunosuke\\dbml\\quoteexplode") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\quoteexplode"))->isInternal()))) {
    /**
     * エスケープやクオートに対応した explode
     *
     * $enclosures は配列で開始・終了文字が別々に指定できるが、実装上の都合で今のところ1文字ずつのみ。
     *
     * 歴史的な理由により第3引数は $limit でも $enclosures でもどちらでも渡すことができる。
     *
     * Example:
     * ```php
     * // シンプルな例
     * that(quoteexplode(',', 'a,b,c\\,d,"e,f"'))->isSame([
     *     'a', // 普通に分割される
     *     'b', // 普通に分割される
     *     'c\\,d', // \\ でエスケープしているので区切り文字とみなされない
     *     '"e,f"', // "" でクオートされているので区切り文字とみなされない
     * ]);
     *
     * // $enclosures で囲い文字の開始・終了文字を明示できる
     * that(quoteexplode(',', 'a,b,{e,f}', ['{' => '}']))->isSame([
     *     'a', // 普通に分割される
     *     'b', // 普通に分割される
     *     '{e,f}', // { } で囲まれているので区切り文字とみなされない
     * ]);
     *
     * // このように第3引数に $limit 引数を差し込むことができる
     * that(quoteexplode(',', 'a,b,{e,f}', 2, ['{' => '}']))->isSame([
     *     'a',
     *     'b,{e,f}',
     * ]);
     * ```
     *
     * @param string|array $delimiter 分割文字列
     * @param string $string 対象文字列
     * @param int $limit 分割数。負数未対応
     * @param array|string $enclosures 囲い文字。 ["start" => "end"] で開始・終了が指定できる
     * @param string $escape エスケープ文字
     * @return array 分割された配列
     */
    function quoteexplode($delimiter, $string, $limit = null, $enclosures = "'\"", $escape = '\\')
    {
        // for compatible 1.3.x
        if (!is_int($limit) && $limit !== null) {
            if (func_num_args() > 3) {
                $escape = $enclosures;
            }
            $enclosures = $limit;
            $limit = PHP_INT_MAX;
        }

        if ($limit === null) {
            $limit = PHP_INT_MAX;
        }
        $limit = max(1, $limit);

        $delimiters = arrayize($delimiter);
        $current = 0;
        $result = [];
        for ($i = 0, $l = strlen($string); $i < $l; $i++) {
            if (count($result) === $limit - 1) {
                break;
            }
            $i = strpos_quoted($string, $delimiters, $i, $enclosures, $escape);
            if ($i === false) {
                break;
            }
            foreach ($delimiters as $delimiter) {
                $delimiterlen = strlen($delimiter);
                if (substr_compare($string, $delimiter, $i, $delimiterlen) === 0) {
                    $result[] = substr($string, $current, $i - $current);
                    $current = $i + $delimiterlen;
                    $i += $delimiterlen - 1;
                    break;
                }
            }
        }
        $result[] = substr($string, $current, $l);
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\quoteexplode") && !defined("ryunosuke\\dbml\\quoteexplode")) {
    define("ryunosuke\\dbml\\quoteexplode", "ryunosuke\\dbml\\quoteexplode");
}

if (!isset($excluded_functions["strpos_quoted"]) && (!function_exists("ryunosuke\\dbml\\strpos_quoted") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\strpos_quoted"))->isInternal()))) {
    /**
     * クオートを考慮して strpos する
     *
     * Example:
     * ```php
     * // クオート中は除外される
     * that(strpos_quoted('hello "this" is world', 'is'))->isSame(13);
     * // 開始位置やクオート文字は指定できる（5文字目以降の \* に囲まれていない hoge の位置を返す）
     * that(strpos_quoted('1:hoge, 2:*hoge*, 3:hoge', 'hoge', 5, '*'))->isSame(20);
     * ```
     *
     * @param string $haystack 対象文字列
     * @param string|iterable $needle 位置を取得したい文字列
     * @param int $offset 開始位置
     * @param string|array $enclosure 囲い文字。この文字中にいる $from, $to 文字は走査外になる
     * @param string $escape エスケープ文字。この文字が前にある $from, $to 文字は走査外になる
     * @return false|int $needle の位置
     */
    function strpos_quoted($haystack, $needle, $offset = 0, $enclosure = "'\"", $escape = '\\')
    {
        if (is_string($enclosure) || is_null($enclosure)) {
            if (strlen($enclosure)) {
                $chars = str_split($enclosure);
                $enclosure = array_combine($chars, $chars);
            }
            else {
                $enclosure = [];
            }
        }
        $needles = arrayval($needle);

        $strlen = strlen($haystack);

        if ($offset < 0) {
            $offset += $strlen;
        }

        $enclosing = [];
        for ($i = $offset; $i < $strlen; $i++) {
            if ($i !== 0 && $haystack[$i - 1] === $escape) {
                continue;
            }
            foreach ($enclosure as $start => $end) {
                if (substr_compare($haystack, $end, $i, strlen($end)) === 0) {
                    if ($enclosing && $enclosing[count($enclosing) - 1] === $end) {
                        array_pop($enclosing);
                        $i += strlen($end) - 1;
                        continue 2;
                    }
                }
                if (substr_compare($haystack, $start, $i, strlen($start)) === 0) {
                    $enclosing[] = $end;
                    $i += strlen($start) - 1;
                    continue 2;
                }
            }

            if (empty($enclosing)) {
                foreach ($needles as $needle) {
                    if (substr_compare($haystack, $needle, $i, strlen($needle)) === 0) {
                        return $i;
                    }
                }
            }
        }
        return false;
    }
}
if (function_exists("ryunosuke\\dbml\\strpos_quoted") && !defined("ryunosuke\\dbml\\strpos_quoted")) {
    define("ryunosuke\\dbml\\strpos_quoted", "ryunosuke\\dbml\\strpos_quoted");
}

if (!isset($excluded_functions["str_contains"]) && (!function_exists("ryunosuke\\dbml\\str_contains") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_contains"))->isInternal()))) {
    /**
     * 指定文字列を含むか返す
     *
     * Example:
     * ```php
     * that(str_contains('abc', 'b'))->isTrue();
     * that(str_contains('abc', 'B', true))->isTrue();
     * that(str_contains('abc', ['b', 'x'], false, false))->isTrue();
     * that(str_contains('abc', ['b', 'x'], false, true))->isFalse();
     * ```
     *
     * @param string $haystack 対象文字列
     * @param string|array $needle 調べる文字列
     * @param bool $case_insensitivity 大文字小文字を無視するか
     * @param bool $and_flag すべて含む場合に true を返すか
     * @return bool $needle を含むなら true
     */
    function str_contains($haystack, $needle, $case_insensitivity = false, $and_flag = false)
    {
        if (!is_array($needle)) {
            $needle = [$needle];
        }

        // あくまで文字列としての判定に徹する（strpos の第2引数は闇が深い気がする）
        $haystack = (string) $haystack;
        $needle = array_map('strval', $needle);

        foreach ($needle as $str) {
            if ($str === '') {
                continue;
            }
            $pos = $case_insensitivity ? stripos($haystack, $str) : strpos($haystack, $str);
            if ($and_flag && $pos === false) {
                return false;
            }
            if (!$and_flag && $pos !== false) {
                return true;
            }
        }
        return !!$and_flag;
    }
}
if (function_exists("ryunosuke\\dbml\\str_contains") && !defined("ryunosuke\\dbml\\str_contains")) {
    define("ryunosuke\\dbml\\str_contains", "ryunosuke\\dbml\\str_contains");
}

if (!isset($excluded_functions["str_chop"]) && (!function_exists("ryunosuke\\dbml\\str_chop") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_chop"))->isInternal()))) {
    /**
     * 先頭・末尾の指定文字列を削ぎ落とす
     *
     * Example:
     * ```php
     * // 文字列からパス文字列と拡張子を削ぎ落とす
     * $PATH = '/path/to/something';
     * that(str_chop("$PATH/hoge.php", "$PATH/", '.php'))->isSame('hoge');
     * ```
     *
     * @param string $string 対象文字列
     * @param string $prefix 削ぎ落とす先頭文字列
     * @param string $suffix 削ぎ落とす末尾文字列
     * @param bool $case_insensitivity 大文字小文字を無視するか
     * @return string 削ぎ落とした文字列
     */
    function str_chop($string, $prefix = null, $suffix = null, $case_insensitivity = false)
    {
        $pattern = [];
        if (strlen($prefix)) {
            $pattern[] = '(\A' . preg_quote($prefix, '#') . ')';
        }
        if (strlen($suffix)) {
            $pattern[] = '(' . preg_quote($suffix, '#') . '\z)';
        }
        $flag = 'u' . ($case_insensitivity ? 'i' : '');
        return preg_replace('#' . implode('|', $pattern) . '#' . $flag, '', $string);
    }
}
if (function_exists("ryunosuke\\dbml\\str_chop") && !defined("ryunosuke\\dbml\\str_chop")) {
    define("ryunosuke\\dbml\\str_chop", "ryunosuke\\dbml\\str_chop");
}

if (!isset($excluded_functions["str_lchop"]) && (!function_exists("ryunosuke\\dbml\\str_lchop") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_lchop"))->isInternal()))) {
    /**
     * 先頭の指定文字列を削ぎ落とす
     *
     * Example:
     * ```php
     * // 文字列からパス文字列を削ぎ落とす
     * $PATH = '/path/to/something';
     * that(str_lchop("$PATH/hoge.php", "$PATH/"))->isSame('hoge.php');
     * ```
     *
     * @param string $string 対象文字列
     * @param string $prefix 削ぎ落とす先頭文字列
     * @param bool $case_insensitivity 大文字小文字を無視するか
     * @return string 削ぎ落とした文字列
     */
    function str_lchop($string, $prefix, $case_insensitivity = false)
    {
        return str_chop($string, $prefix, null, $case_insensitivity);
    }
}
if (function_exists("ryunosuke\\dbml\\str_lchop") && !defined("ryunosuke\\dbml\\str_lchop")) {
    define("ryunosuke\\dbml\\str_lchop", "ryunosuke\\dbml\\str_lchop");
}

if (!isset($excluded_functions["str_subreplace"]) && (!function_exists("ryunosuke\\dbml\\str_subreplace") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_subreplace"))->isInternal()))) {
    /**
     * 指定文字列を置換する
     *
     * $subject 内の $search を $replaces に置換する。
     * str_replace とは「N 番目のみ置換できる」点で異なる。
     * つまり、$search='hoge', $replace=[2 => 'fuga'] とすると「2 番目の 'hoge' が 'fuga' に置換される」という動作になる（0 ベース）。
     *
     * $replace に 非配列を与えた場合は配列化される。
     * つまり `$replaces = 'hoge'` は `$replaces = [0 => 'hoge']` と同じ（最初のマッチを置換する）。
     *
     * $replace に空配列を与えると何もしない。
     * 負数キーは後ろから数える動作となる。
     * また、置換後の文字列は置換対象にはならない。
     *
     * N 番目の検索文字列が見つからない場合は例外を投げる。
     * ただし、文字自体が見つからない場合は投げない。
     *
     * Example:
     * ```php
     * // 1番目（0ベースなので2番目）の x を X に置換
     * that(str_subreplace('xxx', 'x', [1 => 'X']))->isSame('xXx');
     * // 0番目（最前列）の x を Xa に、-1番目（最後尾）の x を Xz に置換
     * that(str_subreplace('!xxx!', 'x', [0 => 'Xa', -1 => 'Xz']))->isSame('!XaxXz!');
     * // 置換結果は置換対象にならない
     * that(str_subreplace('xxx', 'x', [0 => 'xxx', 1 => 'X']))->isSame('xxxXx');
     * ```
     *
     * @param string $subject 対象文字列
     * @param string $search 検索文字列
     * @param array|string $replaces 置換文字列配列（単一指定は配列化される）
     * @param bool $case_insensitivity 大文字小文字を無視するか
     * @return string 置換された文字列
     */
    function str_subreplace($subject, $search, $replaces, $case_insensitivity = false)
    {
        $replaces = is_iterable($replaces) ? $replaces : [$replaces];

        // 空はそのまま返す
        if (is_empty($replaces)) {
            return $subject;
        }

        // 負数対応のために逆数計算（ついでに整数チェック）
        $subcount = $case_insensitivity ? substr_count(strtolower($subject), strtolower($search)) : substr_count($subject, $search);
        if ($subcount === 0) {
            return $subject;
        }
        $mapping = [];
        foreach ($replaces as $n => $replace) {
            $origN = $n;
            if (!is_int($n)) {
                throw new \InvalidArgumentException('$replaces key must be integer.');
            }
            if ($n < 0) {
                $n += $subcount;
            }
            if (!(0 <= $n && $n < $subcount)) {
                throw new \InvalidArgumentException("notfound search string '$search' of {$origN}th.");
            }
            $mapping[$n] = $replace;
        }
        $maxseq = max(array_keys($mapping));
        $offset = 0;
        for ($n = 0; $n <= $maxseq; $n++) {
            $pos = $case_insensitivity ? stripos($subject, $search, $offset) : strpos($subject, $search, $offset);
            if (isset($mapping[$n])) {
                $subject = substr_replace($subject, $mapping[$n], $pos, strlen($search));
                $offset = $pos + strlen($mapping[$n]);
            }
            else {
                $offset = $pos + strlen($search);
            }
        }
        return $subject;
    }
}
if (function_exists("ryunosuke\\dbml\\str_subreplace") && !defined("ryunosuke\\dbml\\str_subreplace")) {
    define("ryunosuke\\dbml\\str_subreplace", "ryunosuke\\dbml\\str_subreplace");
}

if (!isset($excluded_functions["str_embed"]) && (!function_exists("ryunosuke\\dbml\\str_embed") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_embed"))->isInternal()))) {
    /**
     * エスケープ付きで文字列を置換する
     *
     * $replacemap で from -> to 文字列を指定する。
     * to は文字列と配列を受け付ける。
     * 文字列の場合は普通に想起される動作で単純な置換となる。
     * 配列の場合は順次置換していく。要素が足りなくなったら例外を投げる。
     *
     * strtr と同様、最も長いキーから置換を行い、置換後の文字列は対象にならない。
     *
     * $enclosure で「特定文字に囲まれている」場合を無視することができる。
     * $escape で「特定文字が前にある」場合を無視することができる。
     *
     * Example:
     * ```php
     * // 最も単純な置換
     * that(str_embed('a, b, c', ['a' => 'A', 'b' => 'B', 'c' => 'C']))->isSame('A, B, C');
     * // 最も長いキーから置換される
     * that(str_embed('abc', ['a' => 'X', 'ab' => 'AB']))->isSame('ABc');
     * // 配列を渡すと「N番目の置換」が実現できる（文字列の場合は再利用される）
     * that(str_embed('a, a, b, b', [
     *     'a' => 'A',          // 全ての a が A になる
     *     'b' => ['B1', 'B2'], // 1番目の b が B1, 2番目の b が B2 になる
     * ]))->isSame('A, A, B1, B2');
     * // 最も重要な性質として "' で囲まれていると対象にならない
     * that(str_embed('a, "a", b, "b", b', [
     *     'a' => 'A',
     *     'b' => ['B1', 'B2'],
     * ]))->isSame('A, "a", B1, "b", B2');
     * ```
     *
     * @param string $string 対象文字列
     * @param array $replacemap 置換文字列
     * @param string|array $enclosure 囲い文字。この文字中にいる $from, $to 文字は走査外になる
     * @param string $escape エスケープ文字。この文字が前にある $from, $to 文字は走査外になる
     * @return string 置換された文字列
     */
    function str_embed($string, $replacemap, $enclosure = "'\"", $escape = '\\')
    {
        assert(is_iterable($replacemap));

        $string = (string) $string;

        // 長いキーから処理するためソートしておく
        $replacemap = arrayval($replacemap, false);
        uksort($replacemap, function ($a, $b) { return strlen($b) - strlen($a); });
        $srcs = array_keys($replacemap);

        $counter = array_fill_keys(array_keys($replacemap), 0);
        for ($i = 0; $i < strlen($string); $i++) {
            $i = strpos_quoted($string, $srcs, $i, $enclosure, $escape);
            if ($i === false) {
                break;
            }

            foreach ($replacemap as $src => $dst) {
                $srclen = strlen($src);
                if ($srclen === 0) {
                    throw new \InvalidArgumentException("src length is 0.");
                }
                if (substr_compare($string, $src, $i, $srclen) === 0) {
                    if (is_array($dst)) {
                        $n = $counter[$src]++;
                        if (!isset($dst[$n])) {
                            throw new \InvalidArgumentException("notfound search string '$src' of {$n}th.");
                        }
                        $dst = $dst[$n];
                    }
                    $string = substr_replace($string, $dst, $i, $srclen);
                    $i += strlen($dst) - 1;
                    break;
                }
            }
        }
        return $string;
    }
}
if (function_exists("ryunosuke\\dbml\\str_embed") && !defined("ryunosuke\\dbml\\str_embed")) {
    define("ryunosuke\\dbml\\str_embed", "ryunosuke\\dbml\\str_embed");
}

if (!isset($excluded_functions["str_between"]) && (!function_exists("ryunosuke\\dbml\\str_between") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_between"))->isInternal()))) {
    /**
     * 指定文字で囲まれた文字列を取得する
     *
     * $from, $to で指定した文字間を取得する（$from, $to 自体は結果に含まれない）。
     * ネストしている場合、一番外側の文字間を返す。
     *
     * $enclosure で「特定文字に囲まれている」場合を無視することができる。
     * $escape で「特定文字が前にある」場合を無視することができる。
     *
     * $position を与えた場合、その場所から走査を開始する。
     * さらに結果があった場合、 $position には「次の走査開始位置」が代入される。
     * これを使用すると連続で「次の文字, 次の文字, ...」と言った動作が可能になる。
     *
     * Example:
     * ```php
     * // $position を利用して "first", "second", "third" を得る（"で囲まれた "blank" は返ってこない）。
     * that(str_between('{first} and {second} and "{blank}" and {third}', '{', '}', $n))->isSame('first');
     * that(str_between('{first} and {second} and "{blank}" and {third}', '{', '}', $n))->isSame('second');
     * that(str_between('{first} and {second} and "{blank}" and {third}', '{', '}', $n))->isSame('third');
     * // ネストしている場合は最も外側を返す
     * that(str_between('{nest1{nest2{nest3}}}', '{', '}'))->isSame('nest1{nest2{nest3}}');
     * ```
     *
     * @param string $string 対象文字列
     * @param string $from 開始文字列
     * @param string $to 終了文字列
     * @param int $position 開始位置。渡した場合次の開始位置が設定される
     * @param string $enclosure 囲い文字。この文字中にいる $from, $to 文字は走査外になる
     * @param string $escape エスケープ文字。この文字が前にある $from, $to 文字は走査外になる
     * @return string|bool $from, $to で囲まれた文字。見つからなかった場合は false
     */
    function str_between($string, $from, $to, &$position = 0, $enclosure = '\'"', $escape = '\\')
    {
        $strlen = strlen($string);
        $fromlen = strlen($from);
        $tolen = strlen($to);
        $position = intval($position);
        $nesting = 0;
        $start = null;
        for ($i = $position; $i < $strlen; $i++) {
            $i = strpos_quoted($string, [$from, $to], $i, $enclosure, $escape);
            if ($i === false) {
                break;
            }

            // 開始文字と終了文字が重複している可能性があるので $to からチェックする
            if (substr_compare($string, $to, $i, $tolen) === 0) {
                if (--$nesting === 0) {
                    $position = $i + $tolen;
                    return substr($string, $start, $i - $start);
                }
                // いきなり終了文字が来た場合は無視する
                if ($nesting < 0) {
                    $nesting = 0;
                }
            }
            if (substr_compare($string, $from, $i, $fromlen) === 0) {
                if ($nesting++ === 0) {
                    $start = $i + $fromlen;
                }
            }
        }
        return false;
    }
}
if (function_exists("ryunosuke\\dbml\\str_between") && !defined("ryunosuke\\dbml\\str_between")) {
    define("ryunosuke\\dbml\\str_between", "ryunosuke\\dbml\\str_between");
}

if (!isset($excluded_functions["str_ellipsis"]) && (!function_exists("ryunosuke\\dbml\\str_ellipsis") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\str_ellipsis"))->isInternal()))) {
    /**
     * 文字列を指定幅に丸める
     *
     * mb_strimwidth と機能的には同じだが、省略文字の差し込み位置を $pos で指定できる。
     * $pos は負数が指定できる。負数の場合後ろから数えられる。
     * 省略した場合は真ん中となる。
     *
     * Example:
     * ```php
     * // 8文字に丸める（$pos 省略なので真ん中が省略される）
     * that(str_ellipsis('1234567890', 8, '...'))->isSame('12...890');
     * // 8文字に丸める（$pos=1 なので1文字目から省略される）
     * that(str_ellipsis('1234567890', 8, '...', 1))->isSame('1...7890');
     * // 8文字に丸める（$pos=-1 なので後ろから1文字目から省略される）
     * that(str_ellipsis('1234567890', 8, '...', -1))->isSame('1234...0');
     * ```
     *
     * @param string $string 対象文字列
     * @param int $width 丸める幅
     * @param string $trimmarker 省略文字列
     * @param int|null $pos 省略記号の差し込み位置
     * @return string 丸められた文字列
     */
    function str_ellipsis($string, $width, $trimmarker = '...', $pos = null)
    {
        $string = (string) $string;

        $strlen = mb_strlen($string);
        if ($strlen <= $width) {
            return $string;
        }

        $markerlen = mb_strlen($trimmarker);
        if ($markerlen >= $width) {
            return $trimmarker;
        }

        $length = $width - $markerlen;
        $pos = $pos ?? $length / 2;
        if ($pos < 0) {
            $pos += $length;
        }
        $pos = max(0, min($pos, $length));

        return mb_substr_replace($string, $trimmarker, $pos, $strlen - $length);
    }
}
if (function_exists("ryunosuke\\dbml\\str_ellipsis") && !defined("ryunosuke\\dbml\\str_ellipsis")) {
    define("ryunosuke\\dbml\\str_ellipsis", "ryunosuke\\dbml\\str_ellipsis");
}

if (!isset($excluded_functions["pascal_case"]) && (!function_exists("ryunosuke\\dbml\\pascal_case") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\pascal_case"))->isInternal()))) {
    /**
     * PascalCase に変換する
     *
     * Example:
     * ```php
     * that(pascal_case('this_is_a_pen'))->isSame('ThisIsAPen');
     * ```
     *
     * @param string $string 対象文字列
     * @param string $delimiter デリミタ
     * @return string 変換した文字列
     */
    function pascal_case($string, $delimiter = '_')
    {
        return strtr(ucwords(strtr($string, [$delimiter => ' '])), [' ' => '']);
    }
}
if (function_exists("ryunosuke\\dbml\\pascal_case") && !defined("ryunosuke\\dbml\\pascal_case")) {
    define("ryunosuke\\dbml\\pascal_case", "ryunosuke\\dbml\\pascal_case");
}

if (!isset($excluded_functions["namespace_split"]) && (!function_exists("ryunosuke\\dbml\\namespace_split") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\namespace_split"))->isInternal()))) {
    /**
     * 文字列を名前空間とローカル名に区切ってタプルで返す
     *
     * class_namespace/class_shorten や function_shorten とほぼ同じだが下記の違いがある。
     *
     * - あくまで文字列として処理する
     *     - 例えば class_namespace は get_class されるが、この関数は（いうなれば） strval される
     * - \\ を trim しないし、特別扱いもしない
     *     - `ns\\hoge` と `\\ns\\hoge` で返り値が微妙に異なる
     *     - `ns\\` のような場合は名前空間だけを返す
     *
     * Example:
     * ```php
     * that(namespace_split('ns\\hoge'))->isSame(['ns', 'hoge']);
     * that(namespace_split('hoge'))->isSame(['', 'hoge']);
     * that(namespace_split('ns\\'))->isSame(['ns', '']);
     * that(namespace_split('\\hoge'))->isSame(['', 'hoge']);
     * ```
     *
     * @param string $string 対象文字列
     * @return array [namespace, localname]
     */
    function namespace_split($string)
    {
        $pos = strrpos($string, '\\');
        if ($pos === false) {
            return ['', $string];
        }
        return [substr($string, 0, $pos), substr($string, $pos + 1)];
    }
}
if (function_exists("ryunosuke\\dbml\\namespace_split") && !defined("ryunosuke\\dbml\\namespace_split")) {
    define("ryunosuke\\dbml\\namespace_split", "ryunosuke\\dbml\\namespace_split");
}

if (!isset($excluded_functions["paml_import"]) && (!function_exists("ryunosuke\\dbml\\paml_import") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\paml_import"))->isInternal()))) {
    /**
     * paml 的文字列をパースする
     *
     * paml とは yaml を簡易化したような独自フォーマットを指す。
     * ざっくりと下記のような特徴がある。
     *
     * - ほとんど yaml と同じだがフロースタイルのみでキーコロンの後のスペースは不要
     * - yaml のアンカーや複数ドキュメントのようなややこしい仕様はすべて未対応
     * - 配列を前提にしているので、トップレベルの `[]` `{}` は不要
     * - 配列・連想配列の区別はなし。 `[]` でいわゆる php の配列、 `{}` で stdClass を表す
     * - bare string で php の定数を表す
     *
     * 簡易的な設定の注入に使える（yaml は標準で対応していないし、json や php 配列はクオートの必要やケツカンマ問題がある）。
     * なお、かなり緩くパースしてるので基本的にエラーにはならない。
     *
     * 早見表：
     *
     * - php:  `["n" => null, "f" => false, "i" => 123, "d" => 3.14, "s" => "this is string", "a" => [1, 2, "x" => "X"]]`
     *     - ダブルアローとキーのクオートが冗長
     * - json: `{"n":null, "f":false, "i":123, "d":3.14, "s":"this is string", "a":{"0": 1, "1": 2, "x": "X"}}`
     *     - キーのクオートが冗長だしケツカンマ非許容
     * - yaml: `{n: null, f: false, i: 123, d: 3.14, s: "this is string", a: {0: 1, 1: 2, x: X}}`
     *     - 理想に近いが、コロンの後にスペースが必要だし連想配列が少々難。なにより拡張や外部ライブラリが必要
     * - paml: `n:null, f:false, i:123, d:3.14, s:"this is string", a:[1, 2, x:X]`
     *     - シンプルイズベスト
     *
     * Example:
     * ```php
     * // こういったスカラー型はほとんど yaml と一緒だが、コロンの後のスペースは不要（あってもよい）
     * that(paml_import('n:null, f:false, i:123, d:3.14, s:"this is string"'))->isSame([
     *     'n' => null,
     *     'f' => false,
     *     'i' => 123,
     *     'd' => 3.14,
     *     's' => 'this is string',
     * ]);
     * // 配列が使える（キーは連番なら不要）。ネストも可能
     * that(paml_import('a:[1,2,x:X,3], nest:[a:[b:[c:[X]]]]'))->isSame([
     *     'a'    => [1, 2, 'x' => 'X', 3],
     *     'nest' => [
     *         'a' => [
     *             'b' => [
     *                 'c' => ['X']
     *             ],
     *         ],
     *     ],
     * ]);
     * // bare 文字列で定数が使える
     * that(paml_import('pv:PHP_VERSION, ao:ArrayObject::STD_PROP_LIST'))->isSame([
     *     'pv' => \PHP_VERSION,
     *     'ao' => \ArrayObject::STD_PROP_LIST,
     * ]);
     * ```
     *
     * @param string $pamlstring PAML 文字列
     * @param array $options オプション配列
     * @return array php 配列
     */
    function paml_import($pamlstring, $options = [])
    {
        $options += [
            'cache'          => true,
            'trailing-comma' => true,
        ];

        static $caches = [];
        if ($options['cache']) {
            return $caches[$pamlstring] = $caches[$pamlstring] ?? paml_import($pamlstring, ['cache' => false] + $options);
        }

        $escapers = ['"' => '"', "'" => "'", '[' => ']', '{' => '}'];

        $values = array_map('trim', quoteexplode(',', $pamlstring, null, $escapers));
        if ($options['trailing-comma'] && end($values) === '') {
            array_pop($values);
        }

        $result = [];
        foreach ($values as $value) {
            $key = null;
            $kv = array_map('trim', quoteexplode(':', $value, 2, $escapers));
            if (count($kv) === 2) {
                [$key, $value] = $kv;
            }

            $prefix = $value[0] ?? null;
            $suffix = $value[-1] ?? null;

            if ($prefix === '[' && $suffix === ']') {
                $value = (array) paml_import(substr($value, 1, -1), $options);
            }
            elseif ($prefix === '{' && $suffix === '}') {
                $value = (object) paml_import(substr($value, 1, -1), $options);
            }
            elseif ($prefix === '"' && $suffix === '"') {
                //$value = stripslashes(substr($value, 1, -1));
                $value = json_decode($value);
            }
            elseif ($prefix === "'" && $suffix === "'") {
                $value = substr($value, 1, -1);
            }
            elseif (defined($value)) {
                $value = constant($value);
            }
            elseif (is_numeric($value)) {
                if (ctype_digit(ltrim($value, '+-'))) {
                    $value = (int) $value;
                }
                else {
                    $value = (double) $value;
                }
            }

            if ($key === null) {
                $result[] = $value;
            }
            else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\paml_import") && !defined("ryunosuke\\dbml\\paml_import")) {
    define("ryunosuke\\dbml\\paml_import", "ryunosuke\\dbml\\paml_import");
}

if (!isset($excluded_functions["preg_splice"]) && (!function_exists("ryunosuke\\dbml\\preg_splice") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\preg_splice"))->isInternal()))) {
    /**
     * キャプチャも行える preg_replace
     *
     * 「置換を行いつつ、キャプチャ文字列が欲しい」状況はまれによくあるはず。
     *
     * $replacement に callable を渡すと preg_replace_callback がコールされる。
     * callable とはいっても単純文字列 callble （"strtoupper" など）は callable とはみなされない。
     * 配列形式の callable や クロージャのみ preg_replace_callback になる。
     *
     * Example:
     * ```php
     * // 数字を除去しつつその除去された数字を得る
     * that(preg_splice('#\\d+#', '', 'abc123', $m))->isSame('abc');
     * that($m)->isSame(['123']);
     *
     * // callable だと preg_replace_callback が呼ばれる
     * that(preg_splice('#[a-z]+#', function($m){return strtoupper($m[0]);}, 'abc123', $m))->isSame('ABC123');
     * that($m)->isSame(['abc']);
     *
     * // ただし、 文字列 callable は文字列として扱う
     * that(preg_splice('#[a-z]+#', 'strtoupper', 'abc123', $m))->isSame('strtoupper123');
     * that($m)->isSame(['abc']);
     * ```
     *
     * @param string $pattern 正規表現
     * @param string|callable $replacement 置換文字列
     * @param string $subject 対象文字列
     * @param array $matches キャプチャ配列が格納される
     * @return string 置換された文字列
     */
    function preg_splice($pattern, $replacement, $subject, &$matches = [])
    {
        if (preg_match($pattern, $subject, $matches)) {
            if (!is_string($replacement) && is_callable($replacement)) {
                $subject = preg_replace_callback($pattern, $replacement, $subject);
            }
            else {
                $subject = preg_replace($pattern, $replacement, $subject);
            }
        }
        return $subject;
    }
}
if (function_exists("ryunosuke\\dbml\\preg_splice") && !defined("ryunosuke\\dbml\\preg_splice")) {
    define("ryunosuke\\dbml\\preg_splice", "ryunosuke\\dbml\\preg_splice");
}

if (!isset($excluded_functions["preg_replaces"]) && (!function_exists("ryunosuke\\dbml\\preg_replaces") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\preg_replaces"))->isInternal()))) {
    /**
     * パターン番号を指定して preg_replace する
     *
     * パターン番号を指定してそれのみを置換する。
     * 名前付きキャプチャを使用している場合はキーに文字列も使える。
     * 値にクロージャを渡した場合はコールバックされて置換される。
     *
     * $replacements に単一文字列を渡した場合、 `[1 => $replacements]` と等しくなる（第1キャプチャを置換）。
     *
     * Example:
     * ```php
     * // a と z に囲まれた数字を XXX に置換する
     * that(preg_replaces('#a(\d+)z#', [1 => 'XXX'], 'a123z'))->isSame('aXXXz');
     * // 名前付きキャプチャも指定できる
     * that(preg_replaces('#a(?<digit>\d+)z#', ['digit' => 'XXX'], 'a123z'))->isSame('aXXXz');
     * // クロージャを渡すと元文字列を引数としてコールバックされる
     * that(preg_replaces('#a(?<digit>\d+)z#', ['digit' => function($src){return $src * 2;}], 'a123z'))->isSame('a246z');
     * // 複合的なサンプル（a タグの href と target 属性を書き換える）
     * that(preg_replaces('#<a\s+href="(?<href>.*)"\s+target="(?<target>.*)">#', [
     *     'href'   => function($href){return strtoupper($href);},
     *     'target' => function($target){return strtoupper($target);},
     * ], '<a href="hoge" target="fuga">inner text</a>'))->isSame('<a href="HOGE" target="FUGA">inner text</a>');
     * ```
     *
     * @param string $pattern 正規表現
     * @param array|string $replacements 置換文字列
     * @param string $subject 対象文字列
     * @param int $limit 置換回数
     * @param null $count 置換回数格納変数
     * @return string 置換された文字列
     */
    function preg_replaces($pattern, $replacements, $subject, $limit = -1, &$count = null)
    {
        $offset = 0;
        $count = 0;
        if (!is_arrayable($replacements)) {
            $replacements = [1 => $replacements];
        }

        preg_match_all($pattern, $subject, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ($limit-- === 0) {
                break;
            }
            $count++;

            foreach ($match as $index => $m) {
                if ($m[1] >= 0 && $index !== 0 && isset($replacements[$index])) {
                    $src = $m[0];
                    $dst = $replacements[$index];
                    if ($dst instanceof \Closure) {
                        $dst = $dst($src);
                    }

                    $srclen = strlen($src);
                    $dstlen = strlen($dst);

                    $subject = substr_replace($subject, $dst, $offset + $m[1], $srclen);
                    $offset += $dstlen - $srclen;
                }
            }
        }
        return $subject;
    }
}
if (function_exists("ryunosuke\\dbml\\preg_replaces") && !defined("ryunosuke\\dbml\\preg_replaces")) {
    define("ryunosuke\\dbml\\preg_replaces", "ryunosuke\\dbml\\preg_replaces");
}

if (!isset($excluded_functions["mb_substr_replace"]) && (!function_exists("ryunosuke\\dbml\\mb_substr_replace") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\mb_substr_replace"))->isInternal()))) {
    /**
     * マルチバイト対応 substr_replace
     *
     * 本家は配列を与えたりできるが、ややこしいし使う気がしないので未対応。
     *
     * Example:
     * ```php
     * // 2文字目から5文字を「あいうえお」に置換する
     * that(mb_substr_replace('０１２３４５６７８９', 'あいうえお', 2, 5))->isSame('０１あいうえお７８９');
     * ```
     *
     * @param string $string 対象文字列
     * @param string $replacement 置換文字列
     * @param int $start 開始位置
     * @param int $length 置換長
     * @return string 置換した文字列
     */
    function mb_substr_replace($string, $replacement, $start, $length = null)
    {
        $string = (string) $string;

        $strlen = mb_strlen($string);
        if ($start < 0) {
            $start += $strlen;
        }
        if ($length < 0) {
            $length += $strlen - $start;
        }

        return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, null);
    }
}
if (function_exists("ryunosuke\\dbml\\mb_substr_replace") && !defined("ryunosuke\\dbml\\mb_substr_replace")) {
    define("ryunosuke\\dbml\\mb_substr_replace", "ryunosuke\\dbml\\mb_substr_replace");
}

if (!isset($excluded_functions["evaluate"]) && (!function_exists("ryunosuke\\dbml\\evaluate") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\evaluate"))->isInternal()))) {
    /**
     * eval のプロキシ関数
     *
     * 一度ファイルに吐いてから require した方が opcache が効くので抜群に速い。
     * また、素の eval は ParseError が起こったときの表示がわかりにくすぎるので少し見やすくしてある。
     *
     * 関数化してる以上 eval におけるコンテキストの引き継ぎはできない。
     * ただし、引数で変数配列を渡せるようにしてあるので get_defined_vars を併用すれば基本的には同じ（$this はどうしようもない）。
     *
     * 短いステートメントだと opcode が少ないのでファイルを経由せず直接 eval したほうが速いことに留意。
     * 一応引数で指定できるようにはしてある。
     *
     * Example:
     * ```php
     * $a = 1;
     * $b = 2;
     * $phpcode = ';
     * $c = $a + $b;
     * return $c * 3;
     * ';
     * that(evaluate($phpcode, get_defined_vars()))->isSame(9);
     * ```
     *
     * @param string $phpcode 実行する php コード
     * @param array $contextvars コンテキスト変数配列
     * @param int $cachesize キャッシュするサイズ
     * @return mixed eval の return 値
     */
    function evaluate($phpcode, $contextvars = [], $cachesize = 256)
    {
        $cachefile = null;
        if ($cachesize && strlen($phpcode) >= $cachesize) {
            $cachefile = cachedir() . '/' . rawurlencode(__FUNCTION__) . '-' . sha1($phpcode) . '.php';
            if (!file_exists($cachefile)) {
                file_put_contents($cachefile, "<?php $phpcode", LOCK_EX);
            }
        }

        try {
            if ($cachefile) {
                return (static function () {
                    extract(func_get_arg(1));
                    return require func_get_arg(0);
                })($cachefile, $contextvars);
            }
            else {
                return (static function () {
                    extract(func_get_arg(1));
                    return eval(func_get_arg(0));
                })($phpcode, $contextvars);
            }
        }
        catch (\ParseError $ex) {
            $errline = $ex->getLine();
            $errline_1 = $errline - 1;
            $codes = preg_split('#\\R#u', $phpcode);
            $codes[$errline_1] = '>>> ' . $codes[$errline_1];

            $N = 5; // 前後の行数
            $message = $ex->getMessage();
            $message .= "\n" . implode("\n", array_slice($codes, max(0, $errline_1 - $N), $N * 2 + 1));
            if ($cachefile) {
                $message .= "\n in " . realpath($cachefile) . " on line " . $errline . "\n";
            }
            throw new \ParseError($message, $ex->getCode(), $ex);
        }
    }
}
if (function_exists("ryunosuke\\dbml\\evaluate") && !defined("ryunosuke\\dbml\\evaluate")) {
    define("ryunosuke\\dbml\\evaluate", "ryunosuke\\dbml\\evaluate");
}

if (!isset($excluded_functions["optional"]) && (!function_exists("ryunosuke\\dbml\\optional") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\optional"))->isInternal()))) {
    /**
     * オブジェクトならそれを、オブジェクトでないなら NullObject を返す
     *
     * null を返すかもしれないステートメントを一時変数を介さずワンステートメントで呼ぶことが可能になる。
     *
     * NullObject は 基本的に null を返すが、return type が規約されている場合は null 以外を返すこともある。
     * 取得系呼び出しを想定しているので、設定系呼び出しは行うべきではない。
     * __set のような明らかに設定が意図されているものは例外が飛ぶ。
     *
     * Example:
     * ```php
     * // null を返すかもしれないステートメント
     * $getobject = function () {return null;};
     * // メソッド呼び出しは null を返す
     * that(optional($getobject())->method())->isSame(null);
     * // プロパティアクセスは null を返す
     * that(optional($getobject())->property)->isSame(null);
     * // empty は true を返す
     * that(empty(optional($getobject())->nothing))->isSame(true);
     * // __isset は false を返す
     * that(isset(optional($getobject())->nothing))->isSame(false);
     * // __toString は '' を返す
     * that(strval(optional($getobject())))->isSame('');
     * // __invoke は null を返す
     * that(call_user_func(optional($getobject())))->isSame(null);
     * // 配列アクセスは null を返す
     * that(optional($getobject())['hoge'])->isSame(null);
     * // 空イテレータを返す
     * that(iterator_to_array(optional($getobject())))->isSame([]);
     *
     * // $expected を与えるとその型以外は NullObject を返す（\ArrayObject はオブジェクトだが stdClass ではない）
     * that(optional(new \ArrayObject([1]), 'stdClass')->count())->isSame(null);
     * ```
     *
     * @param object|null $object オブジェクト
     * @param string $expected 期待するクラス名。指定した場合は is_a される
     * @return object $object がオブジェクトならそのまま返し、違うなら NullObject を返す
     */
    function optional($object, $expected = null)
    {
        if (is_object($object)) {
            if ($expected === null || is_a($object, $expected)) {
                return $object;
            }
        }

        static $nullobject = null;
        if ($nullobject === null) {
            $nullobject = new class implements \ArrayAccess, \IteratorAggregate {
                // @formatter:off
                public function __isset($name) { return false; }
                public function __get($name) { return null; }
                public function __set($name, $value) { throw new \DomainException('called NullObject#' . __FUNCTION__); }
                public function __unset($name) { throw new \DomainException('called NullObject#' . __FUNCTION__); }
                public function __call($name, $arguments) { return null; }
                public function __invoke() { return null; }
                public function __toString() { return ''; }
                public function offsetExists($offset) { return false; }
                public function offsetGet($offset) { return null; }
                public function offsetSet($offset, $value) { throw new \DomainException('called NullObject#' . __FUNCTION__); }
                public function offsetUnset($offset) { throw new \DomainException('called NullObject#' . __FUNCTION__); }
                public function getIterator() { return new \ArrayIterator([]); }
                // @formatter:on
            };
        }
        /** @var object $nullobject */
        return $nullobject;
    }
}
if (function_exists("ryunosuke\\dbml\\optional") && !defined("ryunosuke\\dbml\\optional")) {
    define("ryunosuke\\dbml\\optional", "ryunosuke\\dbml\\optional");
}

if (!isset($excluded_functions["throws"]) && (!function_exists("ryunosuke\\dbml\\throws") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\throws"))->isInternal()))) {
    /**
     * throw の関数版
     *
     * hoge() or throw などしたいことがまれによくあるはず。
     *
     * Example:
     * ```php
     * try {
     *     throws(new \Exception('throws'));
     * }
     * catch (\Exception $ex) {
     *     that($ex->getMessage())->isSame('throws');
     * }
     * ```
     *
     * @param \Exception $ex 投げる例外
     * @return mixed （`return hoge or throws` のようなコードで警告が出るので抑止用）
     */
    function throws($ex)
    {
        throw $ex;
    }
}
if (function_exists("ryunosuke\\dbml\\throws") && !defined("ryunosuke\\dbml\\throws")) {
    define("ryunosuke\\dbml\\throws", "ryunosuke\\dbml\\throws");
}

if (!isset($excluded_functions["try_finally"]) && (!function_exists("ryunosuke\\dbml\\try_finally") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\try_finally"))->isInternal()))) {
    /**
     * try ～ finally 構文の関数版
     *
     * 例外は投げっぱなす。例外機構構文が冗長なことがまれによくあるはず。
     *
     * Example:
     * ```php
     * $finally_count = 0;
     * $finally = function()use(&$finally_count){$finally_count++;};
     * // 例外が飛ぼうと飛ぶまいと $finally は実行される
     * $try = function($a, $b, $c){return [$a, $b, $c];};
     * that(try_finally($try, $finally, 1, 2, 3))->isSame([1, 2, 3]);
     * that($finally_count)->isSame(1); // 呼ばれている
     * // 例外は投げっぱなすが、 $finally は実行される
     * $try = function(){throw new \Exception('tried');};
     * try {try_finally($try, $finally, 1, 2, 3);} catch(\Exception $e){};
     * that($finally_count)->isSame(2); // 呼ばれている
     * ```
     *
     * @param callable $try try ブロッククロージャ
     * @param callable $finally finally ブロッククロージャ
     * @param array $variadic $try に渡る引数
     * @return \Exception|mixed 例外が飛ばなかったら $try ブロックの返り値、飛んだなら $catch の返り値（デフォルトで例外オブジェクト）
     */
    function try_finally($try, $finally = null, ...$variadic)
    {
        return try_catch_finally($try, throws, $finally, ...$variadic);
    }
}
if (function_exists("ryunosuke\\dbml\\try_finally") && !defined("ryunosuke\\dbml\\try_finally")) {
    define("ryunosuke\\dbml\\try_finally", "ryunosuke\\dbml\\try_finally");
}

if (!isset($excluded_functions["try_catch_finally"]) && (!function_exists("ryunosuke\\dbml\\try_catch_finally") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\try_catch_finally"))->isInternal()))) {
    /**
     * try ～ catch ～ finally 構文の関数版
     *
     * 例外機構構文が冗長なことがまれによくあるはず。
     *
     * Example:
     * ```php
     * $finally_count = 0;
     * $finally = function()use(&$finally_count){$finally_count++;};
     * // 例外が飛ぼうと飛ぶまいと $finally は実行される
     * $try = function($a, $b, $c){return [$a, $b, $c];};
     * that(try_catch_finally($try, null, $finally, 1, 2, 3))->isSame([1, 2, 3]);
     * that($finally_count)->isSame(1); // 呼ばれている
     * // 例外を投げるが、 $catch で握りつぶす
     * $try = function(){throw new \Exception('tried');};
     * that(try_catch_finally($try, null, $finally, 1, 2, 3)->getMessage())->isSame('tried');
     * that($finally_count)->isSame(2); // 呼ばれている
     * ```
     *
     * @param callable $try try ブロッククロージャ
     * @param callable $catch catch ブロッククロージャ
     * @param callable $finally finally ブロッククロージャ
     * @param array $variadic $try に渡る引数
     * @return \Exception|mixed 例外が飛ばなかったら $try ブロックの返り値、飛んだなら $catch の返り値（デフォルトで例外オブジェクト）
     */
    function try_catch_finally($try, $catch = null, $finally = null, ...$variadic)
    {
        if ($catch === null) {
            $catch = function ($v) { return $v; };
        }

        try {
            return $try(...$variadic);
        }
        catch (\Exception $tried_ex) {
            try {
                return $catch($tried_ex);
            }
            catch (\Exception $catched_ex) {
                throw $catched_ex;
            }
        }
        finally {
            if ($finally !== null) {
                $finally();
            }
        }
    }
}
if (function_exists("ryunosuke\\dbml\\try_catch_finally") && !defined("ryunosuke\\dbml\\try_catch_finally")) {
    define("ryunosuke\\dbml\\try_catch_finally", "ryunosuke\\dbml\\try_catch_finally");
}

if (!isset($excluded_functions["cachedir"]) && (!function_exists("ryunosuke\\dbml\\cachedir") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\cachedir"))->isInternal()))) {
    /**
     * 本ライブラリで使用するキャッシュディレクトリを設定する
     *
     * @param string|null $dirname キャッシュディレクトリ。省略時は返すのみ
     * @return string 設定前のキャッシュディレクトリ
     */
    function cachedir($dirname = null)
    {
        static $cachedir;
        if ($cachedir === null) {
            $cachedir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtr(__NAMESPACE__, ['\\' => '%']);
            cachedir($cachedir); // for mkdir
        }

        if ($dirname === null) {
            return $cachedir;
        }

        if (!file_exists($dirname)) {
            @mkdir($dirname, 0777 & (~umask()), true);
        }
        $result = $cachedir;
        $cachedir = realpath($dirname);
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\cachedir") && !defined("ryunosuke\\dbml\\cachedir")) {
    define("ryunosuke\\dbml\\cachedir", "ryunosuke\\dbml\\cachedir");
}

if (!isset($excluded_functions["cache"]) && (!function_exists("ryunosuke\\dbml\\cache") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\cache"))->isInternal()))) {
    /**
     * シンプルにキャッシュする
     *
     * この関数は get/set/delete を兼ねる。
     * キャッシュがある場合はそれを返し、ない場合は $provider を呼び出してその結果をキャッシュしつつそれを返す。
     *
     * $provider に null を与えるとキャッシュの削除となる。
     *
     * Example:
     * ```php
     * $provider = function(){return rand();};
     * // 乱数を返す処理だが、キャッシュされるので同じ値になる
     * $rand1 = cache('rand', $provider);
     * $rand2 = cache('rand', $provider);
     * that($rand1)->isSame($rand2);
     * // $provider に null を与えると削除される
     * cache('rand', null);
     * $rand3 = cache('rand', $provider);
     * that($rand1)->isNotSame($rand3);
     * ```
     *
     * @param string $key キャッシュのキー
     * @param callable $provider キャッシュがない場合にコールされる callable
     * @param string $namespace 名前空間
     * @return mixed キャッシュ
     */
    function cache($key, $provider, $namespace = null)
    {
        static $cacheobject;
        $cacheobject = $cacheobject ?? new class(cachedir()) {
                const CACHE_EXT = '.php-cache';

                /** @var string キャッシュディレクトリ */
                private $cachedir;

                /** @var array 内部キャッシュ */
                private $cache;

                /** @var array 変更感知配列 */
                private $changed;

                public function __construct($cachedir)
                {
                    $this->cachedir = $cachedir;
                    $this->cache = [];
                    $this->changed = [];
                }

                public function __destruct()
                {
                    // 変更されているもののみ保存
                    foreach ($this->changed as $namespace => $dummy) {
                        $filepath = $this->cachedir . '/' . rawurlencode($namespace) . self::CACHE_EXT;
                        $content = "<?php\nreturn " . var_export($this->cache[$namespace], true) . ";\n";

                        $temppath = tempnam(sys_get_temp_dir(), 'cache');
                        if (file_put_contents($temppath, $content) !== false) {
                            @chmod($temppath, 0644);
                            if (!@rename($temppath, $filepath)) {
                                @unlink($temppath);
                            }
                        }
                    }
                }

                public function has($namespace, $key)
                {
                    // ファイルから読み込む必要があるので get しておく
                    $this->get($namespace, $key);
                    return array_key_exists($key, $this->cache[$namespace]);
                }

                public function get($namespace, $key)
                {
                    // 名前空間自体がないなら作る or 読む
                    if (!isset($this->cache[$namespace])) {
                        $nsarray = [];
                        $cachpath = $this->cachedir . '/' . rawurldecode($namespace) . self::CACHE_EXT;
                        if (file_exists($cachpath)) {
                            $nsarray = require $cachpath;
                        }
                        $this->cache[$namespace] = $nsarray;
                    }

                    return $this->cache[$namespace][$key] ?? null;
                }

                public function set($namespace, $key, $value)
                {
                    // 新しい値が来たら変更フラグを立てる
                    if (!isset($this->cache[$namespace]) || !array_key_exists($key, $this->cache[$namespace]) || $this->cache[$namespace][$key] !== $value) {
                        $this->changed[$namespace] = true;
                    }

                    $this->cache[$namespace][$key] = $value;
                }

                public function delete($namespace, $key)
                {
                    $this->changed[$namespace] = true;
                    unset($this->cache[$namespace][$key]);
                }

                public function clear()
                {
                    // インメモリ情報をクリアして・・・
                    $this->cache = [];
                    $this->changed = [];

                    // ファイルも消す
                    foreach (glob($this->cachedir . '/*' . self::CACHE_EXT) as $file) {
                        unlink($file);
                    }
                }
            };

        // flush (for test)
        if ($key === null) {
            if ($provider === null) {
                $cacheobject->clear();
            }
            $cacheobject = null;
            return;
        }

        $namespace = $namespace ?? __FILE__;

        $exist = $cacheobject->has($namespace, $key);
        if ($provider === null) {
            $cacheobject->delete($namespace, $key);
            return $exist;
        }
        if (!$exist) {
            $cacheobject->set($namespace, $key, $provider());
        }
        return $cacheobject->get($namespace, $key);
    }
}
if (function_exists("ryunosuke\\dbml\\cache") && !defined("ryunosuke\\dbml\\cache")) {
    define("ryunosuke\\dbml\\cache", "ryunosuke\\dbml\\cache");
}

if (!isset($excluded_functions["arrayval"]) && (!function_exists("ryunosuke\\dbml\\arrayval") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\arrayval"))->isInternal()))) {
    /**
     * array キャストの関数版
     *
     * intval とか strval とかの array 版。
     * ただキャストするだけだが、関数なのでコールバックとして使える。
     *
     * $recursive を true にすると再帰的に適用する（デフォルト）。
     * 入れ子オブジェクトを配列化するときなどに使える。
     *
     * Example:
     * ```php
     * // キャストなので基本的には配列化される
     * that(arrayval(123))->isSame([123]);
     * that(arrayval('str'))->isSame(['str']);
     * that(arrayval([123]))->isSame([123]); // 配列は配列のまま
     *
     * // $recursive = false にしない限り再帰的に適用される
     * $stdclass = stdclass(['key' => 'val']);
     * that(arrayval([$stdclass], true))->isSame([['key' => 'val']]); // true なので中身も配列化される
     * that(arrayval([$stdclass], false))->isSame([$stdclass]);       // false なので中身は変わらない
     * ```
     *
     * @param mixed $var array 化する値
     * @param bool $recursive 再帰的に行うなら true
     * @return array array 化した配列
     */
    function arrayval($var, $recursive = true)
    {
        // return json_decode(json_encode($var), true);

        // 無駄なループを回したくないので非再帰で配列の場合はそのまま返す
        if (!$recursive && is_array($var)) {
            return $var;
        }

        if (is_primitive($var)) {
            return (array) $var;
        }

        $result = [];
        foreach ($var as $k => $v) {
            if ($recursive && !is_primitive($v)) {
                $v = arrayval($v, $recursive);
            }
            $result[$k] = $v;
        }
        return $result;
    }
}
if (function_exists("ryunosuke\\dbml\\arrayval") && !defined("ryunosuke\\dbml\\arrayval")) {
    define("ryunosuke\\dbml\\arrayval", "ryunosuke\\dbml\\arrayval");
}

if (!isset($excluded_functions["is_empty"]) && (!function_exists("ryunosuke\\dbml\\is_empty") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\is_empty"))->isInternal()))) {
    /**
     * 値が空か検査する
     *
     * `empty` とほぼ同じ。ただし
     *
     * - string: "0"
     * - countable でない object
     * - countable である object で count() > 0
     *
     * は false 判定する。
     * ただし、 $empty_stcClass に true を指定すると「フィールドのない stdClass」も true を返すようになる。
     * これは stdClass の立ち位置はかなり特殊で「フィールドアクセスできる組み込み配列」のような扱いをされることが多いため。
     * （例えば `json_decode('{}')` は stdClass を返すが、このような状況は空判定したいことが多いだろう）。
     *
     * なお、関数の仕様上、未定義変数を true 判定することはできない。
     * 未定義変数をチェックしたい状況は大抵の場合コードが悪いが `$array['key1']['key2']` を調べたいことはある。
     * そういう時には使えない（?? する必要がある）。
     *
     * 「 `if ($var) {}` で十分なんだけど "0" が…」という状況はまれによくあるはず。
     *
     * Example:
     * ```php
     * // この辺は empty と全く同じ
     * that(is_empty(null))->isTrue();
     * that(is_empty(false))->isTrue();
     * that(is_empty(0))->isTrue();
     * that(is_empty(''))->isTrue();
     * // この辺だけが異なる
     * that(is_empty('0'))->isFalse();
     * // 第2引数に true を渡すと空の stdClass も empty 判定される
     * $stdclass = new \stdClass();
     * that(is_empty($stdclass, true))->isTrue();
     * // フィールドがあれば empty ではない
     * $stdclass->hoge = 123;
     * that(is_empty($stdclass, true))->isFalse();
     * ```
     *
     * @param mixed $var 判定する値
     * @param bool $empty_stdClass 空の stdClass を空とみなすか
     * @return bool 空なら true
     */
    function is_empty($var, $empty_stdClass = false)
    {
        // object は is_countable 次第
        if (is_object($var)) {
            // が、 stdClass だけは特別扱い（stdClass は継承もできるので、クラス名で判定する（継承していたらそれはもう stdClass ではないと思う））
            if ($empty_stdClass && get_class($var) === 'stdClass') {
                return !(array) $var;
            }
            if (is_countable($var)) {
                return !count($var);
            }
            return false;
        }

        // "0" は false
        if ($var === '0') {
            return false;
        }

        // 上記以外は empty に任せる
        return empty($var);
    }
}
if (function_exists("ryunosuke\\dbml\\is_empty") && !defined("ryunosuke\\dbml\\is_empty")) {
    define("ryunosuke\\dbml\\is_empty", "ryunosuke\\dbml\\is_empty");
}

if (!isset($excluded_functions["is_primitive"]) && (!function_exists("ryunosuke\\dbml\\is_primitive") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\is_primitive"))->isInternal()))) {
    /**
     * 値が複合型でないか検査する
     *
     * 「複合型」とはオブジェクトと配列のこと。
     * つまり
     *
     * - is_scalar($var) || is_null($var) || is_resource($var)
     *
     * と同義（!is_array($var) && !is_object($var) とも言える）。
     *
     * Example:
     * ```php
     * that(is_primitive(null))->isTrue();
     * that(is_primitive(false))->isTrue();
     * that(is_primitive(123))->isTrue();
     * that(is_primitive(STDIN))->isTrue();
     * that(is_primitive(new \stdClass))->isFalse();
     * that(is_primitive(['array']))->isFalse();
     * ```
     *
     * @param mixed $var 調べる値
     * @return bool 複合型なら false
     */
    function is_primitive($var)
    {
        return is_scalar($var) || is_null($var) || is_resource($var);
    }
}
if (function_exists("ryunosuke\\dbml\\is_primitive") && !defined("ryunosuke\\dbml\\is_primitive")) {
    define("ryunosuke\\dbml\\is_primitive", "ryunosuke\\dbml\\is_primitive");
}

if (!isset($excluded_functions["is_stringable"]) && (!function_exists("ryunosuke\\dbml\\is_stringable") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\is_stringable"))->isInternal()))) {
    /**
     * 変数が文字列化できるか調べる
     *
     * 「配列」「__toString を持たないオブジェクト」が false になる。
     * （厳密に言えば配列は "Array" になるので文字列化できるといえるがここでは考えない）。
     *
     * Example:
     * ```php
     * // こいつらは true
     * that(is_stringable(null))->isTrue();
     * that(is_stringable(true))->isTrue();
     * that(is_stringable(3.14))->isTrue();
     * that(is_stringable(STDOUT))->isTrue();
     * that(is_stringable(new \Exception()))->isTrue();
     * // こいつらは false
     * that(is_stringable(new \ArrayObject()))->isFalse();
     * that(is_stringable([1, 2, 3]))->isFalse();
     * ```
     *
     * @param mixed $var 調べる値
     * @return bool 文字列化できるなら true
     */
    function is_stringable($var)
    {
        if (is_array($var)) {
            return false;
        }
        if (is_object($var) && !method_exists($var, '__toString')) {
            return false;
        }
        return true;
    }
}
if (function_exists("ryunosuke\\dbml\\is_stringable") && !defined("ryunosuke\\dbml\\is_stringable")) {
    define("ryunosuke\\dbml\\is_stringable", "ryunosuke\\dbml\\is_stringable");
}

if (!isset($excluded_functions["is_arrayable"]) && (!function_exists("ryunosuke\\dbml\\is_arrayable") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\is_arrayable"))->isInternal()))) {
    /**
     * 変数が配列アクセス可能か調べる
     *
     * Example:
     * ```php
     * that(is_arrayable([]))->isTrue();
     * that(is_arrayable(new \ArrayObject()))->isTrue();
     * that(is_arrayable(new \stdClass()))->isFalse();
     * ```
     *
     * @param array $var 調べる値
     * @return bool 配列アクセス可能なら true
     */
    function is_arrayable($var)
    {
        return is_array($var) || $var instanceof \ArrayAccess;
    }
}
if (function_exists("ryunosuke\\dbml\\is_arrayable") && !defined("ryunosuke\\dbml\\is_arrayable")) {
    define("ryunosuke\\dbml\\is_arrayable", "ryunosuke\\dbml\\is_arrayable");
}

if (!isset($excluded_functions["is_countable"]) && (!function_exists("ryunosuke\\dbml\\is_countable") || (!true && (new \ReflectionFunction("ryunosuke\\dbml\\is_countable"))->isInternal()))) {
    /**
     * 変数が count でカウントできるか調べる
     *
     * 要するに {@link http://php.net/manual/function.is-countable.php is_countable} の polyfill。
     *
     * Example:
     * ```php
     * that(is_countable([1, 2, 3]))->isTrue();
     * that(is_countable(new \ArrayObject()))->isTrue();
     * that(is_countable((function () { yield 1; })()))->isFalse();
     * that(is_countable(1))->isFalse();
     * that(is_countable(new \stdClass()))->isFalse();
     * ```
     *
     * @polyfill
     *
     * @param mixed $var 調べる値
     * @return bool count でカウントできるなら true
     */
    function is_countable($var)
    {
        return is_array($var) || $var instanceof \Countable;
    }
}
if (function_exists("ryunosuke\\dbml\\is_countable") && !defined("ryunosuke\\dbml\\is_countable")) {
    define("ryunosuke\\dbml\\is_countable", "ryunosuke\\dbml\\is_countable");
}

if (!isset($excluded_functions["var_apply"]) && (!function_exists("ryunosuke\\dbml\\var_apply") || (!false && (new \ReflectionFunction("ryunosuke\\dbml\\var_apply"))->isInternal()))) {
    /**
     * 値にコールバックを適用する
     *
     * 普通のスカラー値であれば `$callback($var)` と全く同じ。
     * この関数は「$var が配列だったら中身に適用して返す（再帰）」という点で上記とは異なる。
     *
     * 「配列が与えられたら要素に適用して配列で返す、配列じゃないなら直に適用してそれを返す」という状況はまれによくあるはず。
     *
     * Example:
     * ```php
     * // 素の値は素の呼び出しと同じ
     * that(var_apply(' x ', 'trim'))->isSame('x');
     * // 配列は中身に適用して配列で返す（再帰）
     * that(var_apply([' x ', ' y ', [' z ']], 'trim'))->isSame(['x', 'y', ['z']]);
     * // 第3引数以降は残り引数を意味する
     * that(var_apply(['!x!', '!y!'], 'trim', '!'))->isSame(['x', 'y']);
     * // 「まれによくある」の具体例
     * that(var_apply(['<x>', ['<y>']], 'htmlspecialchars', ENT_QUOTES, 'utf-8'))->isSame(['&lt;x&gt;', ['&lt;y&gt;']]);
     * ```
     *
     * @param mixed $var $callback を適用する値
     * @param callable $callback 値変換コールバック
     * @param array $args $callback の残り引数（可変引数）
     * @return mixed|array $callback が適用された値。元が配列なら配列で返す
     */
    function var_apply($var, $callback, ...$args)
    {
        $iterable = is_iterable($var);
        if ($iterable) {
            $result = [];
            foreach ($var as $k => $v) {
                $result[$k] = var_apply($v, $callback, ...$args);
            }
            return $result;
        }

        return $callback($var, ...$args);
    }
}
if (function_exists("ryunosuke\\dbml\\var_apply") && !defined("ryunosuke\\dbml\\var_apply")) {
    define("ryunosuke\\dbml\\var_apply", "ryunosuke\\dbml\\var_apply");
}
