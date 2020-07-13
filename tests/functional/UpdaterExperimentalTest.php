<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception as DbException;

abstract class UpdaterExperimentalTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    /**
     * @throws NotSupportedException
     * @throws DbException
     */
    protected function setUp(): void
    {
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        $this->controller->migrationPath = '@bizley/tests/migrations';
        $this->controller->experimental = true;
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;

        $this->addExperimentalBase();
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldNotUpdateTableWhenItsNotChanged(): void
    {
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['exp_updater_base']));
        self::assertSame('', MigrationControllerStub::$content);
    }
}
