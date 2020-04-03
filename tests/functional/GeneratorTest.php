<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception;

abstract class GeneratorTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        $this->controller->migrationPath = '@bizley/tests';
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = true;
    }

    /**
     * @test
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldHandleNonExistingTable(): void
    {
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['non-existing-table']));

        $this->assertSame(
            'Yii 2 Migration Generator Tool v4.0.0

 > No matching tables in database.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     */
    public function shouldGenerateGeneralSchemaTable(): void
    {
        $this->createTable(
            'gs_columns',
            [
                'id' => $this->primaryKey(),
                'col_big_int' => $this->bigInteger(),
                'col_int' => $this->integer(),
                'col_small_int' => $this->smallInteger(),
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
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['gs_columns']));
        $this->assertStringContainsString(
            ' > Generating migration for creating table \'gs_columns\' ...DONE!',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_create_table_gs_columns extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }

        $this->createTable(
            \'{{%gs_columns}}\',
            [
                \'id\' => $this->primaryKey(11),
                \'col_big_int\' => $this->bigInteger(20),
                \'col_int\' => $this->integer(11),
                \'col_small_int\' => $this->smallInteger(6),
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
                \'col_timestamp\' => $this->timestamp()->defaultExpression(\'CURRENT_TIMESTAMP\'),
            ],
            $tableOptions
        );
    }

    public function down()
    {
        $this->dropTable(\'{{%gs_columns}}\');
    }
}
',
            MigrationControllerStub::$content
        );
    }
}
