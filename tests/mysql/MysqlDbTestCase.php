<?php

namespace bizley\migration\tests\mysql;

use bizley\migration\tests\DbTestCase;

abstract class MysqlDbTestCase extends DbTestCase
{
    public static $params;
    protected static $driverName = 'mysql';
    protected static $database = [
        'dsn' => 'mysql:host=127.0.0.1;dbname=test',
        'username' => 'root',
        'password' => '',
    ];
}
