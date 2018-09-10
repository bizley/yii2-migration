<?php

namespace bizley\migration\controllers;

use bizley\migration\Generator;
use bizley\migration\Updater;
use Closure;
use Exception;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\console\Controller;
use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Migration creator and updater.
 * Generates migration file based on the existing database table and previous migrations.
 *
 * @author Paweł Bizley Brzozowski
 * @version 2.3.2
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 */
class MigrationController extends Controller
{
    protected $version = '2.3.2';

    /**
     * @var string Default command action.
     */
    public $defaultAction = 'list';

    /**
     * @var string Directory storing the migration classes. This can be either a path alias or a directory.
     * Alias -p
     */
    public $migrationPath = '@app/migrations';

    /**
     * @var string Full migration namespace. If given it's used instead of $migrationPath. Note that backslash (\)
     * symbol is usually considered a special character in the shell, so you need to escape it properly to avoid shell
     * errors or incorrect behavior.
     * Migration namespace should be resolvable as a path alias if prefixed with @, e.g. if you specify the namespace
     * 'app\migrations', the code Yii::getAlias('@app/migrations') should be able to return the file path to
     * the directory this namespace refers to.
     * Namespaced migrations have been added in Yii 2.0.10.
     * Alias -n
     * @since 1.1
     */
    public $migrationNamespace;

    /**
     * @var string Template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     * Alias -F
     */
    public $templateFile = '@bizley/migration/views/create_migration.php';

    /**
     * @var string Template file for generating updating migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     * Alias -U
     */
    public $templateFileUpdate = '@bizley/migration/views/update_migration.php';

    /**
     * @var bool|string|int Whether the table names generated should consider the $tablePrefix setting of the DB
     * connection. For example, if the table name is 'post' the generator will return '{{%post}}'.
     * Alias -P
     */
    public $useTablePrefix = 1;

    /**
     * @var Connection|array|string DB connection object or the application component ID of the DB connection to use
     * when creating migrations.
     * Starting from Yii 2.0.3, this can also be a configuration array for creating the object.
     */
    public $db = 'db';

    /**
     * @var string Name of the table for keeping applied migration information.
     * The same as in yii\console\controllers\MigrateController::$migrationTable.
     * Alias -t
     * @since 2.0
     */
    public $migrationTable = '{{%migration}}';

    /**
     * @var bool|string|int Whether to only display changes instead of create update migration.
     * Alias -s
     * @since 2.0
     */
    public $showOnly = 0;

    /**
     * @var bool|string|int Whether to use general column schema instead of database specific.
     * Alias -g
     * Since 2.3.0 this property is 1 by default.
     * @since 2.0
     */
    public $generalSchema = 1;

    /**
     * @var bool|string|int Whether to add generated migration to migration history.
     * Alias -h
     * @since 2.0
     */
    public $fixHistory = 0;

