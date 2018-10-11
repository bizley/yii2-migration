<?php declare(strict_types=1);

namespace bizley\tests\mysql;

use bizley\migration\Generator;
use bizley\migration\table\TableColumnBigInt;
use bizley\migration\table\TableColumnBinary;
use bizley\migration\table\TableColumnChar;
use bizley\migration\table\TableColumnDate;
use bizley\migration\table\TableColumnDateTime;
use bizley\migration\table\TableColumnDecimal;
use bizley\migration\table\TableColumnDouble;
use bizley\migration\table\TableColumnFloat;
use bizley\migration\table\TableColumnInt;
use bizley\migration\table\TableColumnJson;
use bizley\migration\table\TableColumnSmallInt;
use bizley\migration\table\TableColumnString;
use bizley\migration\table\TableColumnText;
use bizley\migration\table\TableColumnTime;
use bizley\migration\table\TableColumnTimestamp;
use bizley\migration\table\TableColumnTinyInt;
use Yii;
use yii\db\Schema;

class GeneratorColumnsTest extends MysqlDbTestCase
{
    protected function getGenerator(): Generator
    {
        return new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_columns',
        ]);
    }

    public function testColumnBigInt(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_big_int', $table->columns);
        $this->assertInstanceOf(TableColumnBigInt::class, $table->columns['col_big_int']);
        $this->assertEquals('col_big_int', $table->columns['col_big_int']->name);
        $this->assertEquals(Schema::TYPE_BIGINT, $table->columns['col_big_int']->type);
        $this->assertEquals(20, $table->columns['col_big_int']->size);
        $this->assertEquals(20, $table->columns['col_big_int']->precision);
        $this->assertEquals(null, $table->columns['col_big_int']->scale);
    }

    public function testColumnInt(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_int', $table->columns);
        $this->assertInstanceOf(TableColumnInt::class, $table->columns['col_int']);
        $this->assertEquals('col_int', $table->columns['col_int']->name);
        $this->assertEquals(Schema::TYPE_INTEGER, $table->columns['col_int']->type);
        $this->assertEquals(11, $table->columns['col_int']->size);
        $this->assertEquals(11, $table->columns['col_int']->precision);
        $this->assertEquals(null, $table->columns['col_int']->scale);
    }

    public function testColumnSmallInt(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_small_int', $table->columns);
        $this->assertInstanceOf(TableColumnSmallInt::class, $table->columns['col_small_int']);
        $this->assertEquals('col_small_int', $table->columns['col_small_int']->name);
        $this->assertEquals(Schema::TYPE_SMALLINT, $table->columns['col_small_int']->type);
        $this->assertEquals(6, $table->columns['col_small_int']->size);
        $this->assertEquals(6, $table->columns['col_small_int']->precision);
        $this->assertEquals(null, $table->columns['col_small_int']->scale);
    }

    public function testColumnTinyInt(): void
    {
        $table = (new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_tinyint',
        ]))->table;
        $this->assertArrayHasKey('col_tiny_int', $table->columns);
        $this->assertInstanceOf(TableColumnTinyInt::class, $table->columns['col_tiny_int']);
        $this->assertEquals('col_tiny_int', $table->columns['col_tiny_int']->name);
        $this->assertEquals(Schema::TYPE_TINYINT, $table->columns['col_tiny_int']->type);
        $this->assertEquals(3, $table->columns['col_tiny_int']->size);
        $this->assertEquals(3, $table->columns['col_tiny_int']->precision);
        $this->assertEquals(null, $table->columns['col_tiny_int']->scale);
    }

    public function testColumnBin(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_bin', $table->columns);
        $this->assertInstanceOf(TableColumnBinary::class, $table->columns['col_bin']);
        $this->assertEquals('col_bin', $table->columns['col_bin']->name);
        $this->assertEquals(Schema::TYPE_BINARY, $table->columns['col_bin']->type);
        $this->assertEquals(null, $table->columns['col_bin']->size);
        $this->assertEquals(null, $table->columns['col_bin']->precision);
        $this->assertEquals(null, $table->columns['col_bin']->scale);
    }

    public function testColumnBool(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_bool', $table->columns);
        $this->assertEquals('col_bool', $table->columns['col_bool']->name);
        if (\defined('yii\db\Schema::TYPE_TINYINT')) {
            $this->assertInstanceOf(TableColumnTinyInt::class, $table->columns['col_bool']);
            $this->assertEquals(Schema::TYPE_TINYINT, $table->columns['col_bool']->type);
        } else {
            $this->assertInstanceOf(TableColumnSmallInt::class, $table->columns['col_bool']);
            $this->assertEquals(Schema::TYPE_SMALLINT, $table->columns['col_bool']->type);
        }
        $this->assertEquals(1, $table->columns['col_bool']->size);
        $this->assertEquals(1, $table->columns['col_bool']->precision);
        $this->assertEquals(null, $table->columns['col_bool']->scale);
    }

    public function testColumnChar(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_char', $table->columns);
        $this->assertInstanceOf(TableColumnChar::class, $table->columns['col_char']);
        $this->assertEquals('col_char', $table->columns['col_char']->name);
        $this->assertEquals(Schema::TYPE_CHAR, $table->columns['col_char']->type);
        $this->assertEquals(1, $table->columns['col_char']->size);
        $this->assertEquals(1, $table->columns['col_char']->precision);
        $this->assertEquals(null, $table->columns['col_char']->scale);
    }

    public function testColumnDate(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_date', $table->columns);
        $this->assertInstanceOf(TableColumnDate::class, $table->columns['col_date']);
        $this->assertEquals('col_date', $table->columns['col_date']->name);
        $this->assertEquals(Schema::TYPE_DATE, $table->columns['col_date']->type);
        $this->assertEquals(null, $table->columns['col_date']->size);
        $this->assertEquals(null, $table->columns['col_date']->precision);
        $this->assertEquals(null, $table->columns['col_date']->scale);
    }

    public function testColumnDateTime(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_date_time', $table->columns);
        $this->assertInstanceOf(TableColumnDateTime::class, $table->columns['col_date_time']);
        $this->assertEquals('col_date_time', $table->columns['col_date_time']->name);
        $this->assertEquals(Schema::TYPE_DATETIME, $table->columns['col_date_time']->type);
        $this->assertEquals(null, $table->columns['col_date_time']->size);
        $this->assertEquals(null, $table->columns['col_date_time']->precision);
        $this->assertEquals(null, $table->columns['col_date_time']->scale);
    }

    public function testColumnDecimal(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_decimal', $table->columns);
        $this->assertInstanceOf(TableColumnDecimal::class, $table->columns['col_decimal']);
        $this->assertEquals('col_decimal', $table->columns['col_decimal']->name);
        $this->assertEquals(Schema::TYPE_DECIMAL, $table->columns['col_decimal']->type);
        $this->assertEquals(10, $table->columns['col_decimal']->size);
        $this->assertEquals(10, $table->columns['col_decimal']->precision);
        $this->assertEquals(null, $table->columns['col_decimal']->scale);
    }

    public function testColumnDouble(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_double', $table->columns);
        $this->assertInstanceOf(TableColumnDouble::class, $table->columns['col_double']);
        $this->assertEquals('col_double', $table->columns['col_double']->name);
        $this->assertEquals(Schema::TYPE_DOUBLE, $table->columns['col_double']->type);
        $this->assertEquals(null, $table->columns['col_double']->size);
        $this->assertEquals(null, $table->columns['col_double']->precision);
        $this->assertEquals(null, $table->columns['col_double']->scale);
    }

    public function testColumnFloat(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_float', $table->columns);
        $this->assertInstanceOf(TableColumnFloat::class, $table->columns['col_float']);
        $this->assertEquals('col_float', $table->columns['col_float']->name);
        $this->assertEquals(Schema::TYPE_FLOAT, $table->columns['col_float']->type);
        $this->assertEquals(null, $table->columns['col_float']->size);
        $this->assertEquals(null, $table->columns['col_float']->precision);
        $this->assertEquals(null, $table->columns['col_float']->scale);
    }

    public function testColumnMoney(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_money', $table->columns);
        $this->assertInstanceOf(TableColumnDecimal::class, $table->columns['col_money']);
        $this->assertEquals('col_money', $table->columns['col_money']->name);
        $this->assertEquals(Schema::TYPE_DECIMAL, $table->columns['col_money']->type);
        $this->assertEquals(19, $table->columns['col_money']->size);
        $this->assertEquals(19, $table->columns['col_money']->precision);
        $this->assertEquals(4, $table->columns['col_money']->scale);
    }

    public function testColumnString(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_string', $table->columns);
        $this->assertInstanceOf(TableColumnString::class, $table->columns['col_string']);
        $this->assertEquals('col_string', $table->columns['col_string']->name);
        $this->assertEquals(Schema::TYPE_STRING, $table->columns['col_string']->type);
        $this->assertEquals(255, $table->columns['col_string']->size);
        $this->assertEquals(255, $table->columns['col_string']->precision);
        $this->assertEquals(null, $table->columns['col_string']->scale);
    }

    public function testColumnText(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_text', $table->columns);
        $this->assertInstanceOf(TableColumnText::class, $table->columns['col_text']);
        $this->assertEquals('col_text', $table->columns['col_text']->name);
        $this->assertEquals(Schema::TYPE_TEXT, $table->columns['col_text']->type);
        $this->assertEquals(null, $table->columns['col_text']->size);
        $this->assertEquals(null, $table->columns['col_text']->precision);
        $this->assertEquals(null, $table->columns['col_text']->scale);
    }

    public function testColumnTime(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_time', $table->columns);
        $this->assertInstanceOf(TableColumnTime::class, $table->columns['col_time']);
        $this->assertEquals('col_time', $table->columns['col_time']->name);
        $this->assertEquals(Schema::TYPE_TIME, $table->columns['col_time']->type);
        $this->assertEquals(null, $table->columns['col_time']->size);
        $this->assertEquals(null, $table->columns['col_time']->precision);
        $this->assertEquals(null, $table->columns['col_time']->scale);
    }

    public function testColumnTimestamp(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_timestamp', $table->columns);
        $this->assertInstanceOf(TableColumnTimestamp::class, $table->columns['col_timestamp']);
        $this->assertEquals('col_timestamp', $table->columns['col_timestamp']->name);
        $this->assertEquals(Schema::TYPE_TIMESTAMP, $table->columns['col_timestamp']->type);
        $this->assertEquals(null, $table->columns['col_timestamp']->size);
        $this->assertEquals(null, $table->columns['col_timestamp']->precision);
        $this->assertEquals(null, $table->columns['col_timestamp']->scale);
    }

    public function testColumnJson(): void
    {
        $table = (new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_json',
        ]))->table;
        $this->assertArrayHasKey('col_json', $table->columns);
        $this->assertInstanceOf(TableColumnJson::class, $table->columns['col_json']);
        $this->assertEquals('col_json', $table->columns['col_json']->name);
        $this->assertEquals(Schema::TYPE_JSON, $table->columns['col_json']->type);
        $this->assertEquals(null, $table->columns['col_json']->size);
        $this->assertEquals(null, $table->columns['col_json']->precision);
        $this->assertEquals(null, $table->columns['col_json']->scale);
    }
}
