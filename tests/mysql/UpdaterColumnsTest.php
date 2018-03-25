<?php

namespace bizley\migration\tests\mysql;

use Yii;

class UpdaterColumnsTest extends MysqlDbUpdaterTestCase
{
    protected function tearDown()
    {
        $this->dbDown('ALL');
        parent::tearDown();
    }

    public function testChangeSizeGeneral()
    {
        $this->dbUp('test_pk');

        Yii::$app->db->createCommand()->alterColumn('test_pk', 'id', 'int(9)')->execute();

        $updater = $this->getUpdater('test_pk');
        $this->assertFalse($updater->isUpdateRequired());
    }

    public function testChangeSizeSpecific()
    {
        $this->dbUp('test_pk');

        Yii::$app->db->createCommand()->alterColumn('test_pk', 'id', 'int(9)')->execute();

        $updater = $this->getUpdater('test_pk', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('id', $updater->plan->alterColumn);
        $this->assertEquals(9, $updater->plan->alterColumn['id']->size);
        $this->assertEquals(9, $updater->plan->alterColumn['id']->precision);
    }
}
