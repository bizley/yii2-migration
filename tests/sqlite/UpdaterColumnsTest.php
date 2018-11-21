<?php declare(strict_types=1);

namespace bizley\tests\sqlite;

/**
 * @group sqlite
 */
class UpdaterColumnsTest extends \bizley\tests\cases\UpdaterColumnsTestCase
{
    public static $schema = 'sqlite';

    public function testChangeSizeGeneral(): void
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testNoChangeSizeSpecific(): void
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testNoChangePKSpecific(): void
    {
        $this->markTestSkipped('SQLite does not support DROP/ADD PRIMARY KEY');
    }

    public function testChangeScaleGeneral(): void
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testChangeScaleSpecific(): void
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testChangeColumnType(): void
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testDropColumn(): void
    {
        $this->markTestSkipped('SQLite does not support DROP COLUMN');
    }
}
