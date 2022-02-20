<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use PDO;
use Throwable;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception;

use function preg_match_all;
use function version_compare;

/** @group mysql */
final class GeneratorTest extends \bizley\tests\functional\GeneratorTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    private $v8;

    public function isV8(): bool
    {
        if ($this->v8 === null) {
            $this->v8 = version_compare(
                $this->getDb()->getSlavePdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
                '8.0.17',
                '>='
            );
        }

        return $this->v8;
    }

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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_standard_columns']));
        self::assertStringContainsString(
            $this->isV8()
                ? '
        $this->createTable(
            \'{{%non_standard_columns}}\',
            [
                \'col_tiny_int\' => $this->tinyInteger(),
                \'col_date_time\' => $this->dateTime(),
                \'col_float\' => $this->float(),
                \'col_timestamp\' => $this->timestamp(),
                \'col_json\' => $this->json(),
            ],
            $tableOptions
        );
' : '
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
        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_gs_columns']));
        self::assertStringContainsString(
            $this->isV8()
                ? '
        $this->createTable(
            \'{{%non_gs_columns}}\',
            [
                \'id\' => $this->integer()->notNull()->append(\'AUTO_INCREMENT PRIMARY KEY\'),
                \'col_big_int\' => $this->bigInteger(),
                \'col_int\' => $this->integer(),
                \'col_small_int\' => $this->smallInteger(),
                \'col_tiny_int\' => $this->tinyInteger(),
                \'col_bin\' => $this->binary(),
                \'col_bool\' => $this->tinyInteger(1),
                \'col_char\' => $this->char(1),
                \'col_date\' => $this->date(),
                \'col_date_time\' => $this->dateTime(0),
                \'col_decimal\' => $this->decimal(10, 0),
                \'col_double\' => $this->double(),
                \'col_float\' => $this->float(),
                \'col_money\' => $this->decimal(19, 4),
                \'col_string\' => $this->string(255),
                \'col_text\' => $this->text(),
                \'col_time\' => $this->time(0),
                \'col_timestamp\' => $this->timestamp(0),
                \'col_json\' => $this->json(),
            ],
            $tableOptions
        );
' : '
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
                \'col_date_time\' => $this->dateTime(0),
                \'col_decimal\' => $this->decimal(10, 0),
                \'col_double\' => $this->double(),
                \'col_float\' => $this->float(),
                \'col_money\' => $this->decimal(19, 4),
                \'col_string\' => $this->string(255),
                \'col_text\' => $this->text(),
                \'col_time\' => $this->time(0),
                \'col_timestamp\' => $this->timestamp(0)->notNull()->defaultExpression(\'CURRENT_TIMESTAMP\'),
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_default_size']));
        self::assertStringContainsString(
            $this->isV8()
                ? '
        $this->createTable(
            \'{{%non_default_size}}\',
            [
                \'id\' => $this->primaryKey(),
                \'col_big_int\' => $this->bigInteger(),
                \'col_int\' => $this->integer(),
                \'col_small_int\' => $this->smallInteger(),
                \'col_tiny_int\' => $this->tinyInteger(),
                \'col_char\' => $this->char(2),
                \'col_decimal\' => $this->decimal(8, 3),
                \'col_string\' => $this->string(45),
            ],
            $tableOptions
        );
' : '
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
        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['big_primary_key']));
        self::assertStringContainsString(
            $this->isV8()
                ? '
        $this->createTable(
            \'{{%big_primary_key}}\',
            [
                \'id\' => $this->bigInteger()->notNull()->append(\'AUTO_INCREMENT PRIMARY KEY\'),
            ],
            $tableOptions
        );
' : '
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['big_primary_key']));
        self::assertStringContainsString(
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non_gs_columns']));
        self::assertStringContainsString(
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['appendixes']));
        self::assertStringContainsString(
            $this->isV8()
                ? '
        $this->createTable(
            \'{{%appendixes}}\',
            [
                \'col1\' => $this->integer()->defaultValue(\'2\'),
                \'col2\' => $this->integer()->unsigned(),
                \'col3\' => $this->string()->defaultValue(\'abc\'),
                \'col4\' => $this->integer()->comment(\'comment\'),
                \'col5\' => $this->integer()->notNull(),
                \'col6\' => $this->integer(),
                \'col7\' => $this->timestamp()->defaultExpression(\'CURRENT_TIMESTAMP\'),
            ],
            $tableOptions
        );
' : '
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['composite_primary_key']));
        self::assertStringContainsString(
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['unique']));
        self::assertStringContainsString(
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
        try {
            $this->getDb()->createCommand()->dropForeignKey('fk-table12', 'table12')->execute();
        } catch (Throwable $exception) {
        }
        $this->createTables(
            [
                'table11' => ['id' => $this->primaryKey(11)],
                'table12' => ['col' => $this->integer(11)]
            ]
        );
        $this->getDb()->createCommand()->addForeignKey('fk-table12', 'table12', ['col'], 'table11', ['id'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['table12']));
        self::assertStringContainsString(
            $this->isV8()
                ? '
        $this->createTable(
            \'{{%table12}}\',
            [
                \'col\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->addForeignKey(
            \'fk-table12\',
            \'{{%table12}}\',
            [\'col\'],
            \'{{%table11}}\',
            [\'id\'],
            \'NO ACTION\',
            \'NO ACTION\'
        );
' : '
        $this->createTable(
            \'{{%table12}}\',
            [
                \'col\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->addForeignKey(
            \'fk-table12\',
            \'{{%table12}}\',
            [\'col\'],
            \'{{%table11}}\',
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
            $this->getDb()->createCommand()->dropForeignKey('fk-table21', 'table21')->execute();
            $this->getDb()->createCommand()->dropForeignKey('fk-table22', 'table22')->execute();
        } catch (Throwable $exception) {
        }

        $this->createTables(
            [
                'table21' => [
                    'id1' => $this->primaryKey(),
                    'fk1' => $this->integer(),
                ],
                'table22' => [
                    'id2' => $this->primaryKey(),
                    'fk2' => $this->integer(),
                ]
            ]
        );
        $this->getDb()->createCommand()->addForeignKey(
            'fk-table21',
            'table21',
            ['fk1'],
            'table22',
            ['id2'],
            'CASCADE',
            'CASCADE'
        )->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-table22',
            'table22',
            ['fk2'],
            'table21',
            ['id1'],
            'CASCADE',
            'CASCADE'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['table21,table22']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%table22}}\',
            [
                \'id2\' => $this->primaryKey(),
                \'fk2\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->createIndex(\'fk-table22\', \'{{%table22}}\', [\'fk2\']);
    }

    public function safeDown()
    {
        $this->dropTable(\'{{%table22}}\');
    }',
            MigrationControllerStub::$content
        );
        self::assertStringContainsString(
            'public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%table21}}\',
            [
                \'id1\' => $this->primaryKey(),
                \'fk1\' => $this->integer(),
            ],
            $tableOptions
        );

        $this->addForeignKey(
            \'fk-table21\',
            \'{{%table21}}\',
            [\'fk1\'],
            \'{{%table22}}\',
            [\'id2\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }

    public function safeDown()
    {
        $this->dropTable(\'{{%table21}}\');
    }',
            MigrationControllerStub::$content
        );
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addForeignKey(
            \'fk-table22\',
            \'{{%table22}}\',
            [\'fk2\'],
            \'{{%table21}}\',
            [\'id1\'],
            \'CASCADE\',
            \'CASCADE\'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey(\'fk-table22\', \'{{%table22}}\');
    }',
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
    public function shouldGenerateGeneralSchemaTablesInProperOrder(): void
    {
        try {
            $this->getDb()->createCommand()->dropForeignKey('fk-table31', 'table31')->execute();
            $this->getDb()->createCommand()->dropForeignKey('fk-table32', 'table32')->execute();
        } catch (Throwable $exception) {
        }

        $this->createTables(
            [
                'table31' => [
                    'id1' => $this->primaryKey(),
                    'fk1' => $this->integer(),
                ],
                'table32' => [
                    'id2' => $this->primaryKey(),
                    'fk2' => $this->integer(),
                ],
                'table33' => ['id3' => $this->primaryKey()],
            ]
        );
        $this->getDb()->createCommand()->addForeignKey(
            'fk-table31',
            'table31',
            ['fk1'],
            'table32',
            ['id2'],
            'CASCADE',
            'CASCADE'
        )->execute();
        $this->getDb()->createCommand()->addForeignKey(
            'fk-table32',
            'table32',
            ['fk2'],
            'table33',
            ['id3'],
            'CASCADE',
            'CASCADE'
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['table31,table32,table33']));
        preg_match_all('/create_table_table(\d{2})/', MigrationControllerStub::$content, $matches);
        self::assertSame(['33', '32', '31'], $matches[1]);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateGeneralSchemaTableWithUnsignedPrimaryKey(): void
    {
        $this->createTables(
            [
                'unsigned_pk' => [
                    'id' => $this->primaryKey()->unsigned(),
                ]
            ]
        );

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['unsigned_pk']));
        self::assertStringContainsString(
            $this->isV8()
                ? 'public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%unsigned_pk}}\',
            [
                \'id\' => $this->primaryKey()->unsigned(),
            ],
            $tableOptions
        );
    }

    public function safeDown()
    {
        $this->dropTable(\'{{%unsigned_pk}}\');
    }
}
' : 'public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%unsigned_pk}}\',
            [
                \'id\' => $this->primaryKey(10)->unsigned(),
            ],
            $tableOptions
        );
    }

    public function safeDown()
    {
        $this->dropTable(\'{{%unsigned_pk}}\');
    }
}
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
    public function shouldGenerateGeneralSchemaTableWithUnsignedBigPrimaryKey(): void
    {
        $this->createTables(
            [
                'unsigned_big_pk' => [
                    'id' => $this->bigPrimaryKey()->unsigned(),
                ]
            ]
        );

        self::assertEquals(ExitCode::OK, $this->controller->runAction('create', ['unsigned_big_pk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%unsigned_big_pk}}\',
            [
                \'id\' => $this->bigPrimaryKey()->unsigned(),
            ],
            $tableOptions
        );
    }

    public function safeDown()
    {
        $this->dropTable(\'{{%unsigned_big_pk}}\');
    }
}
',
            MigrationControllerStub::$content
        );
    }
}
