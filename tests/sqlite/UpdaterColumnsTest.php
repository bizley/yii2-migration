<?php declare(strict_types=1);

namespace bizley\tests\sqlite;

use Yii;

/**
 * @group sqlite
 */
class UpdaterColumnsTest extends \bizley\tests\cases\UpdaterColumnsTestCase
{
    public static $schema = 'sqlite';

    /**
     * #runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     */
    public function testChangeSizeGeneral(): void
    {
        $this->dbUp('test_columns');

        // fraking sqlite alter
        Yii::$app->db->createCommand()->renameTable('test_columns', 'test_columns_old')->execute();
        Yii::$app->db->createCommand()->createTable(
            'test_columns',
            [
                'id' => $this->primaryKey(),
                'col_big_int' => $this->bigInteger(),
                'col_int' => $this->integer(9),
                'col_small_int' => $this->smallInteger(),
                'col_bin' => $this->binary(),
                'col_bool' => $this->boolean(),
                'col_char' => $this->char(),
                'col_date' => $this->date(),
                'col_date_time' => $this->dateTime(),
                'col_decimal' => $this->decimal(),
                'col_double' => $this->double(),
                'col_float' => $this->float(),
                'col_money' => $this->money(),
                'col_string' => $this->string(),
                'col_text' => $this->text(),
                'col_time' => $this->time(),
                'col_timestamp' => $this->timestamp(),
            ],
            static::$tableOptions
        )->execute();
        Yii::$app->db->createCommand()->dropTable('test_columns_old')->execute();

        $updater = $this->getUpdater('test_columns');
        $this->assertFalse($updater->isUpdateRequired());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
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
}
