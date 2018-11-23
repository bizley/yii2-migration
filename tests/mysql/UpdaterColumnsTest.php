<?php declare(strict_types=1);

namespace bizley\tests\mysql;

use Yii;

/**
 * @group mysql
 */
class UpdaterColumnsTest extends \bizley\tests\cases\UpdaterColumnsTestCase
{
    public static $schema = 'mysql';
    public static $tableOptions = 'ENGINE=InnoDB';

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     * @throws \yii\base\NotSupportedException
     */
    public function testChangeSizeSpecific(): void
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', $this->integer(9))->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_int', $updater->plan->alterColumn);
        $this->assertEquals(9, $updater->plan->alterColumn['col_int']->length);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     * @throws \yii\base\NotSupportedException
     */
    public function testChangeSizeGeneral(): void
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_int', $this->integer(9))->execute();

        $updater = $this->getUpdater('test_columns');

        $updater->isUpdateRequired();
// Travis frakery
        $this->assertEmpty($updater->plan->addColumn);
        $this->assertEmpty($updater->plan->addForeignKey);
        $this->assertEmpty($updater->plan->addPrimaryKey);
        $this->assertEmpty($updater->plan->alterColumn);
        $this->assertEmpty($updater->plan->createIndex);
        $this->assertEmpty($updater->plan->dropColumn);
        $this->assertEmpty($updater->plan->dropForeignKey);
        $this->assertEmpty($updater->plan->dropIndex);
        $this->assertEmpty($updater->plan->dropPrimaryKey);
    }
}
