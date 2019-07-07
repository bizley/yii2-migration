<?php

declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m190706_143800_create_test_x_depencies extends Migration
{
    public function up(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_a_dep_b}}', [
            'id' => $this->primaryKey(),
            'b_id' => $this->integer(),
        ], $tableOptions);

        $this->createTable('{{%test_b_dep_a}}', [
            'id' => $this->primaryKey(),
            'a_id' => $this->integer(),
        ], $tableOptions);

        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey(
                'fk-test_a_dep_b-b_id',
                '{{%test_a_dep_b}}',
                'b_id',
                '{{%test_b_dep_a}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk-test_b_dep_a-a_id',
                '{{%test_b_dep_a}}',
                'a_id',
                '{{%test_a_dep_b}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function down(): void
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-test_a_dep_b-b_id', '{{%test_a_dep_b}}');
            $this->dropForeignKey('fk-test_b_dep_a-a_id', '{{%test_b_dep_a}}');
        }
        $this->dropTable('{{%test_b_dep_a}}');
        $this->dropTable('{{%test_a_dep_b}}');
    }
}
