<?php

namespace bizley\tests\migrations;

use yii\db\Migration;

class m180328_205700_add_column_two_to_table_test_multiple extends Migration
{
    public function up()
    {
        if ($this->db->driverName === 'sqlite') {
            return;
        }
        $this->addColumn('{{%test_multiple}}', 'two', $this->integer());
    }

    public function down()
    {
        if ($this->db->driverName === 'sqlite') {
            return;
        }
        $this->dropColumn('{{%test_multiple}}', 'two');
    }
}
