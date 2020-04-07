<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

abstract class UpdaterTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        $this->controller->migrationPath = '@bizley/tests/migrations';
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;
    }

    /**
     * @test
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldHandleNonExistingTable(): void
    {
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['non-existing-table']));

        $this->assertStringContainsString(' > No matching tables in database.', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumn(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > 1 table excluded by the config.

 > Comparing current table \'updater_base\' with its migrations ...DONE!

 > Generating migration for updating table \'updater_base\' ...DONE!
 > Saved as \'',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_update_table_updater_base.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            'public function up()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->after(\'col\'));
    }

    public function down()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingColumn(): void
    {
        $this->addBase();
        $this->controller->onlyShow = true;
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing column \'added\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByDroppingColumn(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropColumn('updater_base', 'col')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'col\');
    }

    public function down()
    {
        $this->addColumn(\'{{%updater_base}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByDroppingColumn(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropColumn('updater_base', 'col')->execute();

        $this->controller->onlyShow = true;
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - excessive column \'col\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
