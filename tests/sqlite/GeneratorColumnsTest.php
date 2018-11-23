<?php declare(strict_types=1);

namespace bizley\tests\sqlite;

use bizley\migration\Generator;
use bizley\migration\table\TableColumnBigInt;
use bizley\migration\table\TableColumnBoolean;
use bizley\migration\table\TableColumnChar;
use bizley\migration\table\TableColumnDateTime;
use bizley\migration\table\TableColumnDecimal;
use bizley\migration\table\TableColumnDouble;
use bizley\migration\table\TableColumnFloat;
use bizley\migration\table\TableColumnInt;
use bizley\migration\table\TableColumnSmallInt;
use bizley\migration\table\TableColumnString;
use bizley\migration\table\TableColumnTinyInt;
use Yii;
use yii\db\mysql\Schema;

/**
 * @group sqlite
 */
class GeneratorColumnsTest extends \bizley\tests\cases\GeneratorColumnsTestCase
{
    public static $schema = 'sqlite';

    public function testColumnBigInt(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_big_int', $table->columns);
        $this->assertInstanceOf(TableColumnBigInt::class, $table->columns['col_big_int']);
        $this->assertEquals('col_big_int', $table->columns['col_big_int']->name);
        $this->assertEquals(Schema::TYPE_BIGINT, $table->columns['col_big_int']->type);
        $this->assertEquals(null, $table->columns['col_big_int']->length);
    }

    public function testColumnInt(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_int', $table->columns);
        $this->assertInstanceOf(TableColumnInt::class, $table->columns['col_int']);
        $this->assertEquals('col_int', $table->columns['col_int']->name);
        $this->assertEquals(Schema::TYPE_INTEGER, $table->columns['col_int']->type);
        $this->assertEquals(null, $table->columns['col_int']->length);
    }

    public function testColumnSmallInt(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_small_int', $table->columns);
        $this->assertInstanceOf(TableColumnSmallInt::class, $table->columns['col_small_int']);
        $this->assertEquals('col_small_int', $table->columns['col_small_int']->name);
        $this->assertEquals(Schema::TYPE_SMALLINT, $table->columns['col_small_int']->type);
        $this->assertEquals(null, $table->columns['col_small_int']->length);
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
        $this->assertEquals(null, $table->columns['col_tiny_int']->length);
    }

    public function testColumnBool(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_bool', $table->columns);
        $this->assertEquals('col_bool', $table->columns['col_bool']->name);
        $this->assertInstanceOf(TableColumnBoolean::class, $table->columns['col_bool']);
        $this->assertEquals(Schema::TYPE_BOOLEAN, $table->columns['col_bool']->type);
        $this->assertEquals(null, $table->columns['col_bool']->length);
    }

    public function testColumnChar(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_char', $table->columns);
        $this->assertInstanceOf(TableColumnChar::class, $table->columns['col_char']);
        $this->assertEquals('col_char', $table->columns['col_char']->name);
        $this->assertEquals(Schema::TYPE_CHAR, $table->columns['col_char']->type);
        $this->assertEquals(1, $table->columns['col_char']->length);
    }

    public function testColumnDateTime(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_date_time', $table->columns);
        $this->assertInstanceOf(TableColumnDateTime::class, $table->columns['col_date_time']);
        $this->assertEquals('col_date_time', $table->columns['col_date_time']->name);
        $this->assertEquals(Schema::TYPE_DATETIME, $table->columns['col_date_time']->type);
        $this->assertEquals(null, $table->columns['col_date_time']->length);
    }

    public function testColumnDecimal(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_decimal', $table->columns);
        $this->assertInstanceOf(TableColumnDecimal::class, $table->columns['col_decimal']);
        $this->assertEquals('col_decimal', $table->columns['col_decimal']->name);
        $this->assertEquals(Schema::TYPE_DECIMAL, $table->columns['col_decimal']->type);
        $this->assertEquals(10, $table->columns['col_decimal']->length);
    }

    public function testColumnDouble(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_double', $table->columns);
        $this->assertInstanceOf(TableColumnDouble::class, $table->columns['col_double']);
        $this->assertEquals('col_double', $table->columns['col_double']->name);
        $this->assertEquals(Schema::TYPE_DOUBLE, $table->columns['col_double']->type);
        $this->assertEquals(null, $table->columns['col_double']->length);
    }

    public function testColumnFloat(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_float', $table->columns);
        $this->assertInstanceOf(TableColumnFloat::class, $table->columns['col_float']);
        $this->assertEquals('col_float', $table->columns['col_float']->name);
        $this->assertEquals(Schema::TYPE_FLOAT, $table->columns['col_float']->type);
        $this->assertEquals(null, $table->columns['col_float']->length);
    }

    public function testColumnMoney(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_money', $table->columns);
        $this->assertInstanceOf(TableColumnDecimal::class, $table->columns['col_money']);
        $this->assertEquals('col_money', $table->columns['col_money']->name);
        $this->assertEquals(Schema::TYPE_DECIMAL, $table->columns['col_money']->type);
        $this->assertEquals('19, 4', $table->columns['col_money']->length);
    }

    public function testColumnString(): void
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_string', $table->columns);
        $this->assertInstanceOf(TableColumnString::class, $table->columns['col_string']);
        $this->assertEquals('col_string', $table->columns['col_string']->name);
        $this->assertEquals(Schema::TYPE_STRING, $table->columns['col_string']->type);
        $this->assertEquals(255, $table->columns['col_string']->length);
    }

    public function testColumnJson(): void
    {
        $table = (new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_json',
        ]))->table;
        $this->assertArrayHasKey('col_json', $table->columns);
        $this->assertInstanceOf(TableColumnString::class, $table->columns['col_json']);
        $this->assertEquals('col_json', $table->columns['col_json']->name);
        $this->assertEquals(Schema::TYPE_STRING, $table->columns['col_json']->type);
        $this->assertEquals(null, $table->columns['col_json']->length);
    }
}
