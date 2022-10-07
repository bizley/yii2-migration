<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

/**
 * @group sqlite
 * @group sqlextract
 * @group sqlextract-sqlite
 */
final class SqlExtractTest extends \bizley\tests\functional\SqlExtractTest
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @test
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldExtractSqlFromMigrationUp(): void
    {
        self::assertEquals(
            ExitCode::OK,
            $this->controller->runAction('sql', ['m20200709_121500_create_table_exp_updater_base'])
        );

        self::assertStringContainsString('Yii 2 Migration Generator Tool v', MigrationControllerStub::$stdout);
        self::assertStringContainsString(
            ' > SQL statements of the m20200709_121500_create_table_exp_updater_base file (UP method):',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            'CREATE TABLE `exp_updater_base` (
	`id` integer PRIMARY KEY AUTOINCREMENT NOT NULL,
	`col1` VARCHAR(255),
	`col2` INTEGER(10),
	`col3` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`col4` FLOAT,
	`col5` DECIMAL(10, 3)
);',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            ' (!) Note that the above statements were not executed.',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldExtractSqlFromMigrationDown(): void
    {
        self::assertEquals(
            ExitCode::OK,
            $this->controller->runAction('sql', ['m20200406_124200_create_table_updater_base', 'down'])
        );

        self::assertStringContainsString('Yii 2 Migration Generator Tool v', MigrationControllerStub::$stdout);
        self::assertStringContainsString(
            ' > SQL statements of the m20200406_124200_create_table_updater_base file (DOWN method):',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            'DROP TABLE `updater_base_fk_with_idx`;
DROP TABLE `updater_base_fk`;
DROP TABLE `updater_base_fk_target`;
DROP TABLE `updater_base`;',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            ' (!) Note that the above statements were not executed.',
            MigrationControllerStub::$stdout
        );
    }
}
