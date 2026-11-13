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
final class UpdaterPkShowTest extends \bizley\tests\functional\UpdaterPkShowTestCase
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAddingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->dropTable('no_pk')->execute();
        $this->getDb()->createCommand()->createTable(
            'no_pk',
            [
                'col' => $this->primaryKey(),
                'col2' => $this->integer(),
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            ' > Comparing current table \'no_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually
   - different primary key definition
   - (!) ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually

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
    public function shouldShowUpdateTableByDroppingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->dropTable('string_pk')->execute();
        $this->getDb()->createCommand()->createTable('string_pk', ['col' => $this->string()])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['string_pk']));
        self::assertStringContainsString(
            ' > Comparing current table \'string_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: append (DB: NULL != MIG: "PRIMARY KEY")
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually
   - different primary key definition
   - (!) DROP PRIMARY KEY is not supported by SQLite: Migration must be created manually

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
    public function shouldShowUpdateTableByAddingCompositePrimaryKey(): void
    {
        $this->getDb()->createCommand()->dropTable('no_pk')->execute();
        $this->getDb()->createCommand()->createTable(
            'no_pk',
            [
                'col' => $this->integer(),
                'col2' => $this->integer(),
                'PRIMARY KEY(col, col2)'
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            ' > Comparing current table \'no_pk\' with its migrations ...Showing differences:
   - different primary key definition
   - (!) ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }
}
