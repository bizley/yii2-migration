<?php

namespace bizley\migration\tests\data;

use yii\db\Migration;

class m180322_215100_create_table_test_index_unique extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_index_unique}}', [
            'col' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('idx-test_index_unique-col', '{{%test_index_unique}}', 'col', true);
    }

    public function down()
    {
        $this->dropTable('{{%test_index_unique}}');
    }
}
