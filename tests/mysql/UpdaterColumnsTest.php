<?php declare(strict_types=1);

namespace bizley\tests\mysql;

/**
 * @group mysql
 */
class UpdaterColumnsTest extends \bizley\tests\cases\UpdaterColumnsTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';
}
