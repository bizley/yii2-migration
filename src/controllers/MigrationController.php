<?php

declare(strict_types=1);

namespace bizley\migration\controllers;

use bizley\migration\Schema;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\TableMissingException;
use RuntimeException;
use Throwable;
use Yii;
use yii\base\Action;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
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
use function preg_match;
use function sort;
use function sprintf;
use function strlen;
use function strpos;
use function trim;

/**
 * Migration creator and updater.
 * Generates migration files based on the existing database table and previous migrations.
 *
 * @author Paweł Bizley Brzozowski
 * @version 4.0.0
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 */
class MigrationController extends BaseMigrationController
{
    /** @var string */
    private $version = '4.0.0';

    /**
     * @var string|array<string> Directory storing the migration classes.
     * This can be either a path alias or a directory or array of these in which case the first element will be used
     * for generator and only first one will be created if it doesn't exist yet.
     */
    public $migrationPath = '@app/migrations';

    /**
     * @var string|array<string> Full migration namespace.
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
     * @var bool Whether the table names generated should consider the $tablePrefix setting of the DB connection.
     * For example, if the table name is 'post' the generator will return '{{%post}}'.
     */
    public $useTablePrefix = true;

    /** @var bool Whether to only display changes instead of generating update migration. */
    public $onlyShow = false;

    /** @var bool Whether to add generated migration to migration history. */
    public $fixHistory = false;

    /**
     * @var array<string> List of migrations from the history table that should be skipped during the update process.
     * Here you can place migrations containing actions that can not be covered by extractor.
     */
    public $skipMigrations = [];

