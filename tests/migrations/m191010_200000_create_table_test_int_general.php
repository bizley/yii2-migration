<?php

declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m191010_200000_create_table_test_int_general extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_int_general}}', [
            'col_int' => $this->integer(),
            'col_second' => $this->integer(),
        ], $tableOptions);
    }

    public function down(): void
    {
        $this->dropTable('{{%test_int_general}}');
    }
}
