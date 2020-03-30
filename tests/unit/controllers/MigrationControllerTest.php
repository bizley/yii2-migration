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
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\Schema;

use function ucfirst;

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

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenThereAreNoTables(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}(''));
        $this->assertSame(' > No matching tables in database.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenThereIsNoProvidedTable(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('not-test'));
        $this->assertSame(' > No matching tables in database.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenProvidedTableIsExcluded(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test'));
        $this->assertSame(' > No matching tables in database.
 > 1 table excluded by the config.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancels(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2'));
        $this->assertSame(' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 Operation cancelled by user.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancelsAndOneIsExcluded(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2', 'test3']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2,test3'));
        $this->assertSame(' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - test2
   - test3
 Operation cancelled by user.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancelsAndOneIsHistory(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2', 'test3']);
        $schema->method('getRawTableName')->willReturn('test');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2,test3'));
        $this->assertSame(' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - test2
   - test3
 Operation cancelled by user.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancelsAndAsteriskProvided(string $actionId): void
    {
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('*'));
        $this->assertSame(' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 Operation cancelled by user.
', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStopCreateWhenTableIsMissing(): void
    {
        MigrationControllerStub::$stdout = '';
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('*'));
        $this->assertSame(' > Generating migration for creating table \'test\' ...ERROR!
 > Table \'test\' does not exists!

', MigrationControllerStub::$stdout);
    }
}
