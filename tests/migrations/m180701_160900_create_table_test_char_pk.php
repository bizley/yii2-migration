<?php

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180701_160900_create_table_test_char_pk extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_char_pk}}', [
            'id' => $this->char(128)->notNull()->append('PRIMARY KEY'),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_char_pk}}');
    }
}
