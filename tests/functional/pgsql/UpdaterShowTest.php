<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

/** @group pgsql */
final class UpdaterShowTest extends \bizley\tests\functional\UpdaterShowTest
{
    /** @var string */
    public static $schema = 'pgsql';

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByDroppingColumn(): void
    {
        $this->getDb()->createCommand()->dropColumn('updater_base', 'col')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - excessive column \'col\'

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
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->string())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: type (DB: "string" != MIG: "integer")
   - different \'col\' column property: length (DB: "255" != MIG: NULL)

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
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col2', $this->string(45))->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col2\' column property: length (DB: "45" != MIG: "255")

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
        $this->getDb()->createCommand()->alterColumn(
            'updater_base',
            'col',
            $this->integer()->defaultValue(4)
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: default (DB: "4" != MIG: NULL)

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
    public function shouldShowUpdateTableByAlteringColumnWithNotNull(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->notNull())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: FALSE)

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
    public function shouldShowUpdateTableByAlteringColumnWithUnique(): void
    {
        $this->getDb()->createCommand()->alterColumn('updater_base', 'col', $this->integer()->unique())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base\' with its migrations ...Showing differences:
   - missing index \'updater_base_col_key\'

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
    public function shouldShowUpdateTableByDroppingForeignKey(): void
    {
        $this->getDb()->createCommand()->dropForeignKey('fk-plus', 'updater_base_fk')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - excessive foreign key \'fk-plus\'

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

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - different foreign key \'fk-plus\' columns (DB: ["col"] != MIG: ["updater_base_id"])

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

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        $this->assertStringContainsString(
            ' > Comparing current table \'updater_base_fk\' with its migrations ...Showing differences:
   - different foreign key \'fk-plus\' ON UPDATE constraint (DB: "RESTRICT" != MIG: "CASCADE")
   - different foreign key \'fk-plus\' ON DELETE constraint (DB: "RESTRICT" != MIG: "CASCADE")

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
