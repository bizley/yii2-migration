<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\stubs\MigrationControllerStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

#[Group('pgsql')]
final class UpdaterPkShowTest extends \bizley\tests\functional\UpdaterPkShowTestCase
{
    /** @var string */
    public static $schema = 'pgsql';

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByAddingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            ' > Comparing current table \'no_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)
   - different primary key definition

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
        $this->getDb()->createCommand()->dropPrimaryKey('string_pk-primary-key', 'string_pk')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['string_pk']));
        self::assertStringContainsString(
            ' > Comparing current table \'string_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)
   - different \'col\' column property: append (DB: NULL != MIG: "PRIMARY KEY")
   - different primary key definition

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
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', ['col', 'col2'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            ' > Comparing current table \'no_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)
   - different \'col2\' column property: not null (DB: TRUE != MIG: FALSE)
   - different primary key definition

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }
}
