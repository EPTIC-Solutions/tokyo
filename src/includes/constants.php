<?php

if (!defined('TOKYO_ROOT')) {
    define('TOKYO_ROOT', $_SERVER['HOME'] . '/.config/tokyo');
}

define('TOKYO_SERVER', realpath(__DIR__ . '/../../server/server.php'));
