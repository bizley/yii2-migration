<?php declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180317_110000_create_table_test_tinyint extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_tinyint}}', [
            'col_tiny_int' => $this->tinyInteger(),
        ], $tableOptions);
    }

    public function down(): void
    {
        $this->dropTable('{{%test_tinyint}}');
    }
}
