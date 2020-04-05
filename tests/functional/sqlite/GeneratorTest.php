<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception;

class GeneratorTest extends \bizley\tests\functional\GeneratorTest
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateGeneralSchemaTableWithNonStandardColumns(): void
    {
        $this->createTable(
            'non_standard_columns',
            [
                'col_tiny_int' => $this->tinyInteger(),
                'col_date_time' => $this->dateTime(),
                'col_float' => $this->float(),
                'col_timestamp' => $this->timestamp(),
                'col_json' => $this->json(),
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_standard_columns']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%non_standard_columns}}\',
            [
                \'col_tiny_int\' => $this->tinyInteger(),
                \'col_date_time\' => $this->dateTime(),
                \'col_float\' => $this->float(),
                \'col_timestamp\' => $this->timestamp(),
                \'col_json\' => $this->string(),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateNonGeneralSchemaTable(): void
    {
        $this->createTable(
            'non_gs_columns',
            [
                'id' => $this->primaryKey(),
                'col_big_int' => $this->bigInteger(),
                'col_int' => $this->integer(),
                'col_small_int' => $this->smallInteger(),
                'col_tiny_int' => $this->tinyInteger(),
                'col_bin' => $this->binary(),
                'col_bool' => $this->boolean(),
                'col_char' => $this->char(),
                'col_date' => $this->date(),
                'col_date_time' => $this->dateTime(),
                'col_decimal' => $this->decimal(),
                'col_double' => $this->double(),
                'col_float' => $this->float(),
                'col_money' => $this->money(),
                'col_string' => $this->string(),
                'col_text' => $this->text(),
                'col_time' => $this->time(),
                'col_timestamp' => $this->timestamp(),
                'col_json' => $this->json(),
            ]
        );

        $this->controller->generalSchema = false;
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_gs_columns']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%non_gs_columns}}\',
            [
                \'id\' => $this->integer()->notNull()->append(\'PRIMARY KEY AUTOINCREMENT\'),
                \'col_big_int\' => $this->bigInteger(),
                \'col_int\' => $this->integer(),
                \'col_small_int\' => $this->smallInteger(),
                \'col_tiny_int\' => $this->tinyInteger(),
                \'col_bin\' => $this->binary(),
                \'col_bool\' => $this->boolean(),
                \'col_char\' => $this->char(1),
                \'col_date\' => $this->date(),
                \'col_date_time\' => $this->dateTime(),
                \'col_decimal\' => $this->decimal(10, 0),
                \'col_double\' => $this->double(),
                \'col_float\' => $this->float(),
                \'col_money\' => $this->decimal(19, 4),
                \'col_string\' => $this->string(255),
                \'col_text\' => $this->text(),
                \'col_time\' => $this->time(),
                \'col_timestamp\' => $this->timestamp(),
                \'col_json\' => $this->string(),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateGeneralSchemaTableWithBigPrimaryKey(): void
    {
        $this->createTable('big_primary_key', ['id' => $this->bigPrimaryKey()]);

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['big_primary_key']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%big_primary_key}}\',
            [
                \'id\' => $this->primaryKey(),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateNonGeneralSchemaTableWithBigPrimaryKey(): void
    {
        $this->createTable('big_primary_key', ['id' => $this->bigPrimaryKey()]);

        $this->controller->generalSchema = false;
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['big_primary_key']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%big_primary_key}}\',
            [
                \'id\' => $this->integer()->notNull()->append(\'PRIMARY KEY AUTOINCREMENT\'),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateGeneralSchemaTableWithNonGeneralColumnsDefaultValues(): void
    {
        $this->createTable(
            'non_gs_columns',
            [
                'id' => $this->integer()->notNull()->append('PRIMARY KEY AUTOINCREMENT'),
                'col_money' => $this->decimal(19, 4),
                'col_char' => $this->char(1),
                'col_decimal' => $this->decimal(10, 0),
                'col_string' => $this->string(255),
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_gs_columns']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%non_gs_columns}}\',
            [
                \'id\' => $this->primaryKey(),
                \'col_money\' => $this->money(),
                \'col_char\' => $this->char(),
                \'col_decimal\' => $this->decimal(),
                \'col_string\' => $this->string(),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateGeneralSchemaTableWithColumnsWithAppendixes(): void
    {
        $this->createTable(
            'appendixes',
            [
                'col2' => $this->integer()->defaultValue(2),
                'col3' => $this->integer()->unsigned(),
                'col4' => $this->string()->defaultValue('abc'),
                'col7' => $this->integer()->notNull(),
                'col8' => $this->integer()->null(),
                'col9' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['appendixes']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%appendixes}}\',
            [
                \'col2\' => $this->integer()->defaultValue(\'2\'),
                \'col3\' => $this->integer()->unsigned(),
                \'col4\' => $this->string()->defaultValue(\'abc\'),
                \'col7\' => $this->integer()->notNull(),
                \'col8\' => $this->integer()->defaultValue(\'0\'),
                \'col9\' => $this->timestamp()->defaultExpression(\'CURRENT_TIMESTAMP\'),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }
}
