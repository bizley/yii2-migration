<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

abstract class UpdaterShowTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        $this->controller->migrationPath = '@bizley/tests/migrations';
        $this->controller->onlyShow = true;
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;
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
    public function shouldShowUpdateTableByAddingIndex(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->createIndex('idx-add', 'updater_base', 'col')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add\'

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
    public function shouldShowUpdateTableByAddingUniqueIndex(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->createIndex('idx-add-unique', 'updater_base', 'col', true)->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add-unique\'

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
    public function shouldShowUpdateTableByAddingMultiIndex(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->createIndex('idx-add-multi', 'updater_base', ['col', 'col2'])->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add-multi\'

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
    public function shouldShowUpdateTableByAddingMultiUniqueIndex(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->createIndex(
            'idx-add-multi-unique',
            'updater_base',
            ['col', 'col2'],
            true
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add-multi-unique\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
