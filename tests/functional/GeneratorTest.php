<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

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


}
