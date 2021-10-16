<?php

declare(strict_types=1);

namespace bizley\tests\unit\controllers;

use bizley\migration\controllers\MigrationController;
use bizley\migration\table\Blueprint;
use bizley\tests\stubs\ArrangerStub;
use bizley\tests\stubs\GeneratorStub;
use bizley\tests\stubs\MigrationControllerStoringStub;
use bizley\tests\stubs\MigrationControllerStub;
use bizley\tests\stubs\UpdaterStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yii;
use yii\base\Action;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\View;
use yii\console\ExitCode;
use yii\console\Request;
use yii\console\Response;
use yii\db\Command;
use yii\db\Connection;
use yii\db\ForeignKeyConstraint;
use yii\db\mysql\Schema as MysqlSchema;
use yii\db\Schema;
use yii\db\sqlite\Schema as SqliteSchema;
use yii\db\TableSchema;
use yii\helpers\FileHelper;

use function chmod;
use function date;
use function fileperms;
use function glob;
use function is_dir;
use function mktime;
use function preg_match_all;
use function substr;
use function time;
use function ucfirst;
use function unlink;

/**
 * @group controller
 */
final class MigrationControllerTest extends TestCase
{
    /** @var MigrationControllerStub */
    private $controller;

    /** @var MockObject|Connection */
    private $db;

    /** @var MockObject|View */
    private $view;

    protected function setUp(): void
    {
        Yii::$app = new class {
            public $errorHandler;

            public function __construct()
            {
                $this->errorHandler = new stdClass();
            }

            public function has(): bool
            {
                return false;
            }
        };
        $this->db = $this->createMock(Connection::class);
        $this->controller = new MigrationControllerStub('id', $this->createMock(Module::class));
        $this->controller->db = $this->db;
        $this->controller->migrationPath = [__DIR__ . '/../../runtime/test'];
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

        if (!is_dir(__DIR__ . '/../../runtime')) {
            mkdir(__DIR__ . '/../../runtime');
        }
    }

    protected function tearDown(): void
    {
        Yii::$app = null;
    }

