<?php declare(strict_types=1);

namespace bizley\tests\pgsql;

use Yii;

/**
 * @group pgsql
 */
class UpdaterColumnsTest extends \bizley\tests\cases\UpdaterColumnsTestCase
{
    public static $schema = 'pgsql';

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     * @throws \yii\base\ErrorException
     */
    public function testChangeSizeSpecific(): void
    {
        $this->dbUp('test_columns');

        Yii::$app->db->createCommand()->alterColumn('test_columns', 'col_char', $this->char(2))->execute();

        $updater = $this->getUpdater('test_columns', false);
        $this->assertTrue($updater->isUpdateRequired());
        $this->assertArrayHasKey('col_char', $updater->plan->alterColumn);
        $this->assertEquals(2, $updater->plan->alterColumn['col_char']->length);
    }


}
