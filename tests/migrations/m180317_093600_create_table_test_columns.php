<?php

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180317_093600_create_table_test_columns extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_columns}}', [
            'id' => $this->primaryKey(),
            'col_big_int' => $this->bigInteger(),
            'col_int' => $this->integer(),
            'col_small_int' => $this->smallInteger(),
            'col_bin' => $this->binary(),
            'col_bool' => $this->boolean(),
            'col_char' => $this->char(),
            'col_date' => $this->date(),
            'col_date_time' => $this->dateTime(),
            'col_decimal' => $this->decimal(),
            'col_double' => $this->double(),
            'col_float' => $this->float(),
            'col_money' => $this->money(),
            'col_string' => $this->string(),
            'col_text' => $this->text(),
            'col_time' => $this->time(),
            'col_timestamp' => $this->timestamp(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_columns}}');
    }
}