    /**
     * @var array List of migrations from the history table that should be skipped during the update process.
     * Here you can place migrations containing actions that can not be covered by extractor.
     * @since 2.1.1
     */
    public $skipMigrations = [];

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $createOptions = ['migrationPath', 'migrationNamespace', 'db', 'generalSchema', 'templateFile', 'useTablePrefix', 'fixHistory', 'migrationTable'];
        $updateOptions = ['showOnly', 'templateFileUpdate', 'skipMigrations'];
        switch ($actionID) {
            case 'create':
            case 'create-all':
                return array_merge($options, $createOptions);
            case 'update':
            case 'update-all':
                return array_merge($options, $createOptions, $updateOptions);
        }
        return $options;
    }

    /**
     * @inheritdoc
     * @since 2.0
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'p' => 'migrationPath',
            'n' => 'migrationNamespace',
            't' => 'migrationTable',
            'g' => 'generalSchema',
            'F' => 'templateFile',
            'U' => 'templateFileUpdate',
            'P' => 'useTablePrefix',
            'h' => 'fixHistory',
            's' => 'showOnly',
        ]);
    }

    /**
     * Makes sure boolean properties are boolean.
     */
    public function init()
    {
        parent::init();
        $booleanProperties = ['useTablePrefix', 'showOnly', 'generalSchema', 'fixHistory'];
        foreach ($booleanProperties as $property) {
            if ($this->$property !== true) {
                if ($this->$property === 'true' || $this->$property === 1) {
                    $this->$property = true;
                }
                $this->$property = (bool)$this->$property;
            }
        }
    }

    protected $workingPath;

    /**
     * This method is invoked right before an action is to be executed (after all possible filters).
     * It checks the existence of the migrationPath and makes sure DB connection is prepared.
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
     * @throws InvalidConfigException
     * @throws InvalidParamException
     * @throws \yii\base\Exception
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if (!$this->showOnly && in_array($action->id, ['create', 'create-all', 'update', 'update-all'], true)) {
                if ($this->migrationPath !== null) {
                    $this->migrationPath = $this->preparePathDirectory($this->migrationPath);
                }
                if ($this->migrationNamespace !== null) {
                    $this->migrationNamespace = FileHelper::normalizePath($this->migrationNamespace, '\\');
                    $this->workingPath = $this->preparePathDirectory(FileHelper::normalizePath('@' . $this->migrationNamespace, '/'));
                } else {
                    $this->workingPath = $this->migrationPath;
                }
            }
            $this->db = Instance::ensure($this->db, Connection::className());
            $this->stdout("Yii 2 Migration Generator Tool v{$this->version}\n\n", Console::FG_CYAN);
            return true;
        }
        return false;
    }

    /**
     * Prepares path directory.
     * @param string $path
     * @return string
     * @since 1.1
     * @throws InvalidParamException
     * @throws \yii\base\Exception
     */
    public function preparePathDirectory($path)
    {
        $translatedPath = Yii::getAlias($path);
        if (!is_dir($translatedPath)) {
            FileHelper::createDirectory($translatedPath);
        }
        return $translatedPath;
    }

    /**
     * Creates the migration history table.
     * @throws \yii\db\Exception
     * @since 2.0
     */
    protected function createMigrationHistoryTable()
    {
        $tableName = $this->db->schema->getRawTableName($this->migrationTable);
        $this->stdout(" > Creating migration history table '{$tableName}' ...", Console::FG_YELLOW);
        $this->db->createCommand()->createTable($this->migrationTable, [
            'version' => 'varchar(180) NOT NULL PRIMARY KEY',
            'apply_time' => 'integer',
        ])->execute();
        $this->db->createCommand()->insert($this->migrationTable, [
            'version' => MigrateController::BASE_MIGRATION,
            'apply_time' => time(),
        ])->execute();
        $this->stdout("DONE.\n", Console::FG_GREEN);
    }

    /**
     * Adds migration history entry.
     * @param string $version
     * @param string $namespace
     * @throws \yii\db\Exception
     * @since 2.0
     */
    protected function addMigrationHistory($version, $namespace = null)
    {
        $this->stdout(' > Adding migration history entry ...', Console::FG_YELLOW);
        $command = $this->db->createCommand();
        $command->insert($this->migrationTable, [
            'version' => ($namespace ? $namespace . '\\' : '') . $version,
            'apply_time' => time(),
        ])->execute();
        $this->stdout("DONE.\n", Console::FG_GREEN);
    }

    /**
     * Lists all tables in the database.
     * @return int
     * @since 2.1
     */
    public function actionList()
    {
        $tables = $this->db->schema->getTableNames();
        if (!$tables) {
            $this->stdout(" > Your database does not contain any tables yet.\n");
        } else {
            $this->stdout(' > Your database contains ' . count($tables) . " tables:\n");
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
        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * @param string $path
     * @param string $content
     * @return bool|int
     * @since 2.3.2
     */
    public function generateFile($path, $content)
    {
        return file_put_contents($path, $content);
    }

    /**
     * Creates new migration for the given tables.
     * @param string $table Table names separated by commas.
     * @return int
     * @throws InvalidParamException
     * @throws \yii\db\Exception
     */
    public function actionCreate($table)
    {
        $tables = [$table];
        if (strpos($table, ',') !== false) {
            $tables = explode(',', $table);
        }

        $migrationsGenerated = 0;
        foreach ($tables as $name) {
            $this->stdout(" > Generating create migration for table '{$name}' ...", Console::FG_YELLOW);

            $className = 'm' . gmdate('ymd_His') . '_create_table_' . $name;
            $file = Yii::getAlias($this->workingPath . DIRECTORY_SEPARATOR . $className . '.php');

            $generator = new Generator([
                'db' => $this->db,
                'view' => $this->view,
                'useTablePrefix' => $this->useTablePrefix,
                'templateFile' => $this->templateFile,
                'tableName' => $name,
                'className' => $className,
                'namespace' => $this->migrationNamespace,
                'generalSchema' => $this->generalSchema,
            ]);

            if ($generator->tableSchema === null) {
                $this->stdout("ERROR!\n > Table '{$name}' does not exist!\n\n", Console::FG_RED);
                return Controller::EXIT_CODE_ERROR;
            }

            if ($this->generateFile($file, $generator->generateMigration()) === false) {
                $this->stdout("ERROR!\n > Migration file for table '{$name}' can not be generated!\n\n", Console::FG_RED);
                return Controller::EXIT_CODE_ERROR;
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
        }

        if ($migrationsGenerated) {
            $this->stdout(" Generated $migrationsGenerated file(s).\n", Console::FG_YELLOW);
            $this->stdout(" (!) Remember to verify files before applying migration.\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout(" No files generated.\n\n", Console::FG_YELLOW);
        }
        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * Creates new migrations for every table in database.
     * Since 2.3.0 migration history table is skipped.
     * @return int
     * @throws InvalidParamException
     * @throws \yii\db\Exception
     * @since 2.1
     */
    public function actionCreateAll()
    {
        $tables = $this->removeMigrationTable($this->db->schema->getTableNames());

        if (!$tables) {
            $this->stdout(' > Your database does not contain any tables yet.', Console::FG_YELLOW);
            return Controller::EXIT_CODE_NORMAL;
        }

        if ($this->confirm(' > Are you sure you want to generate ' . count($tables) . ' migrations?', false)) {
            return $this->actionCreate(implode(',', $tables));
        }

        $this->stdout(" Operation cancelled by user.\n\n", Console::FG_YELLOW);
        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * Creates new update migration for the given tables.
     * @param string $table Table names separated by commas.
     * @return int
     * @throws InvalidParamException
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     * @since 2.0
     */
    public function actionUpdate($table)
    {
        $tables = [$table];
        if (strpos($table, ',') !== false) {
            $tables = explode(',', $table);
        }

        $migrationsGenerated = 0;
        foreach ($tables as $name) {
            $this->stdout(" > Generating update migration for table '{$name}' ...", Console::FG_YELLOW);

            $className = 'm' . gmdate('ymd_His') . '_update_table_' . $name;
            $file = Yii::getAlias($this->workingPath . DIRECTORY_SEPARATOR . $className . '.php');

            $updater = new Updater([
                'db' => $this->db,
                'view' => $this->view,
                'useTablePrefix' => $this->useTablePrefix,
                'templateFile' => $this->templateFile,
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

            if ($updater->tableSchema === null) {
                $this->stdout("ERROR!\n > Table '{$name}' does not exist!\n\n", Console::FG_RED);
                return Controller::EXIT_CODE_ERROR;
            }

            if (!$updater->isUpdateRequired()) {
                $this->stdout("UPDATE NOT REQUIRED.\n\n", Console::FG_YELLOW);
                continue;
            }

            if (!$this->showOnly) {
                if ($this->generateFile($file, $updater->generateMigration()) === false) {
                    $this->stdout("ERROR!\n > Migration file for table '{$name}' can not be generated!\n\n", Console::FG_RED);
                    return Controller::EXIT_CODE_ERROR;
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

        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * Creates new update migrations for every table in database.
     * Since 2.3.0 migration history table is skipped.
     * @return int
     * @throws InvalidParamException
     * @throws \yii\base\ErrorException
     * @throws \yii\db\Exception
     * @since 2.1
     */
    public function actionUpdateAll()
    {
        $tables = $this->removeMigrationTable($this->db->schema->getTableNames());

        if (!$tables) {
            $this->stdout(' > Your database does not contain any tables yet.', Console::FG_YELLOW);
            return Controller::EXIT_CODE_NORMAL;
        }

        if ($this->confirm(' > Are you sure you want to potentially generate ' . count($tables) . ' migrations?', false)) {
            return $this->actionUpdate(implode(',', $tables));
        }

        $this->stdout(" Operation cancelled by user.\n\n", Console::FG_YELLOW);
        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * Removes migration history table name from the tables list.
     * @param array $tables
     * @return array
     * @since 2.3.0
     */
    public function removeMigrationTable($tables)
    {
        if (!$tables) {
            return [];
        }
        $filteredTables = [];
        foreach ($tables as $table) {
            if ($this->db->schema->getRawTableName($this->migrationTable) === $table) {
                continue;
            }
            $filteredTables[] = $table;
        }
        return $filteredTables;
    }
}
