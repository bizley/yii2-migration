<?php declare(strict_types=1);

namespace bizley\tests\cases;

use bizley\tests\controllers\MockMigrationController;
use Yii;
use yii\console\ExitCode;

class MigrationControllerTestCase extends DbMigrationsTestCase
{
    protected function tearDown(): void
    {
        $this->dbDown('ALL');

        parent::tearDown();
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function testCreateNonExisting(): void
    {
        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(ExitCode::DATAERR, $controller->runAction('create', ['non-existing-table']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating create migration for table \'non-existing-table\' ...ERROR!', $output);
        $this->assertContains('Table \'non-existing-table\' does not exist!', $output);
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function testUpdateNonExisting(): void
    {
        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(ExitCode::DATAERR, $controller->runAction('update', ['non-existing-table']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'non-existing-table\' ...ERROR!', $output);
        $this->assertContains('Table \'non-existing-table\' does not exist!', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public function testUpdateNoNeeded(): void
    {
        $this->dbUp('test_index_single');

        $controller = new MockMigrationController('migration', \Yii::$app);

        $this->assertEquals(ExitCode::OK, $controller->runAction('update', ['test_index_single']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_index_single\' ...UPDATE NOT REQUIRED.', $output);
        $this->assertContains('No files generated.', $output);
    }

    public function testCreateFileFail(): void
    {
        $this->dbUp('test_pk');

        $mock = $this
                ->getMockBuilder(MockMigrationController::class)
                ->setConstructorArgs(['migration', Yii::$app])
                ->setMethods(['generateFile'])
                ->getMock();

        $mock->method('generateFile')->willReturn(false);

        $this->assertEquals(ExitCode::SOFTWARE, $mock->runAction('create', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating create migration for table \'test_pk\' ...ERROR!', $output);
        $this->assertContains('Migration file for table \'test_pk\' can not be generated!', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \yii\db\Exception
     */
    public function testUpdateFileFail(): void
    {
        $this->dbUp('test_pk');
        Yii::$app->db->createCommand()->addColumn('test_pk', 'col_new', $this->integer())->execute();

        $mock = $this
                ->getMockBuilder(MockMigrationController::class)
                ->setConstructorArgs(['migration', Yii::$app])
                ->setMethods(['generateFile'])
                ->getMock();

        $mock->method('generateFile')->willReturn(false);

        $this->assertEquals(ExitCode::SOFTWARE, $mock->runAction('update', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_pk\' ...ERROR!', $output);
        $this->assertContains('Migration file for table \'test_pk\' can not be generated!', $output);
    }

    public function testCreateSuccess(): void
    {
        $this->dbUp('test_pk');

        $mock = $this
                ->getMockBuilder(MockMigrationController::class)
                ->setConstructorArgs(['migration', Yii::$app])
                ->setMethods(['generateFile'])
                ->getMock();

        $mock->method('generateFile')->willReturn(true);

        $this->assertEquals(ExitCode::OK, $mock->runAction('create', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating create migration for table \'test_pk\' ...DONE!', $output);
        $this->assertContains('Generated 1 file(s).', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateSuccess(): void
    {
        $this->dbUp('test_pk');
        Yii::$app->db->createCommand()->addColumn('test_pk', 'col_new', $this->integer())->execute();

        $mock = $this
                ->getMockBuilder(MockMigrationController::class)
                ->setConstructorArgs(['migration', Yii::$app])
                ->setMethods(['generateFile'])
                ->getMock();

        $mock->method('generateFile')->willReturn(true);

        $this->assertEquals(ExitCode::OK, $mock->runAction('update', ['test_pk']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains('> Generating update migration for table \'test_pk\' ...DONE!', $output);
        $this->assertContains('Generated 1 file(s).', $output);
    }
}
