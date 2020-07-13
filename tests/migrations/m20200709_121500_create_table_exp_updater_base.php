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

        $cols = [
            'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'col1' => 'VARCHAR(255) COMMENT \'test\'',
            'col2' => 'INTEGER(10) UNSIGNED',
            'col3' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'col4' => 'FLOAT',
            'col5' => 'DECIMAL(10, 3)',
            'col6' => 'ENUM(\'one\', \'two\')'
        ];
        if ($driverName === 'pgsql') {
            $cols['id'] = 'serial NOT NULL PRIMARY KEY';
            $cols['col1'] = 'VARCHAR(255)';
            $cols['col2'] = 'INTEGER';
            $cols['col3'] = 'timestamp NOT NULL DEFAULT now()';
            unset($cols['col4'], $cols['col6']);
        }
        if ($driverName === 'sqlite') {
            $cols['id'] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
            $cols['col1'] = 'VARCHAR(255)';
            $cols['col2'] = 'INTEGER(10)';
            unset($cols['col6']);
        }

        $this->createTable('exp_updater_base', $cols, $tableOptions);
    }

    public function down()
    {
        $this->dropTable('exp_updater_base');
    }
}
