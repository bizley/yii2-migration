<?php

declare(strict_types=1);

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
    public function testChangeSizeSpecific(): void
    {
        $this->dbUp('test_int_general');

        Yii::$app->db->createCommand()->alterColumn('test_int_general', 'col_int', $this->integer(9))->execute();

        $updater = $this->getUpdater('test_int_general', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertCount(1, $updater->plan->alterColumn);
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(9, $updater->plan->alterColumn['col_int']->length);
    }
}
