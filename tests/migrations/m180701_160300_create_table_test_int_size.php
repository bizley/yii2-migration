<?php

declare(strict_types=1);

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180701_160300_create_table_test_int_size extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_int_size}}', [
            'col_int' => $this->integer(10),
        ], $tableOptions);
    }

    public function down(): void
    {
        $this->dropTable('{{%test_int_size}}');
    }
}
