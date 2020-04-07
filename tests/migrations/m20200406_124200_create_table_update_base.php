<?php

declare(strict_types=1);

namespace bizley\tests\migrations;

use yii\db\Migration;

class m20200406_124200_create_table_update_base extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(
            'update_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()
            ],
            $tableOptions
        );
    }

    public function down()
    {
        $this->dropTable('update_base');
    }
}
