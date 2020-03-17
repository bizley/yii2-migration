<?php

declare(strict_types=1);

namespace bizley\migration\controllers;

use bizley\migration\Arranger;
use bizley\migration\Generator;
use bizley\migration\HistoryManager;
use bizley\migration\HistoryManagerInterface;
use bizley\migration\Schema;
use bizley\migration\Updater;
use Yii;
use yii\base\Action;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Exception as DbException;
use yii\di\Instance;
use yii\helpers\Console;
use yii\helpers\FileHelper;

use function array_merge;
use function count;
use function explode;
use function file_put_contents;
use function gmdate;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function strlen;
use function strpos;

/**
 * Migration creator and updater.
 * Generates migration file based on the existing database table and previous migrations.
 *
 * @author Paweł Bizley Brzozowski
 * @version 4.0.0
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 */
class OldMigrationController extends Controller
{
    /** @var string */
    protected $version = '4.0.0';

    /** @var string Default command action. */
    public $defaultAction = 'list';

    /**
     * @var string|array Directory storing the migration classes.
     * This can be either a path alias or a directory or array of these in which case the first element will be used
     * for generator and only first one will be created if it doesn't exist yet.
     */
    public $migrationPath = '@app/migrations';

    /**
     * @var string|array Full migration namespace.
     * If given it's used instead of $migrationPath. Note that backslash (\) symbol is usually considered a special
     * character in the shell, so you need to escape it properly to avoid shell errors or incorrect behavior.
     * Migration namespace should be resolvable as a path alias if prefixed with @, e.g. if you specify the namespace
     * 'app\migrations', the code Yii::getAlias('@app/migrations') should be able to return the file path to
     * the directory this namespace refers to. If array of namespaces is provided the first element will be used for
     * generator and only first one will be checked for corresponding directory to exist and be created if needed.
     * When this property is given $migrationPath is ignored.
     */
    public $migrationNamespace;

    /**
     * @var string Template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     */
    public $templateFileCreate = '@bizley/migration/views/create_migration.php';

    /**
     * @var string Template file for generating updating migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     */
    public $templateFileUpdate = '@bizley/migration/views/update_migration.php';

    /**
     * @var string Template file for generating new foreign keys migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     */
    public $templateFileForeignKey = '@bizley/migration/views/create_fk_migration.php';

    /**
     * @var bool Whether the table names generated should consider the $tablePrefix setting of the DB connection.
     * For example, if the table name is 'post' the generator will return '{{%post}}'.
     */
    public $useTablePrefix = true;

    /**
     * @var Connection|array|string DB connection object, configuration array, or the application component ID of
     * the DB connection.
     */
    public $db = 'db';

    /**
     * @var string Name of the table for keeping applied migration information.
     * The same as in yii\console\controllers\MigrateController::$migrationTable.
     */
    public $migrationTable = '{{%migration}}';

    /** @var bool Whether to only display changes instead of generating update migration. */
    public $showOnly = false;

    /** @var bool Whether to use general column schema instead of database specific. */
    public $generalSchema = true;

    /** @var bool Whether to add generated migration to migration history. */
    public $fixHistory = false;

    /**
     * @var array List of migrations from the history table that should be skipped during the update process.
     * Here you can place migrations containing actions that can not be covered by extractor.
     */
    public $skipMigrations = [];

    /**
     * @var string String rendered in the create migration template to initialize table options.
     * By default it adds variable "$tableOptions" with optional collate configuration for MySQL DBMS to be used with
     * default $tableOptions.
     */
    public $tableOptionsInit = '$tableOptions = null;
        if ($this->db->driverName === \'mysql\') {
            $tableOptions = \'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB\';
        }';

    /**
     * @var string String rendered in the create migration template for table options.
     * By default it renders "$tableOptions" to indicate that options should be taken from variable
     * set in $tableOptionsInit property.
     */
    public $tableOptions = '$tableOptions';

    /** @var array List of database tables that should be skipped for *-all actions. */
    public $excludeTables = [];

