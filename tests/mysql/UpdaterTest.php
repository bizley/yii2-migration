<?php

namespace bizley\tests\mysql;

/**
 * @group mysql
 */
class UpdaterTest extends \bizley\tests\cases\UpdaterTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testAddPrimaryKey()
    {
        $this->dbUp('test_index_single');

        \Yii::$app->db->createCommand()->addPrimaryKey('PRIMARYKEY', 'test_index_single', 'col')->execute();

        $updater = $this->getUpdater('test_index_single');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addPrimaryKey);
        $this->assertEmpty($updater->plan->addPrimaryKey->name);
        $this->assertEquals(['col'], $updater->plan->addPrimaryKey->columns);
    }
}
