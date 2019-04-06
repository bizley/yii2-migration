<?php

namespace bizley\tests\sqlite;

use bizley\tests\cases\UpdaterTestCase;

/**
 * @group sqlite
 */
class UpdaterTest extends UpdaterTestCase
{
    public static $schema = 'sqlite';

    public function testDropPrimaryKey()
    {
        $this->markTestSkipped('SQLite does not support DROP PRIMARY KEY');
    }

    public function testDropForeignKey()
    {
        $this->markTestSkipped('SQLite does not support DROP FOREIGN KEY');
    }

    public function testAddForeignKey()
    {
        $this->markTestSkipped('SQLite does not support ADD FOREIGN KEY');
    }

    public function testMultipleMigrations()
    {
        $this->markTestSkipped('SQLite does not support DROP COLUMN');
    }
}
