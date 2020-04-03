<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception;

/*abstract */class GeneratorTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = true;
    }

    /**
     * @test2
     * @throws Exception
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


    }

    /**
     * @test
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldHandleNonExistingTable(): void
    {
        $this->assertEquals(ExitCode::DATAERR, $this->controller->runAction('create', ['non-existing-table']));

        $this->assertStringContainsString(
            '',
            MigrationControllerStub::$stdout
        );
    }
}
