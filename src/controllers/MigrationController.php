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
use yii\helpers\Inflector;

/**
 * Migration creator and updater.
 * Generates migration file based on the existing database table and previous migrations.
 *
 * Yii 2 does not keep information about database indexes (except unique ones) and foreign keys' ON UPDATE and ON DELETE
 * actions so there is no support for them in this generator.
 *
 * @author Paweł Bizley Brzozowski
 * @version 2.0
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 */
class MigrationController extends Controller
{
    /**
     * @var string Default command action.
     */
    public $defaultAction = 'list';

    /**
     * @var string Default user decision in case the file to be generated already exists. Console asks if file should
     * be overwritten.
     * Available options are:
     * 'y' = yes (user is asked before every existing file, 'y' is default),
     * 'a' = no, append next number (user is asked before every existing file, 'a' is default),
     * 'n' = no, skip file (user is asked before every existing file, 'n' is default),
     * 'o' = overwrite all (user is not asked, all files are overwritten),
     * 'p' = append all with next number (user is not asked, all files are appended),
     * 's' = skip all (user is not asked, no files are overwritten).
     * @since 1.1
     */
    public $defaultDecision = 'n';

    /**
     * @var string Directory storing the migration classes. This can be either a path alias or a directory.
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
     * @since 1.1
     */
    public $migrationNamespace;

    /**
     * @var string Template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     */
    public $templateFile = '@vendor/bizley/migration/src/views/create_migration.php';

    /**
     * @var string Template file for generating updating migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php") or a file path.
     */
    public $templateFileUpdate = '@vendor/bizley/migration/src/views/update_migration.php';

    /**
     * @var bool|string|int Whether the table names generated should consider the $tablePrefix setting of the DB
     * connection. For example, if the table name is 'post' the generator will return '{{%post}}'.
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
     * @since 2.0
     */
    public $migrationTable = '{{%migration}}';

    /**
     * @var array List of namespaces containing the migration classes.
     * The same as in yii\console\controllers\BaseMigrateController::$migrationNamespaces.
     * @since 2.0
     */
    public $migrationNamespaces = [];

    /**
     * @var bool|string|int Whether to only display changes instead of create update migration.
     * @since 2.0
     */
    public $showOnly = 0;

    /**
     * @var bool|string|int Whether to use general column schema instead of database specific.
     * @since 2.0
     */
    public $generalSchema = 0;

