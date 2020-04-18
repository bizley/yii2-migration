<?php

/**
 * You can override configuration values by creating a `config.local.php` file and manipulate the `$config` variable.
 */

$config = [
    'mysql' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=migration',
        'username' => 'migration',
        'password' => 'password',
        'charset' => 'utf8mb4',
    ],
    'pgsql' => [
        'dsn' => 'pgsql:host=127.0.0.1;dbname=migration',
        'username' => 'migration',
        'password' => 'password',
    ],
    'sqlite' => [
        'dsn' => 'sqlite::memory:',
    ],
];
if (is_file(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}
return $config;
