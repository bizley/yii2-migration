<?php

use yii\db\Migration;

class m20200406_124201_create_table_updater_base_plus extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            'updater_base_plus',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->integer()->unique(),
                'updater_base_id' => $this->integer(),
            ],
            $tableOptions
        );

        $this->createIndex('idx-col', 'updater_base_plus', 'col');
        $this->addForeignKey(
            'fk-plus',
            'updater_base_plus',
            'updater_base_id',
            'updater_base',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropTable('updater_base_plus');
    }
}
