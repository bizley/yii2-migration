<?php

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180324_153800_create_table_test_addons extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_addons}}', [
            'col_unique' => $this->integer()->unique(),
            'col_unsigned' => $this->integer()->unsigned(),
            'col_not_null' => $this->integer()->notNull(),
            'col_comment' => $this->string()->comment('comment'),
            'col_default_value' => $this->integer()->defaultValue(1),
            'col_default_expression' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%test_addons}}');
    }
}
