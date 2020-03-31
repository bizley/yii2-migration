<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\tests\unit\stubs\ArrangerStub;
use bizley\tests\unit\stubs\GeneratorStub;
use bizley\tests\unit\stubs\MigrationControllerStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Action;
use yii\base\Controller;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\NotSupportedException;
use yii\base\View;
use yii\console\ExitCode;
use yii\db\Command;
use yii\db\Connection;
use yii\db\ForeignKeyConstraint;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\Schema;
use yii\db\sqlite\Schema as SqliteSchema;
use yii\db\TableSchema;

use function ucfirst;

class MigrationControllerTest extends TestCase
{
    /** @var MigrationControllerStub */
    private $controller;

    /** @var MockObject|Connection */
    private $db;

    /** @var MockObject|View */
    private $view;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Connection::class);
        $this->controller = new MigrationControllerStub('id', $this->createMock(Module::class));
        $this->controller->db = $this->db;
        $this->view = $this->createMock(View::class);
        $this->view->method('renderFile')->willReturn('rendered_file');
        $this->controller->view = $this->view;
        Yii::setAlias('@bizley/tests', 'tests');
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$confirmControl = true;
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
            Controller::EVENT_BEFORE_ACTION,
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
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}(''));
        $this->assertSame(
            ' > No matching tables in database.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenThereIsNoProvidedTable(string $actionId): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('not-test'));
        $this->assertSame(
            ' > No matching tables in database.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenProvidedTableIsExcluded(string $actionId): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test'));
        $this->assertSame(
            ' > No matching tables in database.
 > 1 table excluded by the config.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancels(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2'));
        $this->assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancelsAndOneIsExcluded(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2', 'test3']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2,test3'));
        $this->assertSame(
            ' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - test2
   - test3
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancelsAndOneIsHistory(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2', 'test3']);
        $schema->method('getRawTableName')->willReturn('test');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2,test3'));
        $this->assertSame(
            ' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - test2
   - test3
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     * @param string $actionId
     */
    public function shouldNotProceedWhenUserCancelsAndAsteriskProvided(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('*'));
        $this->assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStopCreateWhenTableIsMissing(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('*'));
        $this->assertSame(
            '
 > Generating migration for creating table \'test\' ...ERROR!
 > Table \'test\' does not exists!
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldCreateOneMigration(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        $this->assertStringContainsString(
            ' > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_create_table_test.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldCreateManyMigrations(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        $this->assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_01_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_02_create_table_test2.php\'

 Generated 2 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldCreateManyMigrationsWithPostponedForeignKeys(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturnOnConsecutiveCalls(
            [],
            [
                new ForeignKeyConstraint(
                    [
                        'name' => 'fk',
                        'columnNames' => ['col1'],
                        'foreignTableName' => 'test',
                        'foreignColumnNames' => ['col2'],
                        'onDelete' => null,
                        'onUpdate' => null,
                    ]
                )
            ]
        );
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->arrangerClass = ArrangerStub::class;
        $this->assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        $this->assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_01_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_02_create_table_test2.php\'

 > Generating migration for creating foreign keys ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_03_create_foreign_keys.php\'

 Generated 3 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldCreateOneMigrationAndFixHistory(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);
        $command = $this->createMock(Command::class);
        $command->method('createTable')->willReturn($command);
        $command->method('insert')->willReturn($command);
        $this->db->method('createCommand')->willReturn($command);

        $this->controller->fixHistory = true;
        $this->assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        $this->assertStringContainsString(
            '
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_create_table_test.php\'
 > Fixing migration history ...DONE!

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStopCreateManyMigrationsWithPostponedForeignKeysWhenThereIsException(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturnOnConsecutiveCalls(
            [],
            [
                new ForeignKeyConstraint(
                    [
                        'name' => 'fk',
                        'columnNames' => ['col1'],
                        'foreignTableName' => 'test',
                        'foreignColumnNames' => ['col2'],
                        'onDelete' => null,
                        'onUpdate' => null,
                    ]
                )
            ]
        );
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->generatorClass = GeneratorStub::class;
        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('*'));
        $this->assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_01_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_02_create_table_test2.php\'

 > Generating migration for creating foreign keys ...ERROR!
 > Stub exception
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function shouldStopCreateWhenThereArePostponedForeignKeysAndSchemaIsSqlite(): void
    {
        $schema = $this->createMock(SqliteSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->arrangerClass = ArrangerStub::class;
        $this->assertSame(ExitCode::DATAERR, $this->controller->actionCreate('*'));
        $this->assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
ERROR!
 > Generating migrations for provided tables in batch is not possible because \'ADD FOREIGN KEY\' is not supported by SQLite!
',
            MigrationControllerStub::$stdout
        );
    }
}
