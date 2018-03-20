<?php

namespace bizley\migration\tests\data;

use yii\db\Migration;

class m180320_194600_create_table_test_indexes extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_indexes}}', [
            'id' => $this->primaryKey(),
            'col1' => $this->integer(),
            'col2' => $this->integer(),
            'col3' => $this->integer(),
            'col4' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('standard', '{{%test_indexes}}', 'col1');
        $this->createIndex('unique', '{{%test_indexes}}', 'col2', true);
        $this->createIndex('combined', '{{%test_indexes}}', ['col3', 'col4']);
    }

    public function down()
    {
        $this->dropTable('{{%test_indexes}}');
    }
}
