<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\tests\stubs\GeneratorStub;
use bizley\tests\stubs\MigrationControllerStub;
use bizley\tests\stubs\UpdaterStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Module;
use yii\base\View;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\TableSchema;
use yii\helpers\FileHelper;

/**
 * @group controller
 */
final class TimestampMigrationControllerTest extends TestCase
{
    /** @var MigrationControllerStub */
    private $controller;

    /** @var MockObject&Connection */
    private $db;

    /** @var MockObject&View */
    private $view;

    /** @var MockObject&MysqlSchema */
    private $schema;

    protected function setUp(): void
    {
        Yii::$app = new class {
            public $errorHandler;

            public function __construct()
            {
                $this->errorHandler = new \stdClass();
            }

            public function has(): bool
            {
                return false;
            }
        };
        $this->db = $this->createMock(Connection::class);
        $this->controller = new MigrationControllerStub('id', $this->createMock(Module::class));
        $this->controller->db = $this->db;
        $this->schema = $this->createMock(MysqlSchema::class);
        $this->db->method('getSchema')->willReturn($this->schema);
        $this->db->method('getTableSchema')->willReturn($this->createMock(TableSchema::class));
        $this->view = $this->createMock(View::class);
        $this->view->method('renderFile')->willReturn('rendered_file');
        $this->controller->view = $this->view;
        Yii::setAlias('@bizley/tests', __DIR__ . '/../..');
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = true;
        UpdaterStub::$throwForPrepare = false;
        UpdaterStub::$throwForGenerate = false;
        GeneratorStub::$throwForTable = false;
        GeneratorStub::$throwForKeys = false;

        $this->prepareFolder();
    }

    protected function tearDown(): void
    {
        Yii::$app = null;
    }

    private function prepareFolder(): void
    {
        $path = __DIR__ . '/../../runtime/test';
        FileHelper::removeDirectory($path);
        FileHelper::createDirectory($path);
    }

    /** @test */
    public function shouldDetectCollisionOnCreateWithMigrationPath(): void
    {
        $this->controller->migrationPath = [__DIR__ . '/../../runtime/test'];

        $now = \time();
        $count = 0;
        while ($count < 10) {
            \file_put_contents(
                __DIR__ . '/../../runtime/test/' . \sprintf(
                    'm%s_create_table_tab',
                    \gmdate('ymd_His', $now + $count++)
                ),
                ''
            );
        }
        $this->schema->method('getTableNames')->willReturn(['test']);
        $this->schema->method('getRawTableName')->willReturn('mig');
        MigrationControllerStub::$confirmControl = false;

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('test'));
        self::assertSame(
            ' > There are migration files detected that have timestamps colliding with the ones that will be generated. Are you sure you want to proceed?
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /** @test */
    public function shouldDetectCollisionOnUpdateWithMigrationPath(): void
    {
        $this->controller->migrationPath = [__DIR__ . '/../../runtime/test'];

        $now = \time();
        $count = 0;
        while ($count < 10) {
            \file_put_contents(
                __DIR__ . '/../../runtime/test/' . \sprintf(
                    'm%s_create_table_tab',
                    \gmdate('ymd_His', $now + $count++)
                ),
                ''
            );
        }
        $this->schema->method('getTableNames')->willReturn(['test']);
        $this->schema->method('getRawTableName')->willReturn('mig');
        $this->schema->method('getTableForeignKeys')->willReturn([]);
        $this->schema->method('getTableIndexes')->willReturn([]);
        MigrationControllerStub::$confirmControl = false;

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionUpdate('test'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...DONE!
 > There are migration files detected that have timestamps colliding with the ones that will be generated. Are you sure you want to proceed?
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /** @test */
    public function shouldDetectCollisionOnCreateWithMigrationNamespace(): void
    {
        $this->controller->migrationNamespace = ['bizley\\tests\\runtime\\test'];

        $now = \time();
        $count = 0;
        while ($count < 10) {
            \file_put_contents(
                __DIR__ . '/../../runtime/test/' . \sprintf(
                    'M%sCreateTableTab',
                    \gmdate('ymdHis', $now + $count++)
                ),
                ''
            );
        }
        $this->schema->method('getTableNames')->willReturn(['test']);
        $this->schema->method('getRawTableName')->willReturn('mig');
        MigrationControllerStub::$confirmControl = false;

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('test'));
        self::assertSame(
            ' > There are migration files detected that have timestamps colliding with the ones that will be generated. Are you sure you want to proceed?
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /** @test */
    public function shouldDetectCollisionOnUpdateWithMigrationNamespace(): void
    {
        $this->controller->migrationNamespace = ['bizley\\tests\\runtime\\test'];

        $now = \time();
        $count = 0;
        while ($count < 10) {
            \file_put_contents(
                __DIR__ . '/../../runtime/test/' . \sprintf(
                    'M%sCreateTableTab',
                    \gmdate('ymd_His', $now + $count++)
                ),
                ''
            );
        }
        $this->schema->method('getTableNames')->willReturn(['test']);
        $this->schema->method('getRawTableName')->willReturn('mig');
        $this->schema->method('getTableForeignKeys')->willReturn([]);
        $this->schema->method('getTableIndexes')->willReturn([]);
        MigrationControllerStub::$confirmControl = false;

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionUpdate('test'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...DONE!
 > There are migration files detected that have timestamps colliding with the ones that will be generated. Are you sure you want to proceed?
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }
}
