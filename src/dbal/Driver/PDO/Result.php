<?php

if (interface_exists(\Doctrine\DBAL\Exception::class)) {
    require_once __DIR__ . '/Result.v4.php';
}
else {
    require_once __DIR__ . '/Result.v3.php';
}
