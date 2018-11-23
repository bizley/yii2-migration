<?php declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180322_213900_create_table_test_pk_composite extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $columns = [
            'one' => $this->integer(),
            'two' => $this->integer(),
        ];
        if ($this->db->driverName === 'sqlite') {
            $columns[] = 'PRIMARY KEY(one, two)';
        }

        $this->createTable('{{%test_pk_composite}}', $columns, $tableOptions);

        if ($this->db->driverName !== 'sqlite') {
            $this->addPrimaryKey('PRIMARYKEY', '{{%test_pk_composite}}', ['one', 'two']);
        }
    }

    public function down(): void
    {
        $this->dropTable('{{%test_pk_composite}}');
    }
}
