<?php

namespace bizley\migration\tests\data;

use yii\db\Migration;

class m180320_193900_create_table_test_basic extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_basic}}', [
            'id' => $this->primaryKey(),
            'col_int' => $this->integer(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_basic}}');
    }
}
