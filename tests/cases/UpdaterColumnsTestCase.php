<?php

namespace bizley\tests\cases;

use Yii;
use yii\base\ErrorException;
use yii\base\NotSupportedException;
use yii\db\Exception;
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
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     */
    public function testChangeSizeGeneral()
    {
        $this->dbUp('test_int_general');

        Yii::$app->db->createCommand()->alterColumn('test_int_general', 'col_int', $this->integer(11))->execute();

        $updater = $this->getUpdater('test_int_general');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     * https://github.com/bizley/yii2-migration/issues/30
     * @throws ErrorException
     * @throws NotSupportedException
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
     * @throws ErrorException
     * @throws NotSupportedException
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
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testChangeScaleGeneral()
    {
        $this->dbUp('test_dec_general');

        Yii::$app->db->createCommand()->alterColumn('test_dec_general', 'col_dec', $this->decimal(10, 0))->execute();

        $updater = $this->getUpdater('test_dec_general');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testChangeScaleSpecific()
    {
        $this->dbUp('test_dec_general');

        Yii::$app->db->createCommand()->alterColumn('test_dec_general', 'col_dec', $this->decimal(11, 7))->execute();

        $updater = $this->getUpdater('test_dec_general', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_dec', $updater->plan->alterColumn);
        $this->assertEquals('11, 7', $updater->plan->alterColumn['col_dec']->length);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testChangeColumnType()
    {
        $this->dbUp('test_int_size');

        Yii::$app->db->createCommand()->alterColumn('test_int_size', 'col_int', $this->string(255))->execute();

        $updater = $this->getUpdater('test_int_size', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertCount(1, $updater->plan->alterColumn);
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(Schema::TYPE_STRING, $updater->plan->alterColumn['col_int']->type);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testDropColumn()
    {
        $this->dbUp('test_int_general');

        Yii::$app->db->createCommand()->dropColumn('test_int_general', 'col_int')->execute();

        $updater = $this->getUpdater('test_int_general', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertCount(1, $updater->plan->dropColumn);
        $this->assertEquals(['col_int'], $updater->plan->dropColumn);
        $this->assertEmpty($updater->plan->alterColumn);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws Exception
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function testAddColumn()
    {
        $this->dbUp('test_int_general');

        Yii::$app->db->createCommand()->addColumn('test_int_general', 'col_new', $this->integer())->execute();

        $updater = $this->getUpdater('test_int_general');
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_new', $updater->plan->addColumn);
        $this->assertEquals(Schema::TYPE_INTEGER, $updater->plan->addColumn['col_new']->type);
        $this->assertEquals('col_second', $updater->plan->addColumn['col_new']->after);
        $this->assertEquals(false, $updater->plan->addColumn['col_new']->isFirst);
        $this->assertEmpty($updater->plan->alterColumn);
    }
}
