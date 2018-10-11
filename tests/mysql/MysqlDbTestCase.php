<?php declare(strict_types=1);

namespace bizley\tests\mysql;

use bizley\tests\DbTestCase;

abstract class MysqlDbTestCase extends DbTestCase
{
    public static function setUpBeforeClass(): void
    {
        static::$database = static::getParam('mysql');
        parent::setUpBeforeClass();
    }
}
