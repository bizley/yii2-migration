<?php

declare(strict_types=1);

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
    public function testAddPrimaryKey(): void
    {
        $this->dbUp('test_index_single');

        Yii::$app->db->createCommand()->addPrimaryKey('PRIMARYKEY', 'test_index_single', 'col')->execute();

        $updater = $this->getUpdater('test_index_single');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertNotEmpty($updater->plan->addPrimaryKey);
        $this->assertEmpty($updater->plan->addPrimaryKey->name);
        $this->assertEquals(['col'], $updater->plan->addPrimaryKey->columns);
    }
}
