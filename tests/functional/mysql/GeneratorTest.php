<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use Throwable;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception;

class GeneratorTest extends \bizley\tests\functional\GeneratorTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

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
        $this->createTables(
            [
                'non_standard_columns' => [
                    'col_tiny_int' => $this->tinyInteger(),
                    'col_date_time' => $this->dateTime(),
                    'col_float' => $this->float(),
                    'col_timestamp' => $this->timestamp(),
                    'col_json' => $this->json(),
                ]
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
                \'col_timestamp\' => $this->timestamp()->notNull()->defaultExpression(\'CURRENT_TIMESTAMP\'),
                \'col_json\' => $this->json(),
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
        $this->createTables(
            [
                'non_gs_columns' => [
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
            ]
        );

        $this->controller->generalSchema = false;
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_gs_columns']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%non_gs_columns}}\',
            [
                \'id\' => $this->integer(11)->notNull()->append(\'AUTO_INCREMENT PRIMARY KEY\'),
                \'col_big_int\' => $this->bigInteger(20),
                \'col_int\' => $this->integer(11),
                \'col_small_int\' => $this->smallInteger(6),
                \'col_tiny_int\' => $this->tinyInteger(3),
                \'col_bin\' => $this->binary(),
                \'col_bool\' => $this->tinyInteger(1),
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
                \'col_timestamp\' => $this->timestamp()->notNull()->defaultExpression(\'CURRENT_TIMESTAMP\'),
                \'col_json\' => $this->json(),
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
    public function shouldGenerateGeneralSchemaTableButKeepNonDefaultSize(): void
    {
        $this->createTables(
            [
                'non_default_size' => [
                    'id' => $this->primaryKey(10),
                    'col_big_int' => $this->bigInteger(16),
                    'col_int' => $this->integer(10),
                    'col_small_int' => $this->smallInteger(5),
                    'col_tiny_int' => $this->tinyInteger(2),
                    'col_char' => $this->char(2),
                    'col_decimal' => $this->decimal(8, 3),
                    'col_string' => $this->string(45),
                ]
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_default_size']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%non_default_size}}\',
            [
                \'id\' => $this->primaryKey(10),
                \'col_big_int\' => $this->bigInteger(16),
                \'col_int\' => $this->integer(10),
                \'col_small_int\' => $this->smallInteger(5),
                \'col_tiny_int\' => $this->tinyInteger(2),
                \'col_char\' => $this->char(2),
                \'col_decimal\' => $this->decimal(8, 3),
                \'col_string\' => $this->string(45),
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
        $this->createTables(['big_primary_key' => ['id' => $this->bigPrimaryKey()]]);

        $this->controller->generalSchema = false;
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['big_primary_key']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%big_primary_key}}\',
            [
                \'id\' => $this->bigInteger(20)->notNull()->append(\'AUTO_INCREMENT PRIMARY KEY\'),
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
    public function shouldGenerateGeneralSchemaTableWithNonGeneralBigPrimaryKey(): void
    {
        $this->createTables(
            [
                'big_primary_key' => [
                    'id' => $this->bigInteger(20)->notNull()->append('AUTO_INCREMENT PRIMARY KEY')
                ]
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['big_primary_key']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%big_primary_key}}\',
            [
                \'id\' => $this->bigPrimaryKey(),
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
        $this->createTables(
            [
                'non_gs_columns' => [
                    'id' => $this->integer(11)->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
                    'col_big_int' => $this->bigInteger(20),
                    'col_int' => $this->integer(11),
                    'col_small_int' => $this->smallInteger(6),
                    'col_tiny_int' => $this->tinyInteger(3),
                    'col_bool' => $this->tinyInteger(1),
                    'col_char' => $this->char(1),
                    'col_decimal' => $this->decimal(10, 0),
                    'col_money' => $this->decimal(19, 4),
                    'col_string' => $this->string(255),
                ]
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_gs_columns']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%non_gs_columns}}\',
            [
                \'id\' => $this->primaryKey(),
                \'col_big_int\' => $this->bigInteger(),
                \'col_int\' => $this->integer(),
                \'col_small_int\' => $this->smallInteger(),
                \'col_tiny_int\' => $this->tinyInteger(),
                \'col_bool\' => $this->boolean(),
                \'col_char\' => $this->char(),
                \'col_decimal\' => $this->decimal(),
                \'col_money\' => $this->money(),
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
        $this->createTables(
            [
                'appendixes' => [
                    'col1' => $this->integer()->defaultValue(2),
                    'col2' => $this->integer()->unsigned(),
                    'col3' => $this->string()->defaultValue('abc'),
                    'col4' => $this->integer()->comment('comment'),
                    'col5' => $this->integer()->notNull(),
                    'col6' => $this->integer()->null(),
                    'col7' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                ]
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['appendixes']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%appendixes}}\',
            [
                \'col1\' => $this->integer()->defaultValue(\'2\'),
                \'col2\' => $this->integer()->unsigned(),
                \'col3\' => $this->string()->defaultValue(\'abc\'),
                \'col4\' => $this->integer()->comment(\'comment\'),
                \'col5\' => $this->integer()->notNull(),
                \'col6\' => $this->integer(),
                \'col7\' => $this->timestamp()->notNull()->defaultExpression(\'CURRENT_TIMESTAMP\'),
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
    public function shouldGenerateGeneralSchemaTableWithCompositePrimaryKey(): void
    {
        $this->createTables(
            [
                'composite_primary_key' => [
                    'col1' => $this->integer(),
                    'col2' => $this->integer(),
                ]
            ]
        );
        $this->getDb()->createCommand()->addPrimaryKey('PK', 'composite_primary_key', ['col1', 'col2'])->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['composite_primary_key']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%composite_primary_key}}\',
            [
                \'col1\' => $this->integer()->notNull(),
                \'col2\' => $this->integer()->notNull(),
            ],
            $tableOptions
        );

        $this->addPrimaryKey(\'PRIMARYKEY\', \'{{%composite_primary_key}}\', [\'col1\', \'col2\']);
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
    public function shouldGenerateGeneralSchemaTableWithUniqueColumn(): void
    {
        $this->createTables(['unique' => ['col' => $this->integer()->unique()]]);

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['unique']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%unique}}\',
            [
                \'col\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->createIndex(\'col\', \'{{%unique}}\', [\'col\'], true);
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
    public function shouldGenerateGeneralSchemaTableWithForeignKey(): void
    {
        $this->createTables(
            [
                'table1' => ['id' => $this->primaryKey(11)],
                'table2' => ['col' => $this->integer(11)]
            ]
        );
        $this->getDb()->createCommand()->addForeignKey('fk-table2', 'table2', ['col'], 'table1', ['id'])->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['table2']));
        $this->assertStringContainsString(
            '
        $this->createTable(
            \'{{%table2}}\',
            [
                \'col\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->addForeignKey(
            \'fk-table2\',
            \'{{%table2}}\',
            [\'col\'],
            \'{{%table1}}\',
            [\'id\'],
            \'RESTRICT\',
            \'RESTRICT\'
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
    public function shouldGenerateGeneralSchemaCrossReferredTables(): void
    {
        try {
            $this->getDb()->createCommand()->dropForeignKey('fk-table1', 'table1')->execute();
            $this->getDb()->createCommand()->dropForeignKey('fk-table2', 'table2')->execute();
        } catch (Throwable $exception) {
        }

        $this->createTables(
            [
                'table1' => [
                    'id1' => $this->primaryKey(),
                    'fk1' => $this->integer(),
                ],
                'table2' => [
                    'id2' => $this->primaryKey(),
                    'fk2' => $this->integer(),
                ]
            ]
        );
        $this->getDb()->createCommand()->addForeignKey(
            'fk-table1',
            'table1',
            ['fk1'],
            'table2',
            ['id2'],
            'CASCADE',
            'CASCADE'
        )->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-table2',
            'table2',
            ['fk2'],
            'table1',
            ['id1'],
            'CASCADE',
            'CASCADE'
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['table1,table2']));
        $this->assertStringContainsString(
            'public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%table2}}\',
            [
                \'id2\' => $this->primaryKey(),
                \'fk2\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->createIndex(\'fk-table2\', \'{{%table2}}\', [\'fk2\']);
    }

    public function down()
    {
        $this->dropTable(\'{{%table2}}\');
    }',
            MigrationControllerStub::$content
        );
        $this->assertStringContainsString(
            'public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%table1}}\',
            [
                \'id1\' => $this->primaryKey(),
                \'fk1\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->addForeignKey(
            \'fk-table1\',
            \'{{%table1}}\',
            [\'fk1\'],
            \'{{%table2}}\',
            [\'id2\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }

    public function down()
    {
        $this->dropTable(\'{{%table1}}\');
    }',
            MigrationControllerStub::$content
        );
        $this->assertStringContainsString(
            'public function up()
    {
        $this->addForeignKey(
            \'fk-table2\',
            \'{{%table2}}\',
            [\'fk2\'],
            \'{{%table1}}\',
            [\'id1\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }

    public function down()
    {
        $this->dropForeignKey(\'fk-table2\', \'{{%table2}}\');
    }',
            MigrationControllerStub::$content
        );
    }
}
