<?php

declare(strict_types=1);

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180322_215600_create_table_test_index_multi extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_index_multi}}', [
            'one' => $this->integer(),
            'two' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('idx-test_index_multi-cols', '{{%test_index_multi}}', ['one', 'two']);
    }

    public function down(): void
    {
        $this->dropTable('{{%test_index_multi}}');
    }
}
