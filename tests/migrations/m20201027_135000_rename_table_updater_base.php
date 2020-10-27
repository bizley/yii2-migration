<?php

use yii\db\Migration;

class m20201027_135000_rename_table_updater_base extends Migration
{
    public function up()
    {
        $this->renameTable('updater_base', 'renamed_base');
    }

    public function down()
    {
        $this->renameTable('renamed_base', 'updater_base');
    }
}
