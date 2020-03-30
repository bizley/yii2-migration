<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\tests\unit\stubs\MigrationControllerStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Schema;

class MigrationControllerTest extends TestCase
{
    /** @var MigrationControllerStub */
    private $controller;

    /** @var MockObject|Connection */
    private $db;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->controller = new MigrationControllerStub('id', $this->createMock(Module::class));
        $this->controller->db = $this->db;
        $this->controller->view = $this->createMock(View::class);
        Yii::setAlias('@bizley/tests', 'tests');
    }

    public function providerForOptions(): array
    {
        return [
            'default' => ['default', ['color', 'interactive', 'help', 'db']],
            'create' => [
                'create',
                [
                    'color',
                    'interactive',
                    'help',
                    'db',
                    'fixHistory',
                    'generalSchema',
                    'migrationNamespace',
                    'migrationPath',
                    'migrationTable',
                    'useTablePrefix',
                    'excludeTables'
                ]
            ],
            'update' => [
                'update',
                [
                    'color',
                    'interactive',
                    'help',
                    'db',
                    'fixHistory',
                    'generalSchema',
                    'migrationNamespace',
                    'migrationPath',
                    'migrationTable',
                    'useTablePrefix',
                    'excludeTables',
                    'onlyShow',
                    'skipMigrations'
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForOptions
     * @param string $actionId
     * @param array $expected
     */
    public function shouldReturnProperOptions(string $actionId, array $expected): void
    {
        $this->assertSame($expected, $this->controller->options($actionId));
    }

    /** @test */
    public function shouldReturnProperOptionAliases(): void
    {
        $this->assertSame(
            [
                'h' => 'help',
                'fh' => 'fixHistory',
                'gs' => 'generalSchema',
                'mn' => 'migrationNamespace',
                'mp' => 'migrationPath',
                'mt' => 'migrationTable',
                'os' => 'onlyShow',
                'tp' => 'useTablePrefix',
            ],
            $this->controller->optionAliases()
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldReturnFalseWhenParentBeforeActionReturnsFalse(): void
    {
        $this->controller->on(
            \yii\base\Controller::EVENT_BEFORE_ACTION,
            static function ($event) {
                $event->isValid = false;
            }
        );
        $this->assertFalse($this->controller->beforeAction($this->createMock(Action::class)));
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldReturnTrueBeforeDefaultAction(): void
    {
        MigrationControllerStub::$stdout = '';
        $this->assertTrue($this->controller->beforeAction($this->createMock(Action::class)));
        $this->assertStringContainsString(
            'Yii 2 Migration Generator Tool v',
            MigrationControllerStub::$stdout
        );
    }

    public function providerForActionIds(): array
    {
        return [
            ['create'],
            ['update'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldThrowExceptionWhenNeitherPathOrNamespaceGivenInBeforeNonDefaultAction(string $actionId): void
    {
        $this->expectException(InvalidConfigException::class);
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationNamespace = null;
        $this->controller->migrationPath = null;
        $this->controller->beforeAction($action);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldPrepareSkippedMigrationsInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationPath = 'migrations';
        $this->controller->skipMigrations = ['a\\b\\'];
        $this->assertTrue($this->controller->beforeAction($action));
        $this->assertSame(['a\\b'], $this->controller->skipMigrations);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldPrepareSingleMigrationNamespaceInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationNamespace = 'bizley\\tests';
        $this->assertTrue($this->controller->beforeAction($action));
        $this->assertSame(['bizley\\tests'], $this->controller->migrationNamespace);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function shouldPrepareSingleMigrationPathInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationPath = 'tests';
        $this->assertTrue($this->controller->beforeAction($action));
        $this->assertSame(['tests'], $this->controller->migrationPath);
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReturnListForNoTables(): void
    {
        MigrationControllerStub::$stdout = '';
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $this->assertSame(ExitCode::OK, $this->controller->actionList());
        $this->assertSame(
            ' > Your database does not contain any tables yet.

 > Run
   migration/create <table>
      to generate creating migration for the specific table.
   migration/update <table>
      to generate updating migration for the specific table.

 > <table> can be:
   - * (asterisk) - for all the tables in database (except excluded ones)
   - string with * (one or more) - for all the tables in database matching the pattern (except excluded ones)
   - string without * - for the table of specified name
   - strings separated with comma - for multiple tables of specified names (with optional *)
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws NotSupportedException
     */
    public function shouldReturnSortedListForTables(): void
    {
        MigrationControllerStub::$stdout = '';
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['a', 'b', 't', 'migration_history']);
        $schema->method('getRawTableName')->willReturn('migration_history');
        $this->db->method('getSchema')->willReturn($schema);
        $this->assertSame(ExitCode::OK, $this->controller->actionList());
        $this->assertSame(
            ' > Your database contains 4 tables:
   - a
   - b
   - migration_history (excluded by default unless explicitly requested)
   - t

 > Run
   migration/create <table>
      to generate creating migration for the specific table.
   migration/update <table>
      to generate updating migration for the specific table.

 > <table> can be:
   - * (asterisk) - for all the tables in database (except excluded ones)
   - string with * (one or more) - for all the tables in database matching the pattern (except excluded ones)
   - string without * - for the table of specified name
   - strings separated with comma - for multiple tables of specified names (with optional *)
',
            MigrationControllerStub::$stdout
        );
    }
}
