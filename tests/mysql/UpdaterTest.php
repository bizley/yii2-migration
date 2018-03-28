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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDropPrimaryKey()
    {
        $this->dbUp('test_pk_composite');

        Yii::$app->db->createCommand()->dropPrimaryKey(null, 'test_pk_composite')->execute();

        $updater = $this->getUpdater('test_pk_composite');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->dropPrimaryKey);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDropForeignKey()
    {
        $this->dbUp('test_pk');
        $this->dbUp('test_fk');

        Yii::$app->db->createCommand()->dropForeignKey('fk-test_fk-pk_id', 'test_fk')->execute();

        $updater = $this->getUpdater('test_fk');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertEquals(['fk-test_fk-pk_id'], $updater->plan->dropForeignKey);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddForeignKey()
    {
        $this->dbUp('test_pk');
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->addForeignKey('fk-test_columns-col_int', 'test_columns', 'col_int', 'test_pk', 'id')->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('fk-test_columns-col_int', $updater->plan->addForeignKey);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDropIndex()
    {
        $this->dbUp('test_index_single');

        Yii::$app->db->createCommand()->dropIndex('idx-test_index_single-col', 'test_index_single')->execute();

        $updater = $this->getUpdater('test_index_single');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertEquals(['idx-test_index_single-col'], $updater->plan->dropIndex);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddIndex()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->createIndex('idx-test_columns-col_int', 'test_columns', 'col_int', true)->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('idx-test_columns-col_int', $updater->plan->createIndex);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMultipleMigrations()
    {
        $this->dbUp('test_multiple');

        Yii::$app->db->createCommand()->addColumn('test_multiple', 'three', 'INT(11)')->execute();

        $updater = $this->getUpdater('test_multiple');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('three', $updater->plan->addColumn);
        $this->assertArrayNotHasKey('one', $updater->oldTable->columns);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMultipleMigrationsWithSkip()
    {
        $this->dbUp('test_multiple_skip');

        Yii::$app->db->createCommand()->addColumn('test_multiple', 'three', 'INT(11)')->execute();

        $updater = $this->getUpdater('test_multiple', true, ['bizley\migration\tests\migrations\m180328_205900_drop_column_one_from_table_test_multiple']);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('three', $updater->plan->addColumn);
        $this->assertArrayHasKey('one', $updater->oldTable->columns);
    }
}
