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
    public function testUpdateFileFail(): void
    {
        $this->dbUp('test_pk');
        Yii::$app->db->createCommand()->addColumn('test_pk', 'col_new', $this->integer())->execute();

        $controller = new MockMigrationController('migration', \Yii::$app);

        $this->assertEquals(ExitCode::OK, $controller->runAction('update', ['test_pk']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_pk\' ...WARNING!', $output);
        $this->assertContains('Updating table \'test_pk\' requires manual migration!', $output);
        $this->assertContains('DROP PRIMARY KEY is not supported by SQLite.', $output);
    }
}
