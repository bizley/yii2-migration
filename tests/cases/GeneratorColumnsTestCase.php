<?php declare(strict_types=1);

namespace bizley\tests\cases;

use bizley\migration\Generator;
use bizley\migration\table\TableColumnBinary;
use bizley\migration\table\TableColumnDate;
use bizley\migration\table\TableColumnText;
use bizley\migration\table\TableColumnTime;
use bizley\migration\table\TableColumnTimestamp;
use Yii;
use yii\db\Schema;

class GeneratorColumnsTestCase extends DbTestCase
{
    protected function getGenerator(): Generator
    {
        return new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_columns',
        ]);
    }

    public function testColumnBin(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_bin', $table->columns);
        $this->assertInstanceOf(TableColumnBinary::class, $table->columns['col_bin']);
        $this->assertEquals('col_bin', $table->columns['col_bin']->name);
        $this->assertEquals(Schema::TYPE_BINARY, $table->columns['col_bin']->type);
        $this->assertEquals(null, $table->columns['col_bin']->length);
    }

    public function testColumnDate(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_date', $table->columns);
        $this->assertInstanceOf(TableColumnDate::class, $table->columns['col_date']);
        $this->assertEquals('col_date', $table->columns['col_date']->name);
        $this->assertEquals(Schema::TYPE_DATE, $table->columns['col_date']->type);
        $this->assertEquals(null, $table->columns['col_date']->length);
    }

    public function testColumnText(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_text', $table->columns);
        $this->assertInstanceOf(TableColumnText::class, $table->columns['col_text']);
        $this->assertEquals('col_text', $table->columns['col_text']->name);
        $this->assertEquals(Schema::TYPE_TEXT, $table->columns['col_text']->type);
        $this->assertEquals(null, $table->columns['col_text']->length);
    }

    public function testColumnTime(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_time', $table->columns);
        $this->assertInstanceOf(TableColumnTime::class, $table->columns['col_time']);
        $this->assertEquals('col_time', $table->columns['col_time']->name);
        $this->assertEquals(Schema::TYPE_TIME, $table->columns['col_time']->type);
        $this->assertEquals(null, $table->columns['col_time']->length);
    }

    public function testColumnTimestamp(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_timestamp', $table->columns);
        $this->assertInstanceOf(TableColumnTimestamp::class, $table->columns['col_timestamp']);
        $this->assertEquals('col_timestamp', $table->columns['col_timestamp']->name);
        $this->assertEquals(Schema::TYPE_TIMESTAMP, $table->columns['col_timestamp']->type);
        $this->assertEquals(null, $table->columns['col_timestamp']->length);
    }
}
