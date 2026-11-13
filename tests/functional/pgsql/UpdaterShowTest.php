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
final class UpdaterShowTest extends \bizley\tests\functional\UpdaterShowTestCase
{
    /** @var string */
    public static $schema = 'pgsql';

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldShowUpdateTableByDroppingColumn(): void
    {
        $this->getDb()->createCommand()->dropColumn('updater_base', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - excessive column \'col\'

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
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->string())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: type (DB: "string" != MIG: "integer")
   - different \'col\' column property: length (DB: "255" != MIG: NULL)

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
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col2', $this->string(45))->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col2\' column property: length (DB: "45" != MIG: "255")

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
        $this->getDb()->createCommand()->alterColumn(
            'updater_base',
            'col',
            $this->integer()->defaultValue(4)
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: default (DB: "4" != MIG: NULL)

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
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->notNull())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)

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
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->unique())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'updater_base_col_key\'

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
    public function shouldShowUpdateTableByDroppingForeignKey(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - excessive foreign key \'fk-plus\'

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
    public function shouldUpdateTableByAlteringForeignKeyColumns(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-plus',
            'updater_base_fk',
            'col',
            'updater_base_fk_target',
            'id',
            'CASCADE',
            'CASCADE'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - different foreign key \'fk-plus\' columns (DB: ["col"] != MIG: ["updater_base_id"])

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
    public function shouldShowUpdateTableByAlteringForeignKeyConstraints(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-plus',
            'updater_base_fk',
            'updater_base_id',
            'updater_base_fk_target',
            'id',
            'RESTRICT',
            'RESTRICT'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - different foreign key \'fk-plus\' ON UPDATE constraint (DB: "RESTRICT" != MIG: "CASCADE")
   - different foreign key \'fk-plus\' ON DELETE constraint (DB: "RESTRICT" != MIG: "CASCADE")

 No files generated.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }
}
