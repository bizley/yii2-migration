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
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->addColumn('test_columns', 'after_date', $this->integer()->after('col_date'))->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addColumn);
        $this->assertArrayHasKey('after_date', $updater->plan->addColumn);
        $this->assertEquals('col_date', $updater->plan->addColumn['after_date']->after);
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
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->addColumn('test_columns', 'first_col', $this->integer()->first())->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addColumn);
        $this->assertArrayHasKey('first_col', $updater->plan->addColumn);
        $this->assertTrue($updater->plan->addColumn['first_col']->isFirst);
        $this->assertNull($updater->plan->addColumn['first_col']->after);
    }
}
