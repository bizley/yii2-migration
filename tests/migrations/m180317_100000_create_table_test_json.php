<?php declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180317_100000_create_table_test_json extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_json}}', [
            'col_json' => $this->json(),
        ], $tableOptions);
    }

    public function down(): void
    {
        $this->dropTable('{{%test_json}}');
    }
}
