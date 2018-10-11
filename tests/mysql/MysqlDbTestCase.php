<?php

namespace bizley\tests\mysql;

use bizley\tests\DbTestCase;

abstract class MysqlDbTestCase extends DbTestCase
{
    public static function setUpBeforeClass()
    {
        static::$database = static::getParam('mysql');
        parent::setUpBeforeClass();
    }
}
