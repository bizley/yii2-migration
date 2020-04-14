<?php

use yii\db\Migration;

class m20200414_130200_create_table_pk_base extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('no_pk', ['col' => $this->integer()], $tableOptions);

        $this->createTable('string_pk', ['col' => $this->string()], $tableOptions);
        $this->addPrimaryKey('string_pk-primary-key', 'string_pk', 'col');
    }

    public function down()
    {
        $this->dropTable('string_pk');
        $this->dropTable('no_pk');
    }
}
