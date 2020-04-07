<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

class UpdaterShowTest extends \bizley\tests\functional\UpdaterShowTest
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByDroppingColumn(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col2' => $this->string(),
            ]
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - excessive column \'col\'
   - (!) DROP COLUMN is not supported by SQLite: Migration must be created manually

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
    public function shouldShowUpdateTableByAlteringColumn(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->string(),
                'col2' => $this->string(),
            ]
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: type (DB: "string" != MIG: "integer")
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually
   - different \'col\' column property: length (DB: "255" != MIG: NULL)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

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
    public function shouldShowUpdateTableByAlteringColumnSize(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(45),
            ]
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col2\' column property: length (DB: "45" != MIG: "255")
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

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
    public function shouldShowUpdateTableByAlteringColumnDefault(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()->defaultValue(4),
                'col2' => $this->string(),
            ]
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: default (DB: "4" != MIG: NULL)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
