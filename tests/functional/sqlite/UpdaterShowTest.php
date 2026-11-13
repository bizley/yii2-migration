<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

#[Group('sqlite')]
final class UpdaterShowTest extends \bizley\tests\functional\UpdaterShowTestCase
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByDroppingColumn(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - excessive column \'col\'
   - (!) DROP COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAlteringColumn(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->string(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: type (DB: "string" != MIG: "integer")
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually
   - different \'col\' column property: length (DB: "255" != MIG: NULL)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAlteringColumnSize(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->string(45),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col2\' column property: length (DB: "45" != MIG: "255")
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAlteringColumnDefault(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()->defaultValue(4),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: default (DB: "4" != MIG: NULL)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAlteringColumnWithUnsigned(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()->unsigned(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: unsigned (DB: TRUE != MIG: FALSE)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAlteringColumnWithUnique(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()->unique(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'sqlite_autoindex_updater_base_1\'

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAlteringColumnWithNotNull(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()->notNull(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAlteringForeignKey(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base_fk')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base_fk',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->integer()->unique(),
                'updater_base_id' => $this->integer(),
                'FOREIGN KEY(col) REFERENCES updater_base_fk_target(id)'
            ]
        )->execute();
        $this->getDb()->createCommand()->createIndex('idx-col', 'updater_base_fk', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - missing foreign key \'fk-updater_base_fk-col\'
   - (!) ADD FOREIGN KEY is not supported by SQLite: Migration must be created manually
   - excessive foreign key \'fk-updater_base_fk-updater_base_id\'
   - (!) DROP FOREIGN KEY is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }
}
