<?php

namespace bizley\migration\tests\mysql;

use bizley\migration\tests\DbTestCase;

abstract class MysqlDbTestCase extends DbTestCase
{
    public static function setUpBeforeClass()
    {
        static::$database = static::getParam('mysql');
        parent::setUpBeforeClass();
    }
}
