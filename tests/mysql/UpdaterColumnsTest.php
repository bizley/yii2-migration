<?php

namespace bizley\tests\mysql;

use bizley\tests\cases\UpdaterColumnsTestCase;
use Yii;
use yii\base\ErrorException;
use yii\base\NotSupportedException;
use yii\db\Exception;

/**
 * @group mysql
 */
class UpdaterColumnsTest extends UpdaterColumnsTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testChangeSizeSpecific()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', $this->integer(9))->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(9, $updater->plan->alterColumn['col_int']->length);
    }
}