    /** {@inheritdoc} */
    public function options($actionID): array // BC declaration
    {
        $defaultOptions = array_merge(parent::options($actionID), ['db']);

        $createOptions = [
            'fixHistory',
            'generalSchema',
            'migrationNamespace',
            'migrationPath',
            'migrationTable',
            'tableOptions',
            'tableOptionsInit',
            'templateFileCreate',
            'templateFileForeignKey',
            'useTablePrefix',
        ];
        $updateOptions = ['showOnly', 'skipMigrations', 'templateFileUpdate'];

        switch ($actionID) {
            case 'create':
                return array_merge($defaultOptions, $createOptions);

            case 'create-all':
                return array_merge($defaultOptions, $createOptions, ['excludeTables']);

            case 'update':
                return array_merge($defaultOptions, $createOptions, $updateOptions);

            case 'update-all':
                return array_merge($defaultOptions, $createOptions, $updateOptions, ['excludeTables']);

            default:
                return $defaultOptions;
        }
    }

    /** {@inheritdoc} */
    public function optionAliases(): array
    {
        return array_merge(
            parent::optionAliases(),
            [
                'F' => 'templateFile',
                'g' => 'generalSchema',
                'h' => 'fixHistory',
                'K' => 'templateFileForeignKey',
                'n' => 'migrationNamespace',
                'O' => 'tableOptionsInit',
                'o' => 'tableOptions',
                'P' => 'useTablePrefix',
                'p' => 'migrationPath',
                's' => 'showOnly',
                't' => 'migrationTable',
                'U' => 'templateFileUpdate',
            ]
        );
    }

    /** @var string */
    private $workingPath;

    /**
     * This method is invoked right before an action is to be executed (after all possible filters).
     * It checks the existence of the workingPath and makes sure DB connection is prepared.
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function beforeAction($action): bool // BC declaration
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (in_array($action->id, ['create', 'create-all', 'update', 'update-all'], true)) {
            if ($this->migrationNamespace !== null) {
                if (is_array($this->migrationNamespace) === false) {
                    $this->migrationNamespace = [$this->migrationNamespace];
                }
                foreach ($this->migrationNamespace as &$namespace) {
                    $namespace = FileHelper::normalizePath($namespace, '\\');

                    if ($this->workingPath === null && $this->showOnly === false) {
                        $this->workingPath = $this->preparePathDirectory(
                            '@' . FileHelper::normalizePath($namespace, '/')
                        );
                    }
                }
            } elseif ($this->migrationPath !== null) {
                if (is_array($this->migrationPath) === false) {
                    $this->migrationPath = [$this->migrationPath];
                }
                foreach ($this->migrationPath as $path) {
                    if ($this->workingPath === null && $this->showOnly === false) {
                        $this->workingPath = $this->preparePathDirectory($path);
                        break;
                    }
                }
            } else {
                throw new InvalidConfigException(
                    'You must provide either "migrationPath" or "migrationNamespace" for this action.'
                );
            }
        }

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->stdout("Yii 2 Migration Generator Tool v{$this->version}\n\n", Console::FG_CYAN);

        return true;
    }

    /**
     * Prepares path directory.
     * @param string $path
     * @return string
     * @throws Exception
     */
    protected function preparePathDirectory(string $path): string
    {
        $translatedPath = Yii::getAlias($path);

        if (is_dir($translatedPath) === false) {
            FileHelper::createDirectory($translatedPath);
        }

        return $translatedPath;
    }



    /**
     * @param string $path
     * @param mixed $content
     * @return bool|int
     */
    protected function generateFile(string $path, $content)
    {
        return file_put_contents($path, $content);
    }

    /**
     * Removes excluded tables names from the tables list.
     * @param array $tables
     * @return array
     */
    protected function removeExcludedTables(array $tables): array
    {
        if (!$tables) {
            return [];
        }

        $filteredTables = [];
        $excludedTables = array_merge(
            [$this->db->schema->getRawTableName($this->migrationTable)],
            $this->excludeTables
        );

        foreach ($tables as $table) {
            if (in_array($table, $excludedTables, true) === false) {
                $filteredTables[] = $table;
            }
        }

        return $filteredTables;
    }