    public function providerForOptions(): array
    {
        return [
            'default' => [
                'default',
                [
                    'color',
                    'interactive',
                    'help',
                    'silentExitOnException',
                    'db',
                    'fileMode',
                    'fileOwnership',
                ]
            ],
            'create' => [
                'create',
                [
                    'color',
                    'interactive',
                    'help',
                    'silentExitOnException',
                    'db',
                    'fileMode',
                    'fileOwnership',
                    'fixHistory',
                    'generalSchema',
                    'migrationNamespace',
                    'migrationPath',
                    'migrationTable',
                    'useTablePrefix',
                    'excludeTables',
                    'leeway',
                ]
            ],
            'update' => [
                'update',
                [
                    'color',
                    'interactive',
                    'help',
                    'silentExitOnException',
                    'db',
                    'fileMode',
                    'fileOwnership',
                    'fixHistory',
                    'generalSchema',
                    'migrationNamespace',
                    'migrationPath',
                    'migrationTable',
                    'useTablePrefix',
                    'excludeTables',
                    'leeway',
                    'onlyShow',
                    'skipMigrations',
                    'experimental',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider providerForOptions
     */
    public function shouldReturnProperOptions(string $actionId, array $expected): void
    {
        self::assertSame($expected, $this->controller->options($actionId));
    }

    /**
     * @test
     */
    public function shouldReturnProperOptionAliases(): void
    {
        self::assertSame(
            [
                'h' => 'help',
                'ex' => 'experimental',
                'fh' => 'fixHistory',
                'gs' => 'generalSchema',
                'mn' => 'migrationNamespace',
                'mp' => 'migrationPath',
                'mt' => 'migrationTable',
                'os' => 'onlyShow',
                'tp' => 'useTablePrefix',
                'fm' => 'fileMode',
                'fo' => 'fileOwnership',
                'lw' => 'leeway',
            ],
            $this->controller->optionAliases()
        );
    }

    /**
     * @test
     */
    public function shouldReturnFalseWhenParentBeforeActionReturnsFalse(): void
    {
        $this->controller->on(
            Controller::EVENT_BEFORE_ACTION,
            static function ($event) {
                $event->isValid = false;
            }
        );
        self::assertFalse($this->controller->beforeAction($this->createMock(Action::class)));
    }

    /**
     * @test
     */
    public function shouldReturnTrueBeforeDefaultAction(): void
    {
        self::assertTrue($this->controller->beforeAction($this->createMock(Action::class)));
        self::assertStringContainsString(
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
     */
    public function shouldPrepareSkippedMigrationsInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationPath = 'tests';
        $this->controller->skipMigrations = ['a\\b\\'];
        self::assertTrue($this->controller->beforeAction($action));
        self::assertSame(['a\\b'], $this->controller->skipMigrations);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldPrepareSingleMigrationNamespaceInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationNamespace = 'bizley\\tests';
        self::assertTrue($this->controller->beforeAction($action));
        self::assertSame(['bizley\\tests'], $this->controller->migrationNamespace);
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldPrepareSingleMigrationPathInBeforeNonDefaultAction(string $actionId): void
    {
        $action = $this->createMock(Action::class);
        $action->id = $actionId;
        $this->controller->migrationPath = 'tests';
        self::assertTrue($this->controller->beforeAction($action));
        self::assertSame(['tests'], $this->controller->migrationPath);
    }

    /**
     * @test
     */
    public function shouldReturnListForNoTables(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        self::assertSame(ExitCode::OK, $this->controller->actionList());
        self::assertSame(
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
     */
    public function shouldReturnSortedListForTables(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['a', 'b', 't', 'migration_history']);
        $schema->method('getRawTableName')->willReturn('migration_history');
        $this->db->method('getSchema')->willReturn($schema);
        self::assertSame(ExitCode::OK, $this->controller->actionList());
        self::assertSame(
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
     */
    public function shouldNotProceedWhenThereAreNoTables(string $actionId): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}(''));
        self::assertSame(
            '
 > No matching tables in database.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldNotProceedWhenThereIsNoProvidedTable(string $actionId): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('not-test'));
        self::assertSame(
            '
 > No matching tables in database.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldNotProceedWhenProvidedTableIsExcluded(string $actionId): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test'));
        self::assertSame(
            '
 > No matching tables in database.
 > 1 table excluded by the config.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldNotProceedWhenUserCancels(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2'));
        self::assertSame(
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
     */
    public function shouldNotProceedWhenUserCancelsAndOneIsExcluded(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2', 'test3']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2,test3'));
        self::assertSame(
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
     */
    public function shouldListAllMatchingTablesWhenUserCancelsButProvidesAsteriskVariant1(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['pref_a', 'pref_b', 'ccc']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('pref_*'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - pref_a
   - pref_b
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldListAllMatchingTablesWhenUserCancelsButProvidesAsteriskVariant2(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['a_suf', 'b_suf', 'ccc']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('*_suf'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - a_suf
   - b_suf
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldListAllMatchingTablesWhenUserCancelsButProvidesAsteriskVariant3(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['pref_a_suf', 'pref_b_suf', 'ccc']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('pref_*_suf'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - pref_a_suf
   - pref_b_suf
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldListAllMatchingTablesWhenUserCancelsButProvidesAsteriskVariant4(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['a_tab_a', 'b_tab_b', 'ccc']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('*_tab_*'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - a_tab_a
   - b_tab_b
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldListAllMatchingTablesWhenUserCancelsButProvidesAsteriskVariant5(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['pref_tab', 'tab_suf', 'ccc']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);
        $this->controller->excludeTables = ['test'];

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('pref_*,*_suf'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - pref_tab
   - tab_suf
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @dataProvider providerForActionIds
     */
    public function shouldNotProceedWhenUserCancelsAndOneIsHistory(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2', 'test3']);
        $schema->method('getRawTableName')->willReturn('test');
        $this->db->method('getSchema')->willReturn($schema);

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('test,test2,test3'));
        self::assertSame(
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
     */
    public function shouldNotProceedWhenUserCancelsAndAsteriskProvided(string $actionId): void
    {
        MigrationControllerStub::$confirmControl = false;
        $schema = $this->createMock(Schema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        self::assertSame(ExitCode::OK, $this->controller->{'action' . ucfirst($actionId)}('*'));
        self::assertSame(
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
     */
    public function shouldStopCreateWhenTableIsMissing(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('*'));
        self::assertSame(
            '
 > Generating migration for creating table \'test\' ...ERROR!
 > Table \'test\' does not exists!
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
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

        self::assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        self::assertStringContainsString(
            ' > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
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

        self::assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 Generated 2 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );

        preg_match_all('/m\d{6}_(\d{6})_create_table/m', MigrationControllerStub::$stdout, $matches);
        $time = $matches[1][0];
        self::assertEqualsWithDelta(
            time(),
            mktime((int)substr($time, 0, 2), (int)substr($time, 2, 2), (int)substr($time, -2)),
            2
        );
        self::assertSame(1, $matches[1][1] - $matches[1][0]);
    }

    /**
     * @test
     */
    public function shouldCreateManyMigrationsWithLeeway(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->leeway = 100;
        self::assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 Generated 2 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );

        preg_match_all('/m\d{6}_(\d{6})_create_table/m', MigrationControllerStub::$stdout, $matches);
        self::assertEqualsWithDelta((int)date('His', time() + 100), (int)$matches[1][0], 2);
        self::assertSame(1, $matches[1][1] - $matches[1][0]);
    }

    /**
     * @test
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
        self::assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 > Generating migration for creating foreign keys ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_foreign_keys.php\'

 Generated 3 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );

        preg_match_all('/m\d{6}_(\d{6})_create_/m', MigrationControllerStub::$stdout, $matches);
        self::assertSame(1, $matches[1][2] - $matches[1][1]);
        self::assertSame(1, $matches[1][1] - $matches[1][0]);
    }

    /**
     * @test
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
        self::assertSame(ExitCode::OK, $this->controller->actionCreate('*'));
        self::assertStringContainsString(
            '
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
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

        GeneratorStub::$throwForKeys = true;
        $this->controller->generatorClass = GeneratorStub::class;
        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionCreate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 > Generating migration for creating foreign keys ...ERROR!
 > Stub exception
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldStopCreateWhenThereArePostponedForeignKeysAndSchemaIsSqlite(): void
    {
        $schema = $this->createMock(SqliteSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->arrangerClass = ArrangerStub::class;
        self::assertSame(ExitCode::DATAERR, $this->controller->actionCreate('*'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
ERROR!
 > Generating migrations for provided tables in batch is not possible because \'ADD FOREIGN KEY\' is not supported by SQLite!
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldStopUpdateWhenTableIsMissing(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->migrationPath = ['test'];
        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionUpdate('*'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...ERROR!
 > Table \'test\' does not exists!
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldCreateOneMigrationWhenNoPreviousDataForUpdate(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->migrationPath = ['test'];
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            '
 > Comparing current table \'test\' with its migrations ...DONE!

 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldCreateOneMigrationAndFixHistoryWhenNoPreviousDataForUpdate(): void
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
        $this->controller->migrationPath = ['test'];
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            '
 > Comparing current table \'test\' with its migrations ...DONE!

 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
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
     */
    public function shouldCreateManyMigrationsWhenNoPreviousDataForUpdate(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->migrationPath = ['test'];
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Comparing current table \'test\' with its migrations ...DONE!

 > Comparing current table \'test2\' with its migrations ...DONE!

 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 Generated 2 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldCreateManyMigrationsWithPostponedForeignKeysWhenNoPreviousDataForUpdate(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturnOnConsecutiveCalls(
            [],
            [],
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

        $this->controller->migrationPath = ['test'];
        $this->controller->arrangerClass = ArrangerStub::class;
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Comparing current table \'test\' with its migrations ...DONE!

 > Comparing current table \'test2\' with its migrations ...DONE!

 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 > Generating migration for creating foreign keys ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_foreign_keys.php\'

 Generated 3 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldReturnUpToDate(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->migrationPath = ['test'];
        $this->controller->updaterClass = UpdaterStub::class;
        UpdaterStub::$blueprint = new Blueprint();
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...TABLE IS UP-TO-DATE.

 No files generated.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldReturnDifferencesForUpdate(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->onlyShow = true;
        $this->controller->migrationPath = ['test'];
        $this->controller->updaterClass = UpdaterStub::class;
        UpdaterStub::$blueprint = new Blueprint();
        UpdaterStub::$blueprint->addDescription('Stub description');
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...Showing differences:
   - Stub description

 No files generated.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldReturnStartFromScratchForUpdate(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->onlyShow = true;
        $this->controller->migrationPath = ['test'];
        $this->controller->updaterClass = UpdaterStub::class;
        UpdaterStub::$blueprint = new Blueprint();
        UpdaterStub::$blueprint->startFromScratch();
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...Showing differences:
   - table needs creating migration

 No files generated.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldThrowNotSupportedWarning(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $this->db->method('getSchema')->willReturn($schema);

        $this->controller->migrationPath = ['test'];
        $this->controller->updaterClass = UpdaterStub::class;
        UpdaterStub::$blueprint = new Blueprint();
        UpdaterStub::$throwForPrepare = true;
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...WARNING!
 > Updating table \'test\' requires manual migration!
 > Stub Exception

 No files generated.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldStopUpdateWhenThereArePostponedForeignKeysAndSchemaIsSqlite(): void
    {
        $schema = $this->createMock(SqliteSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturnOnConsecutiveCalls(
            [],
            [],
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
        $this->controller->migrationPath = ['test'];
        self::assertSame(ExitCode::DATAERR, $this->controller->actionUpdate('*'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Comparing current table \'test\' with its migrations ...DONE!

 > Comparing current table \'test2\' with its migrations ...DONE!
ERROR!
 > Generating migrations for provided tables in batch is not possible because \'ADD FOREIGN KEY\' is not supported by SQLite!
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldStopUpdateManyMigrationsWithPostponedForeignKeysWhenThereIsException(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturnOnConsecutiveCalls(
            [],
            [],
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

        $this->controller->migrationPath = ['test'];
        GeneratorStub::$throwForKeys = true;
        $this->controller->generatorClass = GeneratorStub::class;
        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Comparing current table \'test\' with its migrations ...DONE!

 > Comparing current table \'test2\' with its migrations ...DONE!

 > Generating migration for creating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php\'

 > Generating migration for creating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test2.php\'

 > Generating migration for creating foreign keys ...ERROR!
 > Stub exception
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldStopUpdateManyMigrationsWhenThereIsException(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->migrationPath = ['test'];
        GeneratorStub::$throwForTable = true;
        $this->controller->generatorClass = GeneratorStub::class;
        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionUpdate('*'));
        self::assertSame(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Comparing current table \'test\' with its migrations ...DONE!

 > Comparing current table \'test2\' with its migrations ...DONE!

 > Generating migration for creating table \'test\' ...ERROR!
 > Stub exception
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldUpdateMigrationWhenUpdateDataIsAvailable(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->migrationPath = ['test'];
        UpdaterStub::$blueprint = new Blueprint();
        UpdaterStub::$blueprint->addDescription('change');
        $this->controller->updaterClass = UpdaterStub::class;
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            '
 > Comparing current table \'test\' with its migrations ...DONE!

 > Generating migration for updating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_update_table_test.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldUpdateManyMigrationWhenUpdateDataIsAvailable(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test', 'test2']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->migrationPath = ['test'];
        UpdaterStub::$blueprint = new Blueprint();
        UpdaterStub::$blueprint->addDescription('change');
        $this->controller->updaterClass = UpdaterStub::class;
        self::assertSame(ExitCode::OK, $this->controller->actionUpdate('*'));
        self::assertStringContainsString(
            ' > Are you sure you want to generate migrations for the following tables?
   - test
   - test2
 > Comparing current table \'test\' with its migrations ...DONE!

 > Comparing current table \'test2\' with its migrations ...DONE!

 > Generating migration for updating table \'test\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_update_table_test.php\'

 > Generating migration for updating table \'test2\' ...DONE!
 > Saved as \'/m',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_update_table_test2.php\'

 Generated 2 files
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldStopUpdateWhenUpdateDataIsAvailableButThereIsException(): void
    {
        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        $this->controller->migrationPath = ['test'];
        UpdaterStub::$throwForGenerate = true;
        UpdaterStub::$blueprint = new Blueprint();
        UpdaterStub::$blueprint->addDescription('change');
        $this->controller->updaterClass = UpdaterStub::class;
        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $this->controller->actionUpdate('*'));
        self::assertSame(
            '
 > Comparing current table \'test\' with its migrations ...DONE!

 > Generating migration for updating table \'test\' ...ERROR!
 > Stub Exception
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldCreateDirectoryForPath(): void
    {
        chmod(__DIR__ . '/../../runtime', 0777);

        FileHelper::removeDirectory(__DIR__ . '/../../runtime/test');

        $controller = new MigrationControllerStoringStub('id', $this->createMock(Module::class));
        $controller->db = $this->db;
        $controller->migrationPath = '@bizley/tests/runtime/test';

        $action = $this->createMock(Action::class);
        $action->id = 'create';
        $controller->beforeAction($action);

        self::assertDirectoryExists(__DIR__ . '/../../runtime/test');
    }

    /**
     * @test
     */
    public function shouldCreateDirectoryForPathByNamespace(): void
    {
        chmod(__DIR__ . '/../../runtime', 0777);

        FileHelper::removeDirectory(__DIR__ . '/../../runtime/test');

        $controller = new MigrationControllerStoringStub('id', $this->createMock(Module::class));
        $controller->db = $this->db;
        $controller->migrationNamespace = 'bizley\\tests\\runtime\\test';

        $action = $this->createMock(Action::class);
        $action->id = 'create';
        $controller->beforeAction($action);

        self::assertDirectoryExists(__DIR__ . '/../../runtime/test');
    }

    /**
     * @test
     */
    public function shouldStoreOneMigration(): void
    {
        chmod(__DIR__ . '/../../runtime', 0777);

        $potentialFiles = glob(__DIR__ . '/../../runtime/m??????_??????_create_table_test.php');
        foreach ($potentialFiles as $potentialFile) {
            unlink($potentialFile);
        }

        $controller = new MigrationControllerStoringStub('id', $this->createMock(Module::class));
        $controller->db = $this->db;
        $controller->view = $this->view;
        $controller->migrationPath = '@bizley/tests/runtime';

        $action = $this->createMock(Action::class);
        $action->id = 'create';
        $controller->beforeAction($action);

        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        self::assertSame(ExitCode::OK, $controller->actionCreate('test'));
        self::assertNotEmpty(glob(__DIR__ . '/../../runtime/m??????_??????_create_table_test.php'));
    }

    /**
     * @test
     */
    public function shouldNotStoreOneMigration(): void
    {
        chmod(__DIR__ . '/../../runtime', 0644);
        MigrationControllerStoringStub::$stdout = '';

        $controller = new MigrationControllerStoringStub('id', $this->createMock(Module::class));
        $controller->db = $this->db;
        $controller->view = $this->view;
        $controller->migrationPath = '@bizley/tests/runtime';

        $action = $this->createMock(Action::class);
        $action->id = 'create';
        $controller->beforeAction($action);

        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['test']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $controller->actionCreate('test'));
        self::assertStringContainsString(
            ' > Generating migration for creating table \'test\' ...ERROR!
 > file_put_contents(',
            MigrationControllerStoringStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test.php): failed to open stream: permission denied',
            strtolower(MigrationControllerStoringStub::$stdout) // PHP 8 changed case in message
        );

        chmod(__DIR__ . '/../../runtime', 0777);
    }

    public function providerForFileMode(): array
    {
        return [
            'oct' => [0777],
            'int' => [511],
            'oct string' => ['0777'],
            'int string' => ['511'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForFileMode
     * @param string|int $mode
     */
    public function shouldChangeMigrationPermissions($mode): void
    {
        chmod(__DIR__ . '/../../runtime', 0777);

        $potentialFiles = glob(__DIR__ . '/../../runtime/m??????_??????_create_table_testFileMode.php');
        foreach ($potentialFiles as $potentialFile) {
            unlink($potentialFile);
        }

        $controller = new MigrationController(
            'id',
            $this->createMock(Module::class),
            [
                'request' => Request::class,
                'response' => Response::class
            ]
        );
        $controller->db = $this->db;
        $controller->view = $this->view;
        $controller->fileMode = $mode;
        $controller->migrationPath = '@bizley/tests/runtime';

        $action = $this->createMock(Action::class);
        $action->id = 'create';
        $controller->beforeAction($action);

        $schema = $this->createMock(MysqlSchema::class);
        $schema->method('getTableNames')->willReturn(['testFileMode']);
        $schema->method('getRawTableName')->willReturn('mig');
        $schema->method('getTableForeignKeys')->willReturn([]);
        $schema->method('getTableIndexes')->willReturn([]);
        $this->db->method('getSchema')->willReturn($schema);
        $tableSchema = $this->createMock(TableSchema::class);
        $this->db->method('getTableSchema')->willReturn($tableSchema);

        self::assertSame(ExitCode::OK, $controller->actionCreate('testFileMode'));

        $generatedFiles = glob(__DIR__ . '/../../runtime/m??????_??????_create_table_testFileMode.php');
        self::assertNotEmpty($generatedFiles);
        foreach ($generatedFiles as $generatedFile) {
            self::assertSame('0777', substr(sprintf('%o', fileperms($generatedFile)), -4));
        }
    }
}
