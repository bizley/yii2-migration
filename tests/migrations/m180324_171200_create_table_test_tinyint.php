<?php

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180324_171200_create_table_test_tinyint extends Migration
{
    public function up()
    {
        if (!method_exists($this, 'tinyInteger')) {
            return false;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_tinyint}}', [
            'col_tiny_int' => $this->tinyInteger(),
        ], $tableOptions);
    }

    public function down()
    {
        if (!method_exists($this, 'tinyInteger')) {
            return false;
        }
        $this->dropTable('{{%test_tinyint}}');
    }
}
