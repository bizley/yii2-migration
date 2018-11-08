<?php declare(strict_types=1);

namespace bizley\tests\mysql;

class UpdaterTest extends \bizley\tests\cases\UpdaterTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';
}
