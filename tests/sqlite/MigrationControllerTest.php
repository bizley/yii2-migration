<?php declare(strict_types=1);

namespace bizley\tests\sqlite;

use bizley\tests\controllers\MockMigrationController;
use Yii;
use yii\console\ExitCode;

/**
 * @group sqlite
 */
class MigrationControllerTest extends \bizley\tests\cases\MigrationControllerTestCase
{
    public static $schema = 'sqlite';

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     * @throws \yii\db\Exception
     */
    public function testUpdateWarning(): void
    {
        $this->dbUp('test_int_size');

        /*
         * In order to alter column in SQLite you must create new table like the old one but with altered column,
         * copy the data from old one to new one, remove old one and rename new one to old one.
         */
        Yii::$app->db->createCommand()->createTable(
            'test_replica',
            [
                'col_int' => $this->string(),
            ]
        )->execute();

        Yii::$app->db->createCommand()->dropTable('test_int_size')->execute();

        Yii::$app->db->createCommand()->renameTable('test_replica', 'test_int_size')->execute();

        $controller = new MockMigrationController('migration', \Yii::$app);

        $this->assertEquals(ExitCode::OK, $controller->runAction('update', ['test_int_size']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_int_size\' ...WARNING!', $output);
        $this->assertContains('> Updating table \'test_int_size\' requires manual migration!', $output);
        $this->assertContains('> ALTER COLUMN is not supported by SQLite.', $output);
        $this->assertContains('No files generated.', $output);
    }
}
