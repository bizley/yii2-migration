<?php

namespace bizley\migration\tests\mysql;

use Yii;
use yii\db\Schema;

class UpdaterColumnsTest extends MysqlDbUpdaterTestCase
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
    public function testChangeSizeGeneral()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', 'INT(9)')->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testChangeSizeSpecific()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', 'INT(9)')->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(9, $updater->plan->alterColumn['col_int']->size);
        $this->assertEquals(9, $updater->plan->alterColumn['col_int']->precision);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * https://github.com/bizley/yii2-migration/issues/30
     */
    public function testNoChangeSizeSpecific(): void
    {
        $this->dbUp('test_int_size');

        $updater = $this->getUpdater('test_int_size', false);
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * https://github.com/bizley/yii2-migration/issues/30
     */
    public function testNoChangePKSpecific(): void
    {
        $this->dbUp('test_char_pk');

        $updater = $this->getUpdater('test_char_pk', false);
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testChangeScaleGeneral()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_decimal', 'DECIMAL(11,7)')->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testChangeScaleSpecific()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_decimal', 'DECIMAL(11,7)')->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_decimal', $updater->plan->alterColumn);
        $this->assertEquals(11, $updater->plan->alterColumn['col_decimal']->size);
        $this->assertEquals(11, $updater->plan->alterColumn['col_decimal']->precision);
        $this->assertEquals(7, $updater->plan->alterColumn['col_decimal']->scale);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testChangeColumnType()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', 'VARCHAR(255)')->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(Schema::TYPE_STRING, $updater->plan->alterColumn['col_int']->type);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDropColumn()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->dropColumn('test_columns', 'col_int')->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayNotHasKey('col_int', $updater->plan->alterColumn);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddColumn()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->addColumn('test_columns', 'col_new', 'INT')->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_new', $updater->plan->addColumn);
        $this->assertEquals(Schema::TYPE_INTEGER, $updater->plan->addColumn['col_new']->type);
    }
}
