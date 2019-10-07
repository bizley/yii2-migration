<?php

namespace bizley\tests\mysql;

use bizley\migration\Generator;
use bizley\migration\table\TableColumnBigInt;
use bizley\migration\table\TableColumnChar;
use bizley\migration\table\TableColumnDateTime;
use bizley\migration\table\TableColumnDecimal;
use bizley\migration\table\TableColumnDouble;
use bizley\migration\table\TableColumnFloat;
use bizley\migration\table\TableColumnInt;
use bizley\migration\table\TableColumnJson;
use bizley\migration\table\TableColumnSmallInt;
use bizley\migration\table\TableColumnString;
use bizley\migration\table\TableColumnTinyInt;
use bizley\tests\cases\GeneratorColumnsTestCase;
use Yii;
use yii\db\Migration;
use yii\db\mysql\Schema;

/**
 * @group mysql
 */
class GeneratorColumnsTest extends GeneratorColumnsTestCase
{
    public static $schema = 'mysql';

    public function testColumnBigInt()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_big_int', $table->columns);
        $this->assertInstanceOf(TableColumnBigInt::className(), $table->columns['col_big_int']);
        $this->assertEquals('col_big_int', $table->columns['col_big_int']->name);
        $this->assertEquals(Schema::TYPE_BIGINT, $table->columns['col_big_int']->type);
        $this->assertEquals(20, $table->columns['col_big_int']->length);
    }

    public function testColumnInt()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_int', $table->columns);
        $this->assertInstanceOf(TableColumnInt::className(), $table->columns['col_int']);
        $this->assertEquals('col_int', $table->columns['col_int']->name);
        $this->assertEquals(Schema::TYPE_INTEGER, $table->columns['col_int']->type);
        $this->assertEquals(11, $table->columns['col_int']->length);
    }

    public function testColumnSmallInt()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_small_int', $table->columns);
        $this->assertInstanceOf(TableColumnSmallInt::className(), $table->columns['col_small_int']);
        $this->assertEquals('col_small_int', $table->columns['col_small_int']->name);
        $this->assertEquals(Schema::TYPE_SMALLINT, $table->columns['col_small_int']->type);
        $this->assertEquals(6, $table->columns['col_small_int']->length);
    }

    public function testColumnTinyInt()
    {
        if (!method_exists(Migration::className(), 'tinyInteger')) {
            $this->markTestSkipped('TinyInt is supported since Yii 2.0.14.');
        }

        $table = (new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_tinyint',
        ]))->table;

        $this->assertArrayHasKey('col_tiny_int', $table->columns);
        $this->assertInstanceOf(TableColumnTinyInt::className(), $table->columns['col_tiny_int']);
        $this->assertEquals('col_tiny_int', $table->columns['col_tiny_int']->name);
        $this->assertEquals(Schema::TYPE_TINYINT, $table->columns['col_tiny_int']->type);
        $this->assertEquals(3, $table->columns['col_tiny_int']->length);
    }

    public function testColumnBool()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_bool', $table->columns);
        $this->assertEquals('col_bool', $table->columns['col_bool']->name);

        if (defined('yii\db\Schema::TYPE_TINYINT')) {
            $this->assertInstanceOf(TableColumnTinyInt::className(), $table->columns['col_bool']);
            $this->assertEquals(Schema::TYPE_TINYINT, $table->columns['col_bool']->type);
        } else {
            $this->assertInstanceOf(TableColumnSmallInt::className(), $table->columns['col_bool']);
            $this->assertEquals(Schema::TYPE_SMALLINT, $table->columns['col_bool']->type);
        }

        $this->assertEquals(1, $table->columns['col_bool']->length);
    }

    public function testColumnChar()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_char', $table->columns);
        $this->assertInstanceOf(TableColumnChar::className(), $table->columns['col_char']);
        $this->assertEquals('col_char', $table->columns['col_char']->name);
        $this->assertEquals(Schema::TYPE_CHAR, $table->columns['col_char']->type);
        $this->assertEquals(1, $table->columns['col_char']->length);
    }

    public function testColumnDateTime()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_date_time', $table->columns);
        $this->assertInstanceOf(TableColumnDateTime::className(), $table->columns['col_date_time']);
        $this->assertEquals('col_date_time', $table->columns['col_date_time']->name);
        $this->assertEquals(Schema::TYPE_DATETIME, $table->columns['col_date_time']->type);
        $this->assertEquals(null, $table->columns['col_date_time']->length);
    }

    public function testColumnDecimal()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_decimal', $table->columns);
        $this->assertInstanceOf(TableColumnDecimal::className(), $table->columns['col_decimal']);
        $this->assertEquals('col_decimal', $table->columns['col_decimal']->name);
        $this->assertEquals(Schema::TYPE_DECIMAL, $table->columns['col_decimal']->type);
        $this->assertEquals('10, 0', $table->columns['col_decimal']->length);
    }

    public function testColumnDouble()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_double', $table->columns);
        $this->assertInstanceOf(TableColumnDouble::className(), $table->columns['col_double']);
        $this->assertEquals('col_double', $table->columns['col_double']->name);
        $this->assertEquals(Schema::TYPE_DOUBLE, $table->columns['col_double']->type);
        $this->assertEquals(null, $table->columns['col_double']->length);
    }

    public function testColumnFloat()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_float', $table->columns);
        $this->assertInstanceOf(TableColumnFloat::className(), $table->columns['col_float']);
        $this->assertEquals('col_float', $table->columns['col_float']->name);
        $this->assertEquals(Schema::TYPE_FLOAT, $table->columns['col_float']->type);
        $this->assertEquals(null, $table->columns['col_float']->length);
    }

    public function testColumnMoney()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_money', $table->columns);
        $this->assertInstanceOf(TableColumnDecimal::className(), $table->columns['col_money']);
        $this->assertEquals('col_money', $table->columns['col_money']->name);
        $this->assertEquals(Schema::TYPE_DECIMAL, $table->columns['col_money']->type);
        $this->assertEquals('19, 4', $table->columns['col_money']->length);
    }

    public function testColumnString()
    {
        $table = $this->getGenerator()->table;
        $this->assertArrayHasKey('col_string', $table->columns);
        $this->assertInstanceOf(TableColumnString::className(), $table->columns['col_string']);
        $this->assertEquals('col_string', $table->columns['col_string']->name);
        $this->assertEquals(Schema::TYPE_STRING, $table->columns['col_string']->type);
        $this->assertEquals(255, $table->columns['col_string']->length);
    }

    public function testColumnJson()
    {
        if (!method_exists(Migration::className(), 'json')) {
            $this->markTestSkipped('Json is supported since Yii 2.0.14.');
        }

        if (version_compare(PHP_VERSION, '5.6', '<')) {
            /**
             * Disabled due to bug in MySQL extension
             * @link https://bugs.php.net/bug.php?id=70384
             */
            $this->markTestSkipped('Yii 2 does not support Json for MySQL and PHP < 5.6 due to bug in MySQL extension.');
        }

        $table = (new Generator([
            'db' => Yii::$app->db,
            'tableName' => 'test_json',
        ]))->table;

        $this->assertArrayHasKey('col_json', $table->columns);
        $this->assertInstanceOf(TableColumnJson::className(), $table->columns['col_json']);
        $this->assertEquals('col_json', $table->columns['col_json']->name);
        $this->assertEquals(Schema::TYPE_JSON, $table->columns['col_json']->type);
        $this->assertEquals(null, $table->columns['col_json']->length);
    }
}
