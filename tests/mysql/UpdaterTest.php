<?php

namespace bizley\tests\mysql;

use bizley\tests\cases\UpdaterTestCase;
use Yii;
use yii\base\ErrorException;
use yii\base\NotSupportedException;
use yii\db\Exception;

/**
 * @group mysql
 */
class UpdaterTest extends UpdaterTestCase
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
    public function testAddPrimaryKey()
    {
        $this->dbUp('test_index_single');

        Yii::$app->db->createCommand()->addPrimaryKey('PRIMARYKEY', 'test_index_single', 'col')->execute();

        $updater = $this->getUpdater('test_index_single');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addPrimaryKey);
        $this->assertEmpty($updater->plan->addPrimaryKey->name);
        $this->assertEquals(['col'], $updater->plan->addPrimaryKey->columns);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testAddColumnAfter()
    {
        $this->dbUp('test_int_general');

        Yii::$app->db->createCommand()->addColumn('test_int_general', 'after_second', $this->integer()->after('col_second'))->execute();

        $updater = $this->getUpdater('test_int_general');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addColumn);
        $this->assertArrayHasKey('after_second', $updater->plan->addColumn);
        $this->assertEquals('col_second', $updater->plan->addColumn['after_second']->after);
        $this->assertEmpty($updater->plan->alterColumn);
    }
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testAddColumnFirst()
    {
        $this->dbUp('test_int_general');

        Yii::$app->db->createCommand()->addColumn('test_int_general', 'first_col', $this->integer()->first())->execute();

        $updater = $this->getUpdater('test_int_general');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addColumn);
        $this->assertArrayHasKey('first_col', $updater->plan->addColumn);
        $this->assertTrue($updater->plan->addColumn['first_col']->isFirst);
        $this->assertNull($updater->plan->addColumn['first_col']->after);
        $this->assertEmpty($updater->plan->alterColumn);
    }
}