    /** @var array<string> List of database tables that should be skipped for *-all actions. */
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
            'useTablePrefix',
            'excludeTables'
        ];
        $updateOptions = ['onlyShow', 'skipMigrations'];

        switch ($actionID) {
            case 'create':
                return array_merge($defaultOptions, $createOptions);

            case 'update':
                return array_merge($defaultOptions, $createOptions, $updateOptions);

            default:
                return $defaultOptions;
        }
    }

    /** @return array<int|string, mixed> */
    public function optionAliases(): array
    {
        return array_merge(
            parent::optionAliases(),
            [
                'fh' => 'fixHistory',
                'gs' => 'generalSchema',
                'mn' => 'migrationNamespace',
                'mp' => 'migrationPath',
                'mt' => 'migrationTable',
                'os' => 'onlyShow',
                'tp' => 'useTablePrefix',
            ]
        );
    }

    /** @var string */
    private $workingPath;

    /** @var string|null */
    private $workingNamespace;

    /**
     * Sets the workingPath and workingNamespace and makes sure DB connection is prepared.
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function beforeAction($action): bool // BC declaration
    {
        if (parent::beforeAction($action) === false) {
            return false;
        }

        if (in_array($action->id, ['create', 'update'], true)) {
            if ($this->migrationNamespace !== null) {
                if (is_array($this->migrationNamespace) === false) {
                    $this->migrationNamespace = [$this->migrationNamespace];
                }
                foreach ($this->migrationNamespace as &$namespace) {
                    $namespace = FileHelper::normalizePath($namespace, '\\');

                    if ($this->workingPath === null && $this->onlyShow === false) {
                        $this->workingPath = $this->preparePathDirectory(
                            '@' . FileHelper::normalizePath($namespace, '/')
                        );
                        $this->workingNamespace = $namespace;
                    }
                }
                unset($namespace);
            } elseif ($this->migrationPath !== null) {
                if (is_array($this->migrationPath) === false) {
                    $this->migrationPath = [$this->migrationPath];
                }
                foreach ($this->migrationPath as $path) {
                    if ($this->workingPath === null && $this->onlyShow === false) {
                        $this->workingPath = $this->preparePathDirectory($path);
                        break;
                    }
                }
            } else {
                throw new InvalidConfigException(
                    'You must provide either "migrationPath" or "migrationNamespace" for this action.'
                );
            }

            foreach ($this->skipMigrations as $index => $migration) {
                $this->skipMigrations[$index] = trim($migration, '\\');
            }
        }

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->stdout("Yii 2 Migration Generator Tool v{$this->version}\n", Console::FG_CYAN);

        return true;
    }

    /**
     * Lists all tables in the database.
     * @return int
     * @throws NotSupportedException
     */
    public function actionList(): int
    {
        /** @var Connection $db */
        $db = $this->db;
        $tables = $this->getAllTableNames($db);
        $migrationTable = $db->getSchema()->getRawTableName($this->migrationTable);

        $tablesCount = count($tables);
        if ($tablesCount === 0) {
            $this->stdout(" > Your database does not contain any tables yet.\n");
        } else {
            sort($tables);
            $this->stdout(" > Your database contains {$tablesCount} table" . ($tablesCount > 1 ? 's' : '') . ":\n");

            foreach ($tables as $table) {
                if ($table === $migrationTable) {
                    $this->stdout("   - $table (excluded by default unless explicitly requested)\n");
                } else {
                    $this->stdout("   - $table\n");
                }
            }
        }

        $this->stdout("\n > Run\n", Console::FG_GREEN);

        $tab = $this->ansiFormat('<table>', Console::FG_YELLOW);
        $cmd = $this->ansiFormat('migration/create', Console::FG_CYAN);
        $this->stdout("   $cmd $tab\n");
        $this->stdout("      to generate creating migration for the specific table.\n", Console::FG_GREEN);

        $cmd = $this->ansiFormat('migration/update', Console::FG_CYAN);
        $this->stdout("   $cmd $tab\n");
        $this->stdout("      to generate updating migration for the specific table.\n", Console::FG_GREEN);

        $this->stdout("\n > $tab can be:\n");
        $variant = $this->ansiFormat('* (asterisk)', Console::FG_CYAN);
        $this->stdout("   - $variant - for all the tables in database (except excluded ones)\n");
        $variant = $this->ansiFormat('string with * (one or more)', Console::FG_CYAN);
        $this->stdout("   - $variant - for all the tables in database matching the pattern (except excluded ones)\n");
        $variant = $this->ansiFormat('string without *', Console::FG_CYAN);
        $this->stdout("   - $variant - for the table of specified name\n");
        $variant = $this->ansiFormat('strings separated with comma', Console::FG_CYAN);
        $this->stdout("   - $variant - for multiple tables of specified names (with optional *)\n");

        return ExitCode::OK;
    }

    /**
     * Generates creating migration for the given tables.
     * For multiple tables separate the names with comma. You can provide the name as '*' to generate migrations for all
     * tables in database (except excluded ones) or you can use it as a wildcard for tables with common name part
     * (i.e. 'prefix_*' or 'p1*p2*p3').
     * @param string $inputTable Table name or names separated by commas.
     * @return int
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function actionCreate(string $inputTable): int
    {
        $inputTables = $this->proceedWithOperation($inputTable);
        if ($inputTables === null) {
            return ExitCode::OK;
        }

        $countTables = count($inputTables);
        $referencesToPostpone = [];
        $tables = $inputTables;
        if ($countTables > 1) {
            $this->getArranger()->arrangeTables($inputTables);
            $tables = $this->getArranger()->getTablesInOrder();
            $referencesToPostpone = $this->getArranger()->getReferencesToPostpone();

            /** @var Connection $db */
            $db = $this->db;
            if (count($referencesToPostpone) && Schema::isSQLite($db->getSchema())) {
                $this->stdout(
                    "\nERROR!\n > Generating migrations for provided tables in batch is not possible "
                    . "because 'ADD FOREIGN KEY' is not supported by SQLite!\n",
                    Console::FG_RED
                );

                return ExitCode::DATAERR;
            }
        }

        $postponedForeignKeys = [];

        $counterSize = strlen((string)$countTables) + 1;
        $migrationsGenerated = 0;
        foreach ($tables as $tableName) {
            $this->stdout("\n > Generating migration for creating table '{$tableName}' ...", Console::FG_YELLOW);

            $escapedTableName = str_replace('.', '_', $tableName);
            if ($countTables > 1) {
                $migrationClassName = sprintf(
                    "m%s_%0{$counterSize}d_create_table_%s",
                    gmdate('ymd_His'),
                    $migrationsGenerated + 1,
                    $escapedTableName
                );
            } else {
                $migrationClassName = sprintf('m%s_create_table_%s', gmdate('ymd_His'), $escapedTableName);
            }

            try {
                $this->generateMigrationForTableCreation($tableName, $migrationClassName, $referencesToPostpone);
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $migrationsGenerated++;

            $this->stdout("\n");

            $suppressedForeignKeys = $this->getGenerator()->getSuppressedForeignKeys();
            foreach ($suppressedForeignKeys as $suppressedKey) {
                $postponedForeignKeys[] = $suppressedKey;
            }
        }

        if ($postponedForeignKeys) {
            try {
                $this->generateMigrationForForeignKeys(
                    $postponedForeignKeys,
                    sprintf(
                        "m%s_%0{$counterSize}d_create_foreign_keys",
                        gmdate('ymd_His'),
                        ++$migrationsGenerated
                    )
                );
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $this->stdout(
            "\n Generated $migrationsGenerated file" . ($migrationsGenerated > 1 ? 's' : '') . "\n",
            Console::FG_YELLOW
        );
        $this->stdout(" (!) Remember to verify files before applying migration.\n", Console::FG_YELLOW);

        return ExitCode::OK;
    }

    /**
     * Generates updating migration for the given tables.
     * For multiple tables separate the names with comma. You can provide the name as '*' to generate migrations for all
     * tables in database (except excluded ones) or you can use it as a wildcard for tables with common name part
     * (i.e. 'prefix_*' or 'p1*p2*p3').
     * @param string $inputTable
     * @return int
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function actionUpdate(string $inputTable): int
    {
        $inputTables = $this->proceedWithOperation($inputTable);
        if ($inputTables === null) {
            return ExitCode::OK;
        }

        $blueprints = [];
        $newTables = [];
        /** @var array<string> $migrationPaths */
        $migrationPaths = $this->migrationPath;
        foreach ($inputTables as $tableName) {
            $this->stdout("\n > Comparing current table '{$tableName}' with its migrations ...", Console::FG_YELLOW);

            try {
                $blueprint = $this->getUpdater()->prepareBlueprint(
                    $tableName,
                    $this->onlyShow,
                    $this->skipMigrations,
                    $migrationPaths
                );
                if ($blueprint->isPending() === false) {
                    $this->stdout("TABLE IS UP-TO-DATE.\n", Console::FG_GREEN);

                    continue;
                }
                if ($blueprint->needsStartFromScratch()) {
                    $newTables[] = $tableName;
                } else {
                    $blueprints[$tableName] = $blueprint;
                }

                if ($this->onlyShow) {
                    $this->stdout("Showing differences:\n");
                    if ($blueprint->needsStartFromScratch()) {
                        $this->stdout("   - table needs creating migration\n", Console::FG_YELLOW);
                    } else {
                        $differences = $blueprint->getDescriptions();
                        foreach ($differences as $difference) {
                            $this->stdout(
                                "   - $difference\n",
                                strpos($difference, '(!)') !== false ? Console::FG_RED : Console::FG_YELLOW
                            );
                        }
                    }

                    $this->stdout("\n");
                } else {
                    $this->stdout("DONE!\n");
                }
            } catch (NotSupportedException $exception) {
                $this->stdout(
                    "WARNING!\n > Updating table '{$tableName}' requires manual migration!\n",
                    Console::FG_RED
                );
                $this->stdout(' > ' . $exception->getMessage() . "\n", Console::FG_RED);

                continue;
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n", Console::FG_RED);

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        if ($this->onlyShow) {
            $this->stdout(" No files generated.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $countTables = count($newTables);
        $referencesToPostpone = [];
        if ($countTables > 1) {
            $this->getArranger()->arrangeTables($newTables);
            $newTables = $this->getArranger()->getTablesInOrder();
            $referencesToPostpone = $this->getArranger()->getReferencesToPostpone();

            /** @var Connection $db */
            $db = $this->db;
            if (count($referencesToPostpone) && Schema::isSQLite($db->getSchema())) {
                $this->stdout(
                    "ERROR!\n > Generating migrations for provided tables in batch is not possible "
                    . "because 'ADD FOREIGN KEY' is not supported by SQLite!\n",
                    Console::FG_RED
                );

                return ExitCode::DATAERR;
            }
        }

        $postponedForeignKeys = [];

        $counterSize = strlen((string)$countTables) + 1;
        $migrationsGenerated = 0;
        foreach ($newTables as $tableName) {
            $this->stdout("\n > Generating migration for creating table '{$tableName}' ...", Console::FG_YELLOW);

            try {
                $this->generateMigrationForTableCreation(
                    $tableName,
                    sprintf(
                        "m%s_%0{$counterSize}d_create_table_%s",
                        gmdate('ymd_His'),
                        $migrationsGenerated + 1,
                        str_replace('.', '_', $tableName)
                    ),
                    $referencesToPostpone
                );
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $migrationsGenerated++;

            $this->stdout("\n");

            $suppressedForeignKeys = $this->getGenerator()->getSuppressedForeignKeys();
            foreach ($suppressedForeignKeys as $suppressedKey) {
                $postponedForeignKeys[] = $suppressedKey;
            }
        }

        if ($postponedForeignKeys) {
            try {
                $this->generateMigrationForForeignKeys(
                    $postponedForeignKeys,
                    sprintf(
                        "m%s_%0{$counterSize}d_create_foreign_keys",
                        gmdate('ymd_His'),
                        ++$migrationsGenerated
                    )
                );
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        foreach ($blueprints as $tableName => $blueprint) {
            $this->stdout("\n > Generating migration for updating table '{$tableName}' ...", Console::FG_YELLOW);

            $escapedTableName = str_replace('.', '_', $tableName);
            if ($migrationsGenerated === 0) {
                $migrationClassName = 'm' . gmdate('ymd_His') . '_update_table_' . $escapedTableName;
            } else {
                $migrationClassName = sprintf(
                    "m%s_%0{$counterSize}d_update_table_%s",
                    gmdate('ymd_His'),
                    $migrationsGenerated + 1,
                    $escapedTableName
                );
            }

            try {
                $this->generateMigrationWithBlueprint($blueprint, $migrationClassName);
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $migrationsGenerated++;

            $this->stdout("\n");
        }

        if ($migrationsGenerated) {
            $this->stdout(
                "\n Generated $migrationsGenerated file" . ($migrationsGenerated > 1 ? 's' : '') . "\n",
                Console::FG_YELLOW
            );
            $this->stdout(" (!) Remember to verify files before applying migration.\n", Console::FG_YELLOW);
        } else {
            $this->stdout("\n No files generated.\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Prepares path directory. If directory doesn't exist it's being created.
     * @param string $path
     * @return string
     * @throws Exception
     */
    private function preparePathDirectory(string $path): string
    {
        /** @var string $translatedPath */
        $translatedPath = Yii::getAlias($path);

        if (is_dir($translatedPath) === false) {
            FileHelper::createDirectory($translatedPath);
        }

        return $translatedPath;
    }

    /**
     * Stores the content in a file under the given path.
     * @param string $path
     * @param mixed $content
     * @throws Throwable
     */
    public function storeFile(string $path, $content): void
    {
        try {
            if (file_put_contents($path, $content) === false) {
                throw new RuntimeException('Migration file can not be saved!');
            }
        } catch (Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * Fixes the migration history with a new entry. If migration history table doesn't exist it's being created first.
     * @param string $migrationClassName
     * @throws DbException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function fixHistory(string $migrationClassName): void
    {
        if ($this->fixHistory) {
            $this->stdout("\n > Fixing migration history ...", Console::FG_YELLOW);
            $this->getHistoryManager()->addHistory($migrationClassName, $this->workingNamespace);
            $this->stdout('DONE!', Console::FG_GREEN);
        }
    }

    /**
     * Generates migration for postponed foreign keys.
     * @param array<ForeignKeyInterface> $postponedForeignKeys
     * @param string $migrationClassName
     * @throws DbException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function generateMigrationForForeignKeys(array $postponedForeignKeys, string $migrationClassName): void
    {
        $this->stdout("\n > Generating migration for creating foreign keys ...", Console::FG_YELLOW);

        $file = $this->workingPath . DIRECTORY_SEPARATOR . $migrationClassName . '.php';

        /** @var Connection $db */
        $db = $this->db;
        $migration = $this->getGenerator()->generateForForeignKeys(
            $postponedForeignKeys,
            $migrationClassName,
            $this->useTablePrefix,
            $db->tablePrefix,
            $this->workingNamespace
        );

        $this->storeFile($file, $migration);

        $this->stdout("DONE!\n", Console::FG_GREEN);
        $this->stdout(" > Saved as '{$file}'\n");

        $this->fixHistory($migrationClassName);
    }

    /**
     * Generates updating migration based on a blueprint.
     * @param BlueprintInterface $blueprint
     * @param string $migrationClassName
     * @throws DbException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function generateMigrationWithBlueprint(BlueprintInterface $blueprint, string $migrationClassName): void
    {
        $file = $this->workingPath . DIRECTORY_SEPARATOR . $migrationClassName . '.php';

        /** @var Connection $db */
        $db = $this->db;
        $migration = $this->getUpdater()->generateFromBlueprint(
            $blueprint,
            $migrationClassName,
            $this->useTablePrefix,
            $db->tablePrefix,
            $this->workingNamespace
        );

        $this->storeFile($file, $migration);

        $this->stdout("DONE!\n", Console::FG_GREEN);
        $this->stdout(" > Saved as '{$file}'");

        $this->fixHistory($migrationClassName);
    }

    /**
     * Generates creating migration based on a table structure.
     * @param string $tableName
     * @param string $migrationClassName
     * @param array<string> $referencesToPostpone
     * @throws DbException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws TableMissingException
     */
    private function generateMigrationForTableCreation(
        string $tableName,
        string $migrationClassName,
        array $referencesToPostpone
    ): void {
        $file = $this->workingPath . DIRECTORY_SEPARATOR . $migrationClassName . '.php';

        /** @var Connection $db */
        $db = $this->db;
        $migration = $this->getGenerator()->generateForTable(
            $tableName,
            $migrationClassName,
            $referencesToPostpone,
            $this->useTablePrefix,
            $db->tablePrefix,
            $this->workingNamespace
        );

        $this->storeFile($file, $migration);

        $this->stdout("DONE!\n", Console::FG_GREEN);
        $this->stdout(" > Saved as '{$file}'");

        $this->fixHistory($migrationClassName);
    }

    /**
     * Prepares table names based on an input. Resulting names must not be on an excluded list. Migration history table
     * is always on the excluded list by default.
     * @param string $inputTables
     * @return array<string>
     * @throws NotSupportedException
     */
    private function prepareTableNames($inputTables): array
    {
        if (strpos($inputTables, ',') !== false) {
            $tablesList = explode(',', $inputTables);
        } else {
            $tablesList = [$inputTables];
        }

        /** @var Connection $db */
        $db = $this->db;
        $allTables = $this->getAllTableNames($db);
        if (count($allTables) === 0) {
            return [];
        }
        $excludedTables = array_merge(
            [$db->getSchema()->getRawTableName($this->migrationTable)],
            $this->excludeTables
        );

        $tables = [];

        if (in_array('*', $tablesList, true)) {
            $tables = $this->findMatchingTables(null, $allTables, $excludedTables);
        } else {
            foreach ($tablesList as $inputTable) {
                $matchedTables = $this->findMatchingTables($inputTable, $allTables, $excludedTables);
                foreach ($matchedTables as $matchedTable) {
                    $tables[] = $matchedTable;
                }
            }
        }

        return $tables;
    }

    /** @var array<string> */
    private $foundExcluded = [];

    /**
     * Finds tables matching the pattern.
     * @param string|null $pattern
     * @param array<string> $allTables
     * @param array<string> $excludedTables
     * @return array<string>
     */
    private function findMatchingTables(
        string $pattern = null,
        array $allTables = [],
        array $excludedTables = []
    ): array {
        $filteredTables = [];

        foreach ($allTables as $table) {
            if (in_array($table, $excludedTables, true) === false) {
                if ($pattern && preg_match('/^' . str_replace('*', '(.+)', $pattern) . '$/', $table) === 0) {
                    continue;
                }
                $filteredTables[] = $table;
            } else {
                $this->foundExcluded[] = $table;
            }
        }

        return $filteredTables;
    }

    /**
     * Prepares table names and adds confirmation for proceeding with generating for the user.
     * @param string $inputTable
     * @return array<string>|null
     * @throws NotSupportedException
     */
    private function proceedWithOperation(string $inputTable): ?array
    {
        $inputTables = $this->prepareTableNames($inputTable);
        $this->foundExcluded = array_unique($this->foundExcluded);
        $foundExcludedCount = count($this->foundExcluded);
        $excludedInfo = null;
        if ($foundExcludedCount) {
            $excludedInfo = " > $foundExcludedCount table"
                . ($foundExcludedCount > 1 ? 's' : '')
                . " excluded by the config.\n";
        }
        $countTables = count($inputTables);
        if ($countTables === 0) {
            $this->stdout("\n > No matching tables in database.\n", Console::FG_YELLOW);
            if ($excludedInfo) {
                $this->stdout($excludedInfo, Console::FG_YELLOW);
            }
            return null;
        }
        if ($excludedInfo) {
            $this->stdout($excludedInfo, Console::FG_YELLOW);
        }
        if (
            $countTables > 1
            && $this->confirm(
                " > Are you sure you want to generate migrations for the following tables?\n   - "
                . implode("\n   - ", $inputTables)
            ) === false
        ) {
            $this->stdout("\n Operation cancelled by user.\n", Console::FG_YELLOW);
            return null;
        }

        return $inputTables;
    }

    /**
     * @param Connection $db
     * @return array
     * @throws NotSupportedException
     */
    public function getAllTableNames(Connection $db): array
    {
        $tables = array();

        try {
            $schemaNames = $db->getSchema()->getSchemaNames(true);
        } catch (NotSupportedException $ex) {
        }

        if (is_null($schemaNames) || count($schemaNames) < 2) {
            $tables = $db->getSchema()->getTableNames();
        } else {
            foreach ($db->getSchema()->getSchemaNames() as $schemaName) {
                $tables = array_merge(
                    $tables,
                    array_column($db->getSchema()->getTableSchemas($schemaName), 'fullName')
                );
            }
        }
        return $tables;
    }
}
