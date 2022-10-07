<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

/**
 * @group mysql
 * @group sqlextract
 */
final class SqlExtractTest extends \bizley\tests\functional\SqlExtractTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    /**
     * @test
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldExtractSqlFromMigrationUp(): void
    {
        self::assertEquals(
            ExitCode::OK,
            $this->controller->runAction('sql', ['m20200406_124200_create_table_updater_base'])
        );

        self::assertStringContainsString('Yii 2 Migration Generator Tool v', MigrationControllerStub::$stdout);
        self::assertStringContainsString(
            ' > SQL statements of the m20200406_124200_create_table_updater_base file (UP method):',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            'CREATE TABLE `updater_base` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`col` int(11),
	`col2` varchar(255),
	`col3` timestamp(0) NULL DEFAULT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
CREATE TABLE `updater_base_fk_target` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
CREATE TABLE `updater_base_fk` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`col` int(11),
	`col2` int(11) UNIQUE,
	`updater_base_id` int(11)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE `updater_base_fk` ADD INDEX `idx-col` (`col`);
ALTER TABLE `updater_base_fk` ADD CONSTRAINT `fk-plus` FOREIGN KEY (`updater_base_id`) REFERENCES `updater_base_fk_target` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
CREATE TABLE `updater_base_fk_with_idx` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`updater_base_id` int(11),
	`amount` decimal(10,2) NOT NULL,
	`dec_no_scale` decimal(20,0) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB;
ALTER TABLE `updater_base_fk_with_idx` ADD INDEX `idx-updater_base_id` (`updater_base_id`);
ALTER TABLE `updater_base_fk_with_idx` ADD CONSTRAINT `fk-existing-ids` FOREIGN KEY (`updater_base_id`) REFERENCES `updater_base_fk_target` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;',
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
