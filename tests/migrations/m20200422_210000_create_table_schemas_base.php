<?php

use yii\db\Migration;

class m20200422_210000_create_table_schemas_base extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            'table1',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ],
            $tableOptions
        );

        $this->createTable(
            'schema1.table1',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ],
            $tableOptions
        );
        $this->createTable(
            'schema2.table1',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(),
            ],
            $tableOptions
        );
    }

    public function down()
    {
        $this->dropTable('schema2.table1');
        $this->dropTable('schema1.table1');
        $this->dropTable('table1');
    }
}
