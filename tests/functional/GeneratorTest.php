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
        MigrationControllerStub::$content = '';
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

        $this->assertStringContainsString(' > No matching tables in database.', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidRouteException
     * @throws NotSupportedException
     * @throws \yii\base\Exception
     */
    public function shouldGenerateGeneralSchemaTableWithCommonColumns(): void
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
                'col_decimal' => $this->decimal(),
                'col_double' => $this->double(),
                'col_money' => $this->money(),
                'col_string' => $this->string(),
                'col_text' => $this->text(),
                'col_time' => $this->time(),
            ]
        );

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('create', ['gs_columns']));
        $this->assertStringContainsString(
            '> Generating migration for creating table \'gs_columns\' ...DONE!
 > Saved as \'',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_create_table_gs_columns.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
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
                \'id\' => $this->primaryKey(),
                \'col_big_int\' => $this->bigInteger(),
                \'col_int\' => $this->integer(),
                \'col_small_int\' => $this->smallInteger(),
                \'col_bin\' => $this->binary(),
                \'col_bool\' => $this->boolean(),
                \'col_char\' => $this->char(),
                \'col_date\' => $this->date(),
                \'col_decimal\' => $this->decimal(),
                \'col_double\' => $this->double(),
                \'col_money\' => $this->money(),
                \'col_string\' => $this->string(),
                \'col_text\' => $this->text(),
                \'col_time\' => $this->time(),
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
                \'id\' => $this->bigPrimaryKey(),
            ],
            $tableOptions
        );
',
            MigrationControllerStub::$content
        );
    }
}
