<?php

namespace bizley\migration\tests\data;

use yii\db\Migration;

class m180322_213900_create_table_test_pk_composite extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_pk_composite}}', [
            'one' => $this->integer(),
            'two' => $this->integer(),
        ], $tableOptions);

        $this->addPrimaryKey('PRIMARYKEY', '{{%test_pk_composite}}', ['one', 'two']);
    }

    public function down()
    {
        $this->dropTable('{{%test_pk_composite}}');
    }
}
