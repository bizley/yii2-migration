<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\migrations\m20200406_124200_create_table_update_base;
use bizley\tests\stubs\MigrationControllerStub;
use Yii;
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
     * @throws \yii\base\Exception
     */
    public function shouldUpdateTableByAddingColumn(): void
    {
        $this->addUpdateBases([new m20200406_124200_create_table_update_base()]);
        $this->getDb()->createCommand()->addColumn('update_base', 'added', $this->integer())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['update_base']));
        $this->assertSame(
            ' > Comparing current table \'update_base\' with its migrations ...DONE!

 > Generating migration for creating table \'update_base\' ...DONE!
 > Saved as \'/home/bizley/git/yii2-migration/tests/migrations/m200406_105627_01_create_table_update_base.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            'Yii 2 Migration Generator Tool v4.0.0

 > Comparing current table \'update_base\' with its migrations ...DONE!

 > Generating migration for creating table \'update_base\' ...DONE!
 > Saved as \'/home/bizley/git/yii2-migration/tests/migrations/m200406_105627_01_create_table_update_base.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }
}
