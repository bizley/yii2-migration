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

        $this->assertEquals([
            'type' => $updater->plan->alterColumn['col_timestamp']->type,
            'isNotNull' => $updater->plan->alterColumn['col_timestamp']->isNotNull,
            'isUnique' => $updater->plan->alterColumn['col_timestamp']->isUnique,
            'isUnsigned' => $updater->plan->alterColumn['col_timestamp']->isUnsigned,
            'default' => $updater->plan->alterColumn['col_timestamp']->default,
            'append' => $updater->plan->alterColumn['col_timestamp']->append,
            'comment' => $updater->plan->alterColumn['col_timestamp']->comment
        ], [
            'type' => $updater->oldTable->columns['col_timestamp']->type,
            'isNotNull' => $updater->oldTable->columns['col_timestamp']->isNotNull,
            'isUnique' => $updater->oldTable->columns['col_timestamp']->isUnique,
            'isUnsigned' => $updater->oldTable->columns['col_timestamp']->isUnsigned,
            'default' => $updater->oldTable->columns['col_timestamp']->default,
            'append' => $updater->oldTable->columns['col_timestamp']->append,
            'comment' => $updater->oldTable->columns['col_timestamp']->comment
        ]);
    }
}
