<?php declare(strict_types=1);

namespace bizley\tests\mysql;

/**
 * @group mysql
 */
class MigrationControllerTest extends \bizley\tests\cases\MigrationControllerTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';
}
