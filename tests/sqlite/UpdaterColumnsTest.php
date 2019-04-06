<?php

namespace bizley\tests\sqlite;

use bizley\tests\cases\UpdaterColumnsTestCase;

/**
 * @group sqlite
 */
class UpdaterColumnsTest extends UpdaterColumnsTestCase
{
    public static $schema = 'sqlite';

    public function testChangeSizeGeneral()
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testNoChangeSizeSpecific()
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testNoChangePKSpecific()
    {
        $this->markTestSkipped('SQLite does not support DROP/ADD PRIMARY KEY');
    }

    public function testChangeScaleGeneral()
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testChangeScaleSpecific()
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testChangeColumnType()
    {
        $this->markTestSkipped('SQLite does not support ALTER COLUMN');
    }

    public function testDropColumn()
    {
        $this->markTestSkipped('SQLite does not support DROP COLUMN');
    }
}
