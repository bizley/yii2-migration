<?php

use yii\db\Migration;

class m20200709_121500_create_table_exp_updater_base extends Migration
{
    public function up()
    {
        $driverName = $this->db->driverName;
        $tableOptions = null;
        if ($driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            'exp_updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => 'VARCHAR(255) COMMENT \'test\'',
                'col2' => 'INTEGER(10) UNSIGNED',
                'col3' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            ],
            $tableOptions
        );
    }

    public function down()
    {
        $this->dropTable('exp_updater_base');
    }
}
