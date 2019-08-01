<?php

declare(strict_types=1);

namespace bizley\tests\cases;

use bizley\tests\controllers\MockMigrationController;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\console\Exception;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Exception as DbException;
use yii\di\Instance;

class MigrationControllerTestCase extends DbMigrationsTestCase
{
    protected function tearDown(): void
    {
        $this->dbDown('ALL');

        parent::tearDown();
    }

    /**
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function testCreateNonExisting(): void
    {
        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(ExitCode::DATAERR, $controller->runAction('create', ['non-existing-table']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains("> Generating create migration for table 'non-existing-table' ...ERROR!", $output);
        $this->assertContains("Table 'non-existing-table' does not exist!", $output);
    }

    /**
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function testUpdateNonExisting(): void
    {
        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(ExitCode::DATAERR, $controller->runAction('update', ['non-existing-table']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains("> Generating update migration for table 'non-existing-table' ...ERROR!", $output);
        $this->assertContains("Table 'non-existing-table' does not exist!", $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function testUpdateNoNeeded(): void
    {
        $this->dbUp('test_index_single');

        $controller = new MockMigrationController('migration', Yii::$app);

        $this->assertEquals(ExitCode::OK, $controller->runAction('update', ['test_index_single']));

        $output = $controller->flushStdOutBuffer();

        $this->assertContains(
            "> Generating update migration for table 'test_index_single' ...UPDATE NOT REQUIRED.",
            $output
        );
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

        $this->assertContains("> Generating create migration for table 'test_pk' ...ERROR!", $output);
        $this->assertContains("Migration file for table 'test_pk' can not be generated!", $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws DbException
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

        $this->assertContains("> Generating update migration for table 'test_pk' ...ERROR!", $output);
        $this->assertContains("Migration file for table 'test_pk' can not be generated!", $output);
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

        $this->assertContains("> Generating create migration for table 'test_pk' ...DONE!", $output);
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

        $this->assertContains("> Generating update migration for table 'test_pk' ...DONE!", $output);
        $this->assertContains('Generated 1 file(s).', $output);
    }

    /**
     * @throws InvalidConfigException
     */
    public function testRemoveExcluded()
    {
        $controller = new MockMigrationController('migration', Yii::$app);
        $controller->excludeTables = ['exclude'];
        $controller->db = Instance::ensure($controller->db, Connection::class);

        $this->assertEquals(['all-good', 'another'], $controller->removeExcludedTables(['all-good', 'another']));
        $this->assertEquals(['another'], $controller->removeExcludedTables(['exclude', 'another']));
        $this->assertEquals(['another'], $controller->removeExcludedTables(['migration', 'another']));
    }

    public function testCreateInProperOrder(): void
    {
        $this->dbUp('test_pk');
        $this->dbUp('test_fk');

        $mock = $this
            ->getMockBuilder(MockMigrationController::class)
            ->setConstructorArgs(['migration', Yii::$app])
            ->setMethods(['generateFile'])
            ->getMock();
        $mock->method('generateFile')->willReturn(true);

        $this->assertEquals(ExitCode::OK, $mock->runAction('create', ['test_fk,test_pk']));

        $output = str_replace(["\r", "\n"], '', $mock->flushStdOutBuffer());

        $file = Yii::getAlias(
            reset($mock->migrationPath)
            . DIRECTORY_SEPARATOR
            . 'm' . gmdate('ymd_His')
            . '_01_create_table_test_pk.php'
        );

        $this->assertContains(
            "> Generating create migration for table 'test_pk' ...DONE!"
            . " > Saved as '{$file}'"
            . " > Generating create migration for table 'test_fk' ...DONE!",
            $output
        );
        $this->assertContains(' Generated 2 file(s).', $output);
    }

    public function testCreatePostponedFK(): void
    {
        $this->dbUp('test_a_dep_b');
        $this->dbUp('test_b_dep_a');
        $this->dbUp('test_x_dependencies');

        $mock = $this
            ->getMockBuilder(MockMigrationController::class)
            ->setConstructorArgs(['migration', Yii::$app])
            ->setMethods(['generateFile'])
            ->getMock();
        $mock->method('generateFile')->willReturn(true);

        $this->assertEquals(ExitCode::OK, $mock->runAction('create', ['test_a_dep_b,test_b_dep_a']));

        $output = $mock->flushStdOutBuffer();

        $this->assertContains("> Generating create migration for table 'test_a_dep_b' ...DONE!", $output);
        $this->assertContains("> Generating create migration for table 'test_b_dep_a' ...DONE!", $output);
        $this->assertContains('> Generating create migration for foreign keys ...DONE!', $output);
        $this->assertContains(' Generated 3 file(s).', $output);
    }

    /**
     * Keep last
     * @throws Exception
     * @throws InvalidRouteException
     */
    public function testInvalidConfig(): void
    {
        $controller = new MockMigrationController('migration', Yii::$app);
        $controller->migrationPath = null;

        $this->expectExceptionMessage(
            'You must provide either "migrationPath" or "migrationNamespace" for this action.'
        );
        $controller->runAction('create', ['table']);
    }
}
