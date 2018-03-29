<?php

namespace bizley\migration\tests\migrations;

use yii\db\Migration;

class m180317_100000_create_table_test_json extends Migration
{
    public function up()
    {
        if (!method_exists($this, 'json')) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            if (version_compare(PHP_VERSION, '5.6', '<')) {
                return true;
            }
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%test_json}}', [
            'col_json' => $this->json(),
        ], $tableOptions);
    }

    public function down()
    {
        if (!method_exists($this, 'json') || ($this->db->driverName === 'mysql' && version_compare(PHP_VERSION, '5.6', '<'))) {
            return true;
        }
        $this->dropTable('{{%test_json}}');
    }
}