    /**
     * Lists all tables in the database.
     * @return int
     */
    public function actionList(): int
    {
        $tables = $this->db->schema->getTableNames();

        if (!$tables) {
            $this->stdout(" > Your database does not contain any tables yet.\n");
        } else {
            $tablesCount = count($tables);
            $this->stdout(" > Your database contains {$tablesCount} table" . ($tablesCount > 1 ? 's' : '') . ":\n");

            foreach ($tables as $table) {
                $this->stdout("   - $table\n");
            }
        }

        $this->stdout("\n > Run\n", Console::FG_GREEN);

        $tab = $this->ansiFormat('<table>', Console::FG_YELLOW);
        $cmd = $this->ansiFormat('migration/create', Console::FG_CYAN);
        $this->stdout("   $cmd $tab\n");

        $this->stdout("      to generate creating migration for the specific table.\n", Console::FG_GREEN);

        $cmd = $this->ansiFormat('migration/create-all', Console::FG_CYAN);
        $this->stdout("   $cmd\n");

        $this->stdout("      to generate creating migrations for all the tables.\n", Console::FG_GREEN);

        $cmd = $this->ansiFormat('migration/update', Console::FG_CYAN);
        $this->stdout("   $cmd $tab\n");

        $this->stdout("      to generate updating migration for the specific table.\n", Console::FG_GREEN);

        $cmd = $this->ansiFormat('migration/update-all', Console::FG_CYAN);
        $this->stdout("   $cmd\n");

        $this->stdout("      to generate updating migrations for all the tables.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    protected function getArranger(): Arranger
    {
        return new Arranger(['db' => $this->db]);
    }

    /**
     * Creates new migration for the given tables.
     * @param string $table Table names separated by commas.
     * @return int
     * @throws DbException
     * @throws InvalidConfigException
     */
    public function actionCreate(string $table): int
    {
        $tables = [$table];
        if (strpos($table, ',') !== false) {
            $tables = explode(',', $table);
        }

        $countTables = count($tables);
        $suppressForeignKeys = [];
        if ($countTables > 1) {
            $arranger = $this->getArranger();
            $arranger->arrangeMigrations($tables);
            $tables = $arranger->getTablesInOrder();
            $suppressForeignKeys = $arranger->getSuppressedForeignKeys();

            if (
                count($suppressForeignKeys)
                && Schema::identifySchema(get_class($this->db->schema)) === Schema::SQLITE
            ) {
                $this->stdout(
                    "WARNING!\n > Creating provided tables in batch requires manual migration!\n",
                    Console::FG_RED
                );

                return ExitCode::DATAERR;
            }
        }

        $postponedForeignKeys = [];

        $counterSize = strlen((string) $countTables) + 1;
        $migrationsGenerated = 0;
        foreach ($tables as $name) {
            $this->stdout(" > Generating create migration for table '{$name}' ...", Console::FG_YELLOW);

            if ($countTables > 1) {
                $className
                    = sprintf(
                        "m%s_%0{$counterSize}d_create_table_%s",
                        gmdate('ymd_His'),
                        $migrationsGenerated + 1,
                        $name
                    );
            } else {
                $className = sprintf('m%s_create_table_%s', gmdate('ymd_His'), $name);
            }
            $file = $this->workingPath . DIRECTORY_SEPARATOR . $className . '.php';

            $generator
                = new Generator([
                    'db' => $this->db,
                    'view' => $this->view,
                    'useTablePrefix' => $this->useTablePrefix,
                    'templateFileCreate' => $this->templateFileCreate,
                    'tableName' => $name,
                    'className' => $className,
                    'namespace' => $this->migrationNamespace,
                    'generalSchema' => $this->generalSchema,
                    'tableOptionsInit' => $this->tableOptionsInit,
                    'tableOptions' => $this->tableOptions,
                    'suppressForeignKey' => $suppressForeignKeys[$name] ?? [],
                ]);

            if ($generator->getTableSchema() === null) {
                $this->stdout("ERROR!\n > Table '{$name}' does not exist!\n\n", Console::FG_RED);

                return ExitCode::DATAERR;
            }

            if ($this->generateFile($file, $generator->generateMigration()) === false) {
                $this->stdout(
                    "ERROR!\n > Migration file for table '{$name}' can not be generated!\n\n",
                    Console::FG_RED
                );

                return ExitCode::SOFTWARE;
            }

            $migrationsGenerated++;

            $this->stdout("DONE!\n", Console::FG_GREEN);
            $this->stdout(" > Saved as '{$file}'\n");

            if ($this->fixHistory) {
                if ($this->db->schema->getTableSchema($this->migrationTable, true) === null) {
                    $this->createMigrationHistoryTable();
                }

                $this->addMigrationHistory($className, $this->migrationNamespace);
            }

            $this->stdout("\n");

            $suppressedKeys = $generator->getSuppressedForeignKeys();
            foreach ($suppressedKeys as $suppressedKey) {
                $postponedForeignKeys[] = $suppressedKey;
            }
        }

        if ($postponedForeignKeys) {
            $this->stdout(' > Generating create migration for foreign keys ...', Console::FG_YELLOW);

            $className
                = sprintf(
                    "m%s_%0{$counterSize}d_create_foreign_keys",
                    gmdate('ymd_His'),
                    ++$migrationsGenerated
                );
            $file = $this->workingPath . DIRECTORY_SEPARATOR . $className . '.php';

            if (
                $this->generateFile(
                    $file,
                    $this->view->renderFile(
                        Yii::getAlias($this->templateFileForeignKey),
                        [
                            'fks' => $postponedForeignKeys,
                            'className' => $className,
                            'namespace' => $this->migrationNamespace
                        ]
                    )
                ) === false
            ) {
                $this->stdout(
                    "ERROR!\n > Migration file for foreign keys can not be generated!\n\n",
                    Console::FG_RED
                );

                return ExitCode::SOFTWARE;
            }

            $this->stdout("DONE!\n", Console::FG_GREEN);
            $this->stdout(" > Saved as '{$file}'\n");

            if ($this->fixHistory) {
                $this->addMigrationHistory($className, $this->migrationNamespace);
            }
        }

        if ($migrationsGenerated) {
            $this->stdout(" Generated $migrationsGenerated file(s).\n", Console::FG_YELLOW);
            $this->stdout(" (!) Remember to verify files before applying migration.\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout(" No files generated.\n\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Creates new migrations for every table in database.
     * @return int
     * @throws DbException
     * @throws InvalidConfigException
     */
    public function actionCreateAll(): int
    {
        $tables = $this->removeExcludedTables($this->db->schema->getTableNames());

        if (!$tables) {
            $this->stdout(' > Your database does not contain any tables yet.', Console::FG_YELLOW);

            return ExitCode::OK;
        }

        $tablesCount = count($tables);
        if (
            $this->confirm(
                " > Are you sure you want to generate $tablesCount migration"
                . ($tablesCount > 1 ? 's' : '')
                . '?'
            )
        ) {
            return $this->actionCreate(implode(',', $tables));
        }

        $this->stdout(" Operation cancelled by user.\n\n", Console::FG_YELLOW);

        return ExitCode::OK;
    }

    /**
     * Creates new update migration for the given tables.
     * @param string $table Table names separated by commas.
     * @return int
     * @throws ErrorException
     * @throws DbException
     * @throws InvalidConfigException
     */
    public function actionUpdate(string $table): int
    {
        $tables = [$table];
        if (strpos($table, ',') !== false) {
            $tables = explode(',', $table);
        }

        $migrationsGenerated = 0;
        foreach ($tables as $name) {
            $this->stdout(" > Generating update migration for table '{$name}' ...", Console::FG_YELLOW);

            $className = 'm' . gmdate('ymd_His') . '_update_table_' . $name;
            $file = $this->workingPath . DIRECTORY_SEPARATOR . $className . '.php';

            $updater
                = new Updater([
                    'db' => $this->db,
                    'view' => $this->view,
                    'useTablePrefix' => $this->useTablePrefix,
                    'templateFileCreate' => $this->templateFileCreate,
                    'templateFileUpdate' => $this->templateFileUpdate,
                    'tableName' => $name,
                    'className' => $className,
                    'namespace' => $this->migrationNamespace,
                    'migrationPath' => $this->migrationPath,
                    'migrationTable' => $this->migrationTable,
                    'showOnly' => $this->showOnly,
                    'generalSchema' => $this->generalSchema,
                    'skipMigrations' => $this->skipMigrations,
                ]);

            if ($updater->getTableSchema() === null) {
                $this->stdout("ERROR!\n > Table '{$name}' does not exist!\n\n", Console::FG_RED);

                return ExitCode::DATAERR;
            }

            try {
                if ($updater->isUpdateRequired() === false) {
                    $this->stdout("UPDATE NOT REQUIRED.\n\n", Console::FG_YELLOW);

                    continue;
                }
            } catch (NotSupportedException $exception) {
                $this->stdout("WARNING!\n > Updating table '{$name}' requires manual migration!\n", Console::FG_RED);
                $this->stdout(' > ' . $exception->getMessage() . "\n\n", Console::FG_RED);

                continue;
            }

            if (!$this->showOnly) {
                if ($this->generateFile($file, $updater->generateMigration()) === false) {
                    $this->stdout(
                        "ERROR!\n > Migration file for table '{$name}' can not be generated!\n\n",
                        Console::FG_RED
                    );

                    return ExitCode::SOFTWARE;
                }

                $migrationsGenerated++;

                $this->stdout("DONE!\n", Console::FG_GREEN);
                $this->stdout(" > Saved as '{$file}'\n");

                if ($this->fixHistory) {
                    if ($this->db->schema->getTableSchema($this->migrationTable, true) === null) {
                        $this->createMigrationHistoryTable();
                    }

                    $this->addMigrationHistory($className, $this->migrationNamespace);
                }
            }

            $this->stdout("\n");
        }

        if ($migrationsGenerated) {
            $this->stdout(" Generated $migrationsGenerated file(s).\n", Console::FG_YELLOW);
            $this->stdout(" (!) Remember to verify files before applying migration.\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout(" No files generated.\n\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Creates new update migrations for every table in database.
     * @return int
     * @throws ErrorException
     * @throws DbException
     * @throws InvalidConfigException
     */
    public function actionUpdateAll(): int
    {
        $tables = $this->removeExcludedTables($this->db->schema->getTableNames());

        if (!$tables) {
            $this->stdout(' > Your database does not contain any tables yet.', Console::FG_YELLOW);

            return ExitCode::OK;
        }

        $tablesCount = count($tables);
        if (
            $this->confirm(
                " > Are you sure you want to potentially generate $tablesCount migration"
                . ($tablesCount > 1 ? 's' : '')
                . '?'
            )
        ) {
            return $this->actionUpdate(implode(',', $tables));
        }

        $this->stdout(" Operation cancelled by user.\n\n", Console::FG_YELLOW);

        return ExitCode::OK;
    }

    /** @var HistoryManagerInterface */
    private $migrationHistoryManager;

    /**
     * @return HistoryManagerInterface
     */
    public function getMigrationHistoryManager(): HistoryManagerInterface
    {
        if ($this->migrationHistoryManager === null) {
            $this->migrationHistoryManager = new HistoryManager($this->db, $this->migrationTable);
        }

        return $this->migrationHistoryManager;
    }

    /**
     * @param HistoryManagerInterface $migrationHistoryManager
     */
    public function setMigrationHistoryManager(HistoryManagerInterface $migrationHistoryManager): void
    {
        $this->migrationHistoryManager = $migrationHistoryManager;
    }


}
