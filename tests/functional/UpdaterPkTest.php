<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\NotSupportedException;
use yii\db\Exception as DbException;

abstract class UpdaterPkTest extends DbLoaderTestCase
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
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;

        $this->addPkBase();
    }
}
