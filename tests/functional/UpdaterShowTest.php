<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception as DbException;

abstract class UpdaterShowTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    /**
     * @throws NotSupportedException
     * @throws DbException
     */
    protected function setUp(): void
    {
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        $this->controller->migrationPath = '@bizley/tests/migrations';
        $this->controller->onlyShow = true;
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;

        $this->addBase();
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingColumn(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing column \'added\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add', 'updater_base', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingUniqueIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add-unique', 'updater_base', 'col', true)->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add-unique\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingMultiIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add-multi', 'updater_base', ['col', 'col2'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add-multi\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingMultiUniqueIndex(): void
    {
        $this->getDb()->createCommand()->createIndex(
            'idx-add-multi-unique',
            'updater_base',
            ['col', 'col2'],
            true
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'idx-add-multi-unique\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByDroppingIndex(): void
    {
        $this->getDb()->createCommand()->dropIndex('idx-col', 'updater_base_fk')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - excessive index \'idx-col\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }
}
