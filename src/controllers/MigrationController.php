<?php

declare(strict_types=1);

namespace bizley\migration\controllers;

use bizley\migration\Schema;
use bizley\migration\TableMissingException;
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
use function sprintf;
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
class MigrationController extends BaseMigrationController
{
    /** @var string */
    private $version = '4.0.0';

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

    /** @var bool Whether to only display changes instead of generating update migration. */
    public $onlyShow = false;

    /** @var bool Whether to add generated migration to migration history. */
    public $fixHistory = false;

    /**
     * @var array List of migrations from the history table that should be skipped during the update process.
     * Here you can place migrations containing actions that can not be covered by extractor.
     */
    public $skipMigrations = [];

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
    private function preparePathDirectory(string $path): string
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
    private function removeExcludedTables(array $tables): array
    {
        if (count($tables) === 0) {
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

        $tablesCount = count($tables);
        if ($tablesCount === 0) {
            $this->stdout(" > Your database does not contain any tables yet.\n");
        } else {
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

    /**
     * Creates new migration for the given tables.
     * @param string $inputTable Table name or names separated by commas.
     * @return int
     * @throws InvalidConfigException
     */
    public function actionCreate(string $inputTable): int
    {
        if (strpos($inputTable, ',') !== false) {
            $inputTables = explode(',', $inputTable);
        } else {
            $inputTables = [$inputTable];
        }

        $countTables = count($inputTables);
        $referencesToPostpone = [];
        $tables = $inputTables;
        if ($countTables > 1) {
            $this->getArranger()->arrangeMigrations($inputTables);
            $tables = $this->getArranger()->getTablesInOrder();
            $referencesToPostpone = $this->getArranger()->getReferencesToPostpone();

            if (count($referencesToPostpone) && Schema::isSQLite($this->db->schema)) {
                $this->stdout(
                    "ERROR!\n > Generating migrations for provided tables in batch is not possible "
                    . "because 'ADD FOREIGN KEY' is not supported by SQLite!\n",
                    Console::FG_RED
                );

                return ExitCode::DATAERR;
            }
        }

        $postponedForeignKeys = [];

        $counterSize = strlen((string) $countTables) + 1;
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
            $file = $this->workingPath . DIRECTORY_SEPARATOR . $migrationClassName . '.php';

            try {
                $migration = $this->getGenerator()->generateForTable(
                    $tableName,
                    $migrationClassName,
                    $referencesToPostpone,
                    $this->generalSchema,
                    $this->workingNamespace
                );
            } catch (TableMissingException $exception) {
                $this->stdout("ERROR!\n > Table '{$tableName}' does not exist!\n\n", Console::FG_RED);
                return ExitCode::DATAERR;
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            if ($this->generateFile($file, $migration) === false) {
                $this->stdout(
                    "ERROR!\n > Migration file for table '{$tableName}' can not be generated!\n\n",
                    Console::FG_RED
                );

                return ExitCode::SOFTWARE;
            }

            $migrationsGenerated++;

            $this->stdout("DONE!\n", Console::FG_GREEN);
            $this->stdout(" > Saved as '{$file}'\n");

            if ($this->fixHistory) {
                try {
                    $this->getHistoryManager()->addHistory($migrationClassName, $this->workingNamespace);
                } catch (DbException $exception) {
                    $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
            }

            $this->stdout("\n");

            $suppressedForeignKeys = $this->getGenerator()->getSuppressedForeignKeys();
            foreach ($suppressedForeignKeys as $suppressedKey) {
                $postponedForeignKeys[] = $suppressedKey;
            }
        }

        if ($postponedForeignKeys) {
            $this->stdout(' > Generating migration for creating foreign keys ...', Console::FG_YELLOW);

            $migrationClassName = sprintf(
                "m%s_%0{$counterSize}d_create_foreign_keys",
                gmdate('ymd_His'),
                ++$migrationsGenerated
            );
            $file = $this->workingPath . DIRECTORY_SEPARATOR . $migrationClassName . '.php';

            try {
                $migration = $this->getGenerator()->generateForForeignKeys(
                    $postponedForeignKeys,
                    $migrationClassName,
                    $this->workingNamespace
                );
            } catch (Throwable $exception) {
                $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            if ($this->generateFile($file, $migration) === false) {
                $this->stdout(
                    "ERROR!\n > Migration file for foreign keys can not be generated!\n\n",
                    Console::FG_RED
                );

                return ExitCode::SOFTWARE;
            }

            $this->stdout("DONE!\n", Console::FG_GREEN);
            $this->stdout(" > Saved as '{$file}'\n");

            if ($this->fixHistory) {
                try {
                    $this->getHistoryManager()->addHistory($migrationClassName, $this->workingNamespace);
                } catch (DbException $exception) {
                    $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
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
     * Creates new migrations for every table in database.
     * @return int
     * @throws InvalidConfigException
     */
    public function actionCreateAll(): int
    {
        $tables = $this->removeExcludedTables($this->db->schema->getTableNames());

        $tablesCount = count($tables);
        if ($tablesCount === 0) {
            $this->stdout(' > Your database does not contain any tables yet.', Console::FG_YELLOW);

            return ExitCode::OK;
        }

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
     * @param string $inputTable
     * @return int
     * @throws InvalidConfigException
     */
    public function actionUpdate(string $inputTable): int
    {
        if (strpos($inputTable, ',') !== false) {
            $inputTables = explode(',', $inputTable);
        } else {
            $inputTables = [$inputTable];
        }

        $migrationsGenerated = 0;
        foreach ($inputTables as $tableName) {
            $this->stdout(" > Generating migration for updating table '{$tableName}' ...", Console::FG_YELLOW);

            $migrationClassName = 'm' . gmdate('ymd_His') . '_update_table_' . $tableName;
            $file = $this->workingPath . DIRECTORY_SEPARATOR . $migrationClassName . '.php';

            try {
                if (
                    $this->getUpdater()->isUpdateRequired(
                        $tableName,
                        $this->onlyShow,
                        $this->skipMigrations,
                        $this->migrationPath
                    ) === false
                ) {
                    $this->stdout("UPDATE NOT REQUIRED.\n\n", Console::FG_YELLOW);

                    continue;
                }

                $migration = $this->getUpdater()->generateForPendingTable(
                    $migrationClassName,
                    $this->generalSchema,
                    $this->workingNamespace
                );
            } catch (TableMissingException $exception) {
                $this->stdout("ERROR!\n > Table '{$tableName}' does not exist!\n\n", Console::FG_RED);

                return ExitCode::DATAERR;
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

            if ($this->onlyShow === false) {
                if ($this->generateFile($file, $migration) === false) {
                    $this->stdout(
                        "ERROR!\n > Migration file for table '{$tableName}' can not be generated!\n\n",
                        Console::FG_RED
                    );

                    return ExitCode::SOFTWARE;
                }

                $migrationsGenerated++;

                $this->stdout("DONE!\n", Console::FG_GREEN);
                $this->stdout(" > Saved as '{$file}'\n");

                if ($this->fixHistory) {
                    try {
                        $this->getHistoryManager()->addHistory($migrationClassName, $this->workingNamespace);
                    } catch (DbException $exception) {
                        $this->stdout("ERROR!\n > {$exception->getMessage()}\n\n", Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                }
            }

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
     * Creates new update migrations for every table in database.
     * @return int
     * @throws InvalidConfigException
     */
    public function actionUpdateAll(): int
    {
        $tables = $this->removeExcludedTables($this->db->schema->getTableNames());

        $tablesCount = count($tables);
        if ($tablesCount === 0) {
            $this->stdout(' > Your database does not contain any tables yet.', Console::FG_YELLOW);

            return ExitCode::OK;
        }

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
}