    /**
     * @var bool|string|int Whether to add generated migration to migration history.
     * @since 2.0
     */
    public $fixHistory = 0;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['defaultDecision', 'migrationPath', 'migrationNamespace', 'db', 'generalSchema', 'templateFile',
                'useTablePrefix', 'fixHistory', 'migrationTable'],
            $actionID === 'update' ? ['migrationNamespaces', 'showOnly', 'templateFileUpdate'] : []
        );
    }

    /**
     * @inheritdoc
     * @since 2.0
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'd' => 'defaultDecision',
            'p' => 'migrationPath',
            'n' => 'migrationNamespace',
            'N' => 'migrationNamespaces',
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
            if (!empty($this->migrationNamespace)) {
                $this->prepareNamespacedDirectory();
            } else {
                $this->preparePathDirectory($this->migrationPath);
            }
            $this->db = Instance::ensure($this->db, Connection::className());
            $this->stdout("Yii 2 Migration Generator Tool v2.0\n\n", Console::FG_CYAN);
            return true;
        }
        return false;
    }

    /**
     * Prepares path directory.
     * @param string $path
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
        $this->migrationPath = $translatedPath;
    }

    /**
     * Prepares namespaced directory.
     * @since 1.1
     * @throws \yii\base\Exception
     * @throws InvalidParamException
     */
    public function prepareNamespacedDirectory()
    {
        $this->preparePathDirectory(FileHelper::normalizePath('@' . $this->migrationNamespace, '/'));
    }

    /**
     * Returns name of the migration class based on the selected type.
     * @param string $tableName
     * @param string $action
     * @return string
     * @since 1.1
     */
    public function generateClassName($tableName, $action = 'create')
    {
        if (empty($this->migrationNamespace)) {
            return 'm' . gmdate('ymd_His') . '_' . $action . '_table_' . $tableName;
        }
        return Inflector::camelize($action . '_' . $tableName . '_table');
    }

    /**
     * Returns file name and path appended with next available number.
     * @param string $className old file name
     * @param string $file old file path
     * @param int $suffix next number to check
     * @return array
     * @since 2.0
     */
    public function suffixCheck($className, $file, $suffix = 2)
    {
        $newClassName = $className . $suffix;
        $newFile = substr($file, 0, -4) . $suffix . '.php';
        if (file_exists($newFile)) {
            return $this->suffixCheck($className, $file, ++$suffix);
        }
        return [$newClassName, $newFile];
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
     * @throws \yii\db\Exception
     * @since 2.0
     */
    protected function addMigrationHistory($version)
    {
        $this->stdout(' > Adding migration history entry ...', Console::FG_YELLOW);
        $command = $this->db->createCommand();
        $command->insert($this->migrationTable, [
            'version' => $version,
            'apply_time' => time(),
        ])->execute();
        $this->stdout("DONE.\n", Console::FG_GREEN);
    }

    /**
     * Handles the execution of the given action with mass decision taken into account.
     * @param string $type
     * @param string $table
     * @param Closure $actionMethod
     * @since 2.0
     */
    public function massDecision($type, $table, $actionMethod)
    {
        $tables = [$table];
        if (strpos($table, ',') !== false) {
            $tables = explode(',', $table);
        }

        $massDecision = null;
        if (in_array($this->defaultDecision, ['o', 'p', 's'], true)) {
            $massDecision = $this->defaultDecision;
        }

        $migrationsGenerated = 0;
        foreach ($tables as $name) {
            try {
                $this->stdout(" > Generating $type migration for table '{$name}' ...", Console::FG_YELLOW);

                $className = $this->generateClassName($name, $type);
                $file = Yii::getAlias($this->migrationPath . DIRECTORY_SEPARATOR . $className . '.php');

                if (file_exists($file)) {
                    if ($massDecision === 'o') {
                        $this->stdout("OVERWRITE ALL\n", Console::FG_RED);
                        $prompt = 'o';
                    } elseif ($massDecision === 's') {
                        $this->stdout("SKIP ALL\n", Console::FG_YELLOW);
                        $prompt = 'n';
                    } elseif ($massDecision === 'p') {
                        $this->stdout("APPEND ALL\n", Console::FG_GREEN);
                        $prompt = 'p';
                    } else {
                        $message = "\n > (!) File '{$file}' already exists - overwrite? ([y]es / [n]o / [a]ppend next number";
                        if (count($tables) > 1) {
                            $message .= ' / [o]verwrite all / [s]kip all / a[p]pend all';
                        }
                        $message .= ')';
                        $prompt = $this->prompt($message, [
                            'required' => false,
                            'default' => in_array($this->defaultDecision, ['y', 'n', 'a'], true) ? $this->defaultDecision : 'n',
                            'validator' => function ($input, &$error) {
                                if (!in_array(strtolower($input), ['y', 'n', 'a', 'o', 's', 'p'], true)) {
                                    $error = 'Available options are: y = yes'
                                            . ($this->defaultDecision === 'y' ? ' (default)' : '')
                                            . ', n = no'
                                            . ($this->defaultDecision === 'n' ? ' (default)' : '')
                                            . ', a = append next number'
                                            . ($this->defaultDecision === 'a' ? ' (default)' : '')
                                            . ', o = overwrite all, s = skip all, p = append all';
                                    return false;
                                }
                                return true;
                            }
                        ]);
                    }
                    switch (strtolower($prompt)) {
                        case 'o':
                            $massDecision = 'o';
                            // no break
                        case 'y':
                            $this->stdout(' > Overwriting migration file ...', Console::FG_YELLOW);
                            break;
                        case 'p':
                            $massDecision = 'p';
                            // no break
                        case 'a':
                            list($className, $file) = $this->suffixCheck($className, $file);
                            $this->stdout(' > Generating migration file with appended number ...', Console::FG_YELLOW);
                            break;
                        case 's':
                            $massDecision = 's';
                            // no break
                        case 'n':
                            $this->stdout(" > Migration for table '{$name}' not generated!\n\n", Console::FG_YELLOW);
                            continue 2;
                    }
                }

                if ($actionMethod($name, $className, $file)) {
                    $migrationsGenerated++;
                    $this->stdout("DONE!\n", Console::FG_GREEN);
                    $this->stdout(" > Saved as '{$file}'\n");

                    if ($this->fixHistory) {
                        if ($this->db->schema->getTableSchema($this->migrationTable, true) === null) {
                            $this->createMigrationHistoryTable();
                        }
                        $this->addMigrationHistory($className);
                    }

                    $this->stdout("\n");
                }
            } catch (Exception $exc) {
                $this->stdout("ERROR!\n", Console::FG_RED);
                $this->stdout(' > ' . $exc->getMessage() . "\n\n", Console::FG_RED);
            }
        }

        if ($migrationsGenerated) {
            $this->stdout("Generated $migrationsGenerated file(s).\n", Console::FG_YELLOW);
            $this->stdout("(!) Remember to verify files before applying migration.\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout("No files generated.\n\n", Console::FG_YELLOW);
        }
    }


    /**
     * Lists all Tables in the database.
     */
    public function actionList()
    {
        $tables = $this->db->schema->getTableNames();
        if (!$tables) {
            $this->stdout("Your database does not contain any tables yet.\n");
        } else {
            $this->stdout("Your database contains " . count($tables) . " tables:\n\n");
            foreach ($tables as $table) {
                $this->stdout("$table\n");
            }
        }
        $this->stdout("\n\nUse ./yii migration/create <table> to create a migration for the specific table.\n");
    }

    /**
     * Creates new migration for a given tables.
     * @param string $table Table names separated by commas.
     * @throws InvalidParamException
     */
    public function actionCreate($table)
    {
        $this->massDecision('create', $table, function ($name, $className, $file) {
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
            file_put_contents($file, $generator->generateMigration());
            return true;
        });
    }

    /**
     * Creates new update migration for a given tables.
     * @param string $table Table names separated by commas.
     * @since 2.0
     * @throws InvalidParamException
     */
    public function actionUpdate($table)
    {
        $this->massDecision('update', $table, function ($name, $className, $file) {
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
                'migrationNamespaces' => $this->migrationNamespaces,
                'showOnly' => $this->showOnly,
                'generalSchema' => $this->generalSchema,
            ]);
            if ($updater->isUpdateRequired()) {
                if (!$this->showOnly) {
                    file_put_contents($file, $updater->generateMigration());
                    return true;
                }
            } else {
                $this->stdout("UPDATE NOT REQUIRED.\n\n", Console::FG_YELLOW);
            }
            return false;
        });
    }
}
