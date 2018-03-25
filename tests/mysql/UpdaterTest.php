<?php

namespace bizley\migration\tests\mysql;

use bizley\migration\Updater;
use Yii;
use yii\db\Migration;

class UpdaterTest extends MysqlDbTestCase
{
    protected function getUpdater($tableName)
    {
        return new Updater([
            'db' => Yii::$app->db,
            'tableName' => $tableName,
        ]);
    }

    public function testChangeSize()
    {
        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', (new Migration())->integer(9))->execute();
        //Yii::$app = null;
        //static::mockApplication();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
    }
}
