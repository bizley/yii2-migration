<?php declare(strict_types=1);

namespace bizley\tests\sqlite;

/**
 * @group sqlite
 */
class UpdaterTest extends \bizley\tests\cases\UpdaterTestCase
{
    public static $schema = 'sqlite';

    public function testDropPrimaryKey(): void
    {
        $this->markTestSkipped('SQLite does not support DROP PRIMARY KEY');
    }

    public function testDropForeignKey(): void
    {
        $this->markTestSkipped('SQLite does not support DROP FOREIGN KEY');
    }

    public function testAddForeignKey(): void
    {
        $this->markTestSkipped('SQLite does not support ADD FOREIGN KEY');
    }

    public function testMultipleMigrations(): void
    {
        $this->markTestSkipped('SQLite does not support DROP COLUMN');
    }
}
