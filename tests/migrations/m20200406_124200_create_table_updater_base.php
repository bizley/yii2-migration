<?php

use yii\db\Migration;

class m20200406_124200_create_table_updater_base extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ],
            $tableOptions
        );

        $this->createTable('updater_base_fk_target', ['id' => $this->primaryKey()], $tableOptions);

        $this->createTable(
            'updater_base_fk',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->integer()->unique(),
                'updater_base_id' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('idx-col', 'updater_base_fk', 'col');
        $this->addForeignKey(
            $this->db->driverName !== 'sqlite' ? 'fk-plus' : '',
            'updater_base_fk',
            'updater_base_id',
            'updater_base_fk_target',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable(
            'updater_base_fk_with_idx',
            [
                'id' => $this->primaryKey(),
                'updater_base_id' => $this->integer(),
            ],
            $tableOptions
        );
        $this->createIndex('idx-updater_base_id', 'updater_base_fk_with_idx', 'updater_base_id');
        $this->addForeignKey(
            'fk-existing-ids',
            'updater_base_fk_with_idx',
            'updater_base_id',
            'updater_base_fk_target',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function down()
    {
        $this->dropTable('updater_base_fk_with_idx');
        $this->dropTable('updater_base_fk');
        $this->dropTable('updater_base_fk_target');
        $this->dropTable('updater_base');
    }
}
