<?php declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180324_105400_create_table_test_fk extends Migration
{
    public function up(): void
    {
        if ($this->db->driverName === 'sqlite') {
            return;
        }
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_fk}}', [
            'pk_id' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk-test_fk-pk_id', '{{%test_fk}}', 'pk_id', '{{%test_pk}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function down(): void
    {
        if ($this->db->driverName === 'sqlite') {
            return;
        }
        $this->dropTable('{{%test_fk}}');
    }
}
