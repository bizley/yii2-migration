<?php

declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180324_153800_create_table_test_addons extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $structure = [
            'col_unique' => $this->integer()->unique(),
            'col_unsigned' => $this->integer()->unsigned(),
            'col_not_null' => $this->integer()->notNull(),
            'col_default_value' => $this->integer()->defaultValue(1),
        ];
        if ($this->db->driverName !== 'sqlite') {
            $structure['col_comment'] = $this->string()->comment('comment');
            $structure['col_default_expression'] = $this->timestamp()->defaultExpression('now()');
        } else {
            $structure['col_default_expression'] = $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP');
        }

        $this->createTable('{{%test_addons}}', $structure, $tableOptions);
    }

    public function down(): void
    {
        $this->dropTable('{{%test_addons}}');
    }
}
