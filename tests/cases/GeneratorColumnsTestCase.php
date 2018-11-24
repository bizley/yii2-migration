<?php

namespace bizley\tests\cases;

use bizley\migration\Generator;
use Yii;
use yii\db\Schema;

class GeneratorColumnsTestCase extends DbTestCase
{
    protected function getGenerator()
    {
        return new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_columns',
        ]);
    }

    public function testColumnBin()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_bin', $table->columns);
        $this->assertInstanceOf('bizley\migration\table\TableColumnBinary', $table->columns['col_bin']);
        $this->assertEquals('col_bin', $table->columns['col_bin']->name);
        $this->assertEquals(Schema::TYPE_BINARY, $table->columns['col_bin']->type);
        $this->assertEquals(null, $table->columns['col_bin']->length);
    }

    public function testColumnDate()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_date', $table->columns);
        $this->assertInstanceOf('bizley\migration\table\TableColumnDate', $table->columns['col_date']);
        $this->assertEquals('col_date', $table->columns['col_date']->name);
        $this->assertEquals(Schema::TYPE_DATE, $table->columns['col_date']->type);
        $this->assertEquals(null, $table->columns['col_date']->length);
    }

    public function testColumnText()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_text', $table->columns);
        $this->assertInstanceOf('bizley\migration\table\TableColumnText', $table->columns['col_text']);
        $this->assertEquals('col_text', $table->columns['col_text']->name);
        $this->assertEquals(Schema::TYPE_TEXT, $table->columns['col_text']->type);
        $this->assertEquals(null, $table->columns['col_text']->length);
    }

    public function testColumnTime()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_time', $table->columns);
        $this->assertInstanceOf('bizley\migration\table\TableColumnTime', $table->columns['col_time']);
        $this->assertEquals('col_time', $table->columns['col_time']->name);
        $this->assertEquals(Schema::TYPE_TIME, $table->columns['col_time']->type);
        $this->assertEquals(null, $table->columns['col_time']->length);
    }

    public function testColumnTimestamp()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_timestamp', $table->columns);
        $this->assertInstanceOf('bizley\migration\table\TableColumnTimestamp', $table->columns['col_timestamp']);
        $this->assertEquals('col_timestamp', $table->columns['col_timestamp']->name);
        $this->assertEquals(Schema::TYPE_TIMESTAMP, $table->columns['col_timestamp']->type);
        $this->assertEquals(null, $table->columns['col_timestamp']->length);
    }
}
