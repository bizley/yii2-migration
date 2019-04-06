<?php

namespace bizley\tests\mysql;

use bizley\tests\cases\MigrationControllerTestCase;

/**
 * @group mysql
 */
class MigrationControllerTest extends MigrationControllerTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';
}
