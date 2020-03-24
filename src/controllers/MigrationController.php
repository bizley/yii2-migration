<?php

declare(strict_types=1);

namespace bizley\migration\controllers;

use bizley\migration\Schema;
use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\ForeignKeyInterface;
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
        $updateOptions = ['showOnly', 'skipMigrations'];

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
                'g' => 'generalSchema',
                'h' => 'fixHistory',
                'n' => 'migrationNamespace',
                'P' => 'useTablePrefix',
                'p' => 'migrationPath',
                's' => 'showOnly',
                't' => 'migrationTable',
            ]
        );
    }

    /** @var string */
    private $workingPath;

    /** @var string|null */
    private $workingNamespace;

    /**
     * It checks the existence of the workingPath and makes sure DB connection is prepared.
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

        if (in_array($action->id, ['create', 'create-all', 'update', 'update-all'], true)) {
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
        }

        foreach ($this->skipMigrations as $index => $migration) {
            $this->skipMigrations[$index] = trim($migration, '\\');
        }

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->stdout("Yii 2 Migration Generator Tool v{$this->version}\n\n", Console::FG_CYAN);

        return true;
    }

    /**
     * Lists all tables in the database.
     * @return int
     */
    public function actionList(): int
    {
        /** @var Connection $db */
        $db = $this->db;
        $tables = $db->schema->getTableNames();
        $migrationTable = $db->schema->getRawTableName($this->migrationTable);

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
        $this->stdout("   - $variant - for the tables of specified name\n");
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
     */
    public function actionCreate(string $inputTable): int
    {
        $inputTables = $this->prepareTableNames($inputTable);
        $countTables = count($inputTables);
        if ($countTables === 0) {
            $this->stdout(' > No matching tables in database.', Console::FG_YELLOW);

            return ExitCode::OK;
        }
        if (
            $countTables > 1
            && $this->confirm(
                " > Are you sure you want to generate migrations for the following tables?\n   - "
                . implode("\n   - ", $inputTables)
            ) === false
        ) {
            $this->stdout(" Operation cancelled by user.\n\n", Console::FG_YELLOW);

            return ExitCode::OK;
        }

        $referencesToPostpone = [];
        $tables = $inputTables;
        if ($countTables > 1) {
            $this->getArranger()->arrangeMigrations($inputTables);
            $tables = $this->getArranger()->getTablesInOrder();
            $referencesToPostpone = $this->getArranger()->getReferencesToPostpone();

            /** @var Connection $db */
            $db = $this->db;
            if (count($referencesToPostpone) && Schema::isSQLite($db->schema)) {
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
        foreach ($tables as $tableName) {
            $this->stdout(" > Generating migration for creating table '{$tableName}' ...", Console::FG_YELLOW);

            if ($countTables > 1) {
                $migrationClassName = sprintf(
                    "m%s_%0{$counterSize}d_create_table_%s",
                    gmdate('ymd_His'),
                    $migrationsGenerated + 1,
                    $tableName
                );
            } else {
                $migrationClassName = sprintf('m%s_create_table_%s', gmdate('ymd_His'), $tableName);
            }

            try {
                $this->generateMigrationForTableCreation($tableName, $migrationClassName, $referencesToPostpone);
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
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
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        if ($migrationsGenerated) {
            $this->stdout(
                " Generated $migrationsGenerated file" . ($migrationsGenerated > 1 ? 's' : '') . "\n",
                Console::FG_YELLOW
            );
            $this->stdout(" (!) Remember to verify files before applying migration.\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout(" No files generated.\n\n", Console::FG_YELLOW);
        }

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
     */
    public function actionUpdate(string $inputTable): int
    {
        $inputTables = $this->prepareTableNames($inputTable);
        $countTables = count($inputTables);
        if ($countTables === 0) {
            $this->stdout(' > No matching tables in database.', Console::FG_YELLOW);

            return ExitCode::OK;
        }
        if (
            $countTables > 1
            && $this->confirm(
                " > Are you sure you want to generate migrations for the following tables?\n   - "
                . implode("\n   - ", $inputTables)
            ) === false
        ) {
            $this->stdout(" Operation cancelled by user.\n\n", Console::FG_YELLOW);

            return ExitCode::OK;
        }

        $blueprints = [];
        $newTables = [];
        /** @var array<string> $migrationPaths */
        $migrationPaths = $this->migrationPath;
        foreach ($inputTables as $tableName) {
            $this->stdout(" > Comparing current table '{$tableName}' with its migrations ...", Console::FG_YELLOW);

            try {
                $blueprint = $this->getUpdater()->prepareBlueprint(
                    $tableName,
                    $this->onlyShow,
                    $this->skipMigrations,
                    $migrationPaths
                );
                if ($blueprint->isPending() === false) {
                    $this->stdout("TABLE IS UP-TO-DATE.\n\n", Console::FG_GREEN);

                    continue;
                }
                if ($blueprint->isStartFromScratch()) {
                    $newTables[] = $tableName;
                } else {
                    $blueprints[$tableName] = $blueprint;
                }

                if ($this->onlyShow) {
                    $this->stdout("Showing differences:\n");
                    if ($blueprint->isStartFromScratch()) {
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
                }
            } catch (NotSupportedException $exception) {
                $this->stdout(
                    "WARNING!\n > Updating table '{$tableName}' requires manual migration!\n",
                    Console::FG_RED
                );
                $this->stdout(' > ' . $exception->getMessage() . "\n\n", Console::FG_RED);

                continue;
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        if ($this->onlyShow) {
            $this->stdout(" No files generated.\n\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $countTables = count($newTables);
        $referencesToPostpone = [];
        if ($countTables > 1) {
            $this->getArranger()->arrangeMigrations($newTables);
            $newTables = $this->getArranger()->getTablesInOrder();
            $referencesToPostpone = $this->getArranger()->getReferencesToPostpone();

            /** @var Connection $db */
            $db = $this->db;
            if (count($referencesToPostpone) && Schema::isSQLite($db->schema)) {
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
            $this->stdout(" > Generating migration for creating table '{$tableName}' ...", Console::FG_YELLOW);

            try {
                $this->generateMigrationForTableCreation(
                    $tableName,
                    sprintf(
                        "m%s_%0{$counterSize}d_create_table_%s",
                        gmdate('ymd_His'),
                        $migrationsGenerated + 1,
                        $tableName
                    ),
                    $referencesToPostpone
                );
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
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
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        foreach ($blueprints as $tableName => $blueprint) {
            $this->stdout(" > Generating migration for updating table '{$tableName}' ...", Console::FG_YELLOW);

            if ($migrationsGenerated === 0) {
                $migrationClassName = 'm' . gmdate('ymd_His') . '_update_table_' . $tableName;
            } else {
                $migrationClassName = sprintf(
                    "m%s_%0{$counterSize}d_update_table_%s",
                    gmdate('ymd_His'),
                    $migrationsGenerated + 1,
                    $tableName
                );
            }

            try {
                $this->generateMigrationWithBlueprint($blueprint, $migrationClassName);
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $migrationsGenerated++;

            $this->stdout("\n");
        }

        if ($migrationsGenerated) {
            $this->stdout(
                " Generated $migrationsGenerated file" . ($migrationsGenerated > 1 ? 's' : '') . "\n",
                Console::FG_YELLOW
            );
            $this->stdout(" (!) Remember to verify files before applying migration.\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout(" No files generated.\n\n", Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }

    /**
     * Prepares path directory.
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
     * @param string $path
     * @param mixed $content
     * @throws RuntimeException
     */
    private function generateFile(string $path, $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('Migration file can not be saved!');
        }
    }

    /**
     * @param string $migrationClassName
     * @throws DbException
     * @throws InvalidConfigException
     */
    private function fixHistory(string $migrationClassName): void
    {
        if ($this->fixHistory) {
            $this->getHistoryManager()->addHistory($migrationClassName, $this->workingNamespace);
        }
    }

    /**
     * @param array<ForeignKeyInterface> $postponedForeignKeys
     * @param string $migrationClassName
     * @throws DbException
     * @throws InvalidConfigException
     */
    private function generateMigrationForForeignKeys(array $postponedForeignKeys, string $migrationClassName): void
    {
        $this->stdout(' > Generating migration for creating foreign keys ...', Console::FG_YELLOW);

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

        $this->generateFile($file, $migration);

        $this->stdout("DONE!\n", Console::FG_GREEN);
        $this->stdout(" > Saved as '{$file}'\n");

        $this->fixHistory($migrationClassName);
    }

    /**
     * @param BlueprintInterface $blueprint
     * @param string $migrationClassName
     * @throws DbException
     * @throws InvalidConfigException
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

        $this->generateFile($file, $migration);

        $this->stdout("DONE!\n", Console::FG_GREEN);
        $this->stdout(" > Saved as '{$file}'\n");

        $this->fixHistory($migrationClassName);
    }

    /**
     * @param string $tableName
     * @param string $migrationClassName
     * @param array<string> $referencesToPostpone
     * @throws DbException
     * @throws InvalidConfigException
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

        $this->generateFile($file, $migration);

        $this->stdout("DONE!\n", Console::FG_GREEN);
        $this->stdout(" > Saved as '{$file}'\n");

        $this->fixHistory($migrationClassName);
    }

    /**
     * @param string $inputTables
     * @return array<string>
     */
    private function prepareTableNames($inputTables): array
    {
        if (strpos($inputTables, ',') !== false) {
            $tablesList = explode(',', $inputTables);
        } else {
            $tablesList = [$inputTables];
        }

        $tables = [];

        if (in_array('*', $tablesList, true)) {
            $tables = $this->findMatchingTables();
        } else {
            foreach ($tablesList as $inputTable) {
                if (strpos($inputTable, '*') === false) {
                    $tables[] = $inputTable;
                } else {
                    $matchedTables = $this->findMatchingTables($inputTable);
                    foreach ($matchedTables as $matchedTable) {
                        $tables[] = $matchedTable;
                    }
                }
            }
        }

        return $tables;
    }

    /**
     * @param string|null $pattern
     * @return array<string>
     */
    private function findMatchingTables(string $pattern = null): array
    {
        /** @var Connection $db */
        $db = $this->db;
        $allTables = $db->schema->getTableNames();
        if (count($allTables) === 0) {
            return [];
        }

        $filteredTables = [];
        $excludedTables = array_merge(
            [$db->schema->getRawTableName($this->migrationTable)],
            $this->excludeTables
        );

        foreach ($allTables as $table) {
            if (in_array($table, $excludedTables, true) === false) {
                if ($pattern && preg_match('/' . str_replace('*', '(.+)', $pattern) . '/', $table) === 0) {
                    continue;
                }
                $filteredTables[] = $table;
            }
        }

        return $filteredTables;
    }
}
