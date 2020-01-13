<?php

/**
 * You can override configuration values by creating a `config.local.php` file and manipulate the `$config` variable.
 */

$config = [
    'mysql' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=migrationtest',
        'username' => 'migration',
        'password' => 'migration',
        'charset' => 'utf8',
    ],
    'pgsql' => [
        'dsn' => 'pgsql:host=127.0.0.1;dbname=migrationtest;port=5432',
        'username' => 'postgres',
        'password' => 'postgres',
        'charset' => 'utf8',
    ],
    'sqlite' => [
        'dsn' => 'sqlite::memory:',
    ],
];
if (is_file(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}
return $config;
