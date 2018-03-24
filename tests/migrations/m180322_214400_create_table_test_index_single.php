<?php

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180322_214400_create_table_test_index_single extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_index_single}}', [
            'col' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('idx-test_index_single-col', '{{%test_index_single}}', 'col');
    }

    public function down()
    {
        $this->dropTable('{{%test_index_single}}');
    }
}
