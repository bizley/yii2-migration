<?php

namespace bizley\tests\cases;

use Yii;
use yii\db\Schema;

class UpdaterColumnsTestCase extends DbMigrationsTestCase
{
    protected function tearDown()
    {
        $this->dbDown('ALL');

        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    public function testChangeSizeGeneral()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', $this->integer(9))->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     * https://github.com/bizley/yii2-migration/issues/30
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testNoChangeSizeSpecific()
    {
        $this->dbUp('test_int_size');

        $updater = $this->getUpdater('test_int_size', false);
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * https://github.com/bizley/yii2-migration/issues/30
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testNoChangePKSpecific()
    {
        $this->dbUp('test_char_pk');

        $updater = $this->getUpdater('test_char_pk', false);
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testChangeScaleGeneral()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_decimal', $this->decimal(11, 7))->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testChangeScaleSpecific()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_decimal', $this->decimal(11, 7))->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_decimal', $updater->plan->alterColumn);
        $this->assertEquals('11, 7', $updater->plan->alterColumn['col_decimal']->length);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testChangeColumnType()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', $this->string(255))->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(Schema::TYPE_STRING, $updater->plan->alterColumn['col_int']->type);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
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
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testAddColumn()
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->addColumn('test_columns', 'col_new', $this->integer())->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_new', $updater->plan->addColumn);
        $this->assertEquals(Schema::TYPE_INTEGER, $updater->plan->addColumn['col_new']->type);
    }
}
