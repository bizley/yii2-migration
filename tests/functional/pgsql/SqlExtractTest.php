<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

/**
 * @group sqlite
 * @group sqlextract
 */
final class SqlExtractTest extends \bizley\tests\functional\SqlExtractTest
{
    /** @var string */
    public static $schema = 'pgsql';

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
            'CREATE TABLE "updater_base" (
	"id" serial NOT NULL PRIMARY KEY,
	"col" integer,
	"col2" varchar(255),
	"col3" timestamp(0) NULL DEFAULT NULL
);
CREATE TABLE "updater_base_fk_target" (
	"id" serial NOT NULL PRIMARY KEY
);
CREATE TABLE "updater_base_fk" (
	"id" serial NOT NULL PRIMARY KEY,
	"col" integer,
	"col2" integer UNIQUE,
	"updater_base_id" integer
);
CREATE INDEX "idx-col" ON "updater_base_fk" ("col");
ALTER TABLE "updater_base_fk" ADD CONSTRAINT "fk-plus" FOREIGN KEY ("updater_base_id") REFERENCES "updater_base_fk_target" ("id") ON DELETE CASCADE ON UPDATE CASCADE;
CREATE TABLE "updater_base_fk_with_idx" (
	"id" serial NOT NULL PRIMARY KEY,
	"updater_base_id" integer,
	"amount" numeric(10,2) NOT NULL,
	"dec_no_scale" numeric(20,0) NOT NULL
);
CREATE INDEX "idx-updater_base_id" ON "updater_base_fk_with_idx" ("updater_base_id");
ALTER TABLE "updater_base_fk_with_idx" ADD CONSTRAINT "fk-existing-ids" FOREIGN KEY ("updater_base_id") REFERENCES "updater_base_fk_target" ("id") ON DELETE CASCADE ON UPDATE CASCADE;',
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
            'DROP TABLE "updater_base_fk_with_idx";
DROP TABLE "updater_base_fk";
DROP TABLE "updater_base_fk_target";
DROP TABLE "updater_base";',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            ' (!) Note that the above statements were not executed.',
            MigrationControllerStub::$stdout
        );
    }
}
