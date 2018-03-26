<?php

namespace bizley\migration\tests\mysql;

use Yii;

class UpdaterTest extends MysqlDbUpdaterTestCase
{
    protected function tearDown()
    {
        $this->dbDown('ALL');
        parent::tearDown();
    }

    public function testDropPrimaryKey()
    {
        $this->dbUp('test_pk_composite');

        Yii::$app->db->createCommand()->dropPrimaryKey(null, 'test_pk_composite')->execute();

        $updater = $this->getUpdater('test_pk_composite');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->dropPrimaryKey);
    }

    public function testAddPrimaryKey()
    {
        $this->dbUp('test_index_single');

        Yii::$app->db->createCommand()->addPrimaryKey(null, 'test_index_single', 'col')->execute();

        $updater = $this->getUpdater('test_index_single');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addPrimaryKey);
        $this->assertEmpty($updater->plan->addPrimaryKey->name);
        $this->assertEquals(['col'], $updater->plan->addPrimaryKey->columns);
    }

    public function testDropForeignKey()
    {
        $this->dbUp('test_pk');
        $this->dbUp('test_fk');

        Yii::$app->db->createCommand()->dropForeignKey('fk-test_fk-pk_id', 'test_fk')->execute();

        $updater = $this->getUpdater('test_fk');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertEquals(['fk-test_fk-pk_id'], $updater->plan->dropForeignKey);
    }

    public function testAddForeignKey()
    {
        $this->dbUp('test_pk');
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->addForeignKey('fk-test_columns-col_int', 'test_columns', 'col_int', 'test_pk', 'id')->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('fk-test_columns-col_int', $updater->plan->addForeignKey);
    }
}
