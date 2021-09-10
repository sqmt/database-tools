<?php

use think\facade\Db;

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/config.local.php')) {
    $config = include __DIR__ . '/config.local.php';
} else {
    $config = include __DIR__ . '/config.php';
}

Db::setConfig($config);