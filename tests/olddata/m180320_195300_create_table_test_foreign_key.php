<?php

namespace bizley\migration\tests\data;

use yii\db\Migration;

class m180320_195300_create_table_test_foreign_key extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_foreign_key}}', [
            'id' => $this->primaryKey(),
            'basic_id' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk-test_foreign_key-test_basic', '{{%test_foreign_key}}', 'basic_id', '{{%test_basic}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('{{%test_foreign_key}}');
    }
}
