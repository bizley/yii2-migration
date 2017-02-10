<?php

namespace bizley\migration\controllers;

use bizley\migration\Generator;
use Exception;
use Yii;
use yii\base\Action;
use yii\console\Controller;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

/**
 * Migration creator.
 * Generates migration file based on the existing database table.
 * 
 * Tested with MySQL DB.
 * Doesn't generate indexes.
 * Foreign keys' ON UPDATE and ON DELETE are set to null.
 * 
 * @author Paweł Bizley Brzozowski
 * @version 1.1
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 */
class MigrationController extends Controller
{
    /**
     * @var string Default command action.
     */
    public $defaultAction = 'create';

    /**
     * @var string Default user decision in case the file to be generated 
     * already exists. Console asks if file should be overwritten. 
     * Available options are:
     * 'y' = yes (user is asked before every existing file, 'y' is default),
     * 'n' = no (user is asked before every existing file, 'n' is default),
     * 'a' = overwrite all (user is not asked, all files are overwritten),
     * 's' = skip all (user is not asked, no files are overwritten).
     * @since 1.1
     */
    public $defaultDecision = 'n';

    /**
     * @var string Directory storing the migration classes. This can be either
     * a path alias or a directory.
     */
    public $migrationPath = '@app/migrations';
    
    /**
     * @var string Full migration namespace. If given it's used instead of 
     * $migrationPath. Note that backslash (\) symbol is usually considered 
     * a special character in the shell, so you need to escape it properly to 
     * avoid shell errors or incorrect behavior.
     * Migration namespace should be resolvable as a path alias if prefixed 
     * with @, e.g. if you specify the namespace 'app\migrations', the code 
     * Yii::getAlias('@app/migrations') should be able to return the file path 
     * to the directory this namespace refers to.
     * Namespaced migrations have been added in Yii 2.0.10.
     * @since 1.1
     */
    public $migrationNamespace;

    /**
     * @var string Template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php")
     * or a file path.
     */
    public $templateFile = '@vendor/bizley/migration/src/views/migration.php';

    /**
     * @var bool|string|int Whether the table names generated should consider
     * the $tablePrefix setting of the DB connection. For example, if the table
     * name is 'post' the generator will return '{{%post}}'.
     */
    public $useTablePrefix = true;

    /**
     * @var Connection|array|string the DB connection object or the application 
     * component ID of the DB connection to use when creating migrations. 
     * Starting from Yii 2.0.3, this can also be a configuration array
     * for creating the object.
     */
    public $db = 'db';

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['defaultDecision', 'migrationPath', 'migrationNamespace', 'templateFile', 'useTablePrefix', 'db']
        );
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->useTablePrefix !== true) {
            if ($this->useTablePrefix === 'true' || $this->useTablePrefix === 1) {
                $this->useTablePrefix = true;
            }
            $this->useTablePrefix = (bool)$this->useTablePrefix;
        }
    }

    /**
     * This method is invoked right before an action is to be executed (after 
     * all possible filters).
     * It checks the existence of the migrationPath and makes sure 
     * DB connection is prepared.
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to be executed.
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
            $this->stdout("Yii 2 Migration Generator Tool v1.1\n\n");
            return true;
        }
        return false;
    }

    /**
     * Prepares path directory.
     * @param string $path
     * @since 1.1
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
     */
    public function prepareNamespacedDirectory()
    {
        $this->preparePathDirectory(FileHelper::normalizePath('@' . $this->migrationNamespace, '/'));
    }

    /**
     * Returns name of the migration class based on the selected type.
     * @param string $tableName
     * @return string
     * @since 1.1
     */
    public function generateClassName($tableName)
    {
        if (empty($this->migrationNamespace)) {
            return 'm' . gmdate('ymd_His') . '_create_table_' . $tableName;
        }
        return 'Create' . Inflector::camelize($tableName) . 'Table';
    }

    /**
     * Creates new migration for a given tables.
     * @param string $table Table names separated by commas.
     */
    public function actionCreate($table)
    {
        $tables = [$table];
        if (strpos($table, ',') !== false) {
            $tables = explode(',', $table);
        }

        $massDecision = null;
        if (in_array($this->defaultDecision, ['a', 's'])) {
            $massDecision = $this->defaultDecision;
        }

        $migrationsGenerated = 0;
        foreach ($tables as $name) {
            try {
                $this->stdout(" > Creating migration for table '{$name}' ...");

                $className = $this->generateClassName($name);
                $file = $this->migrationPath . DIRECTORY_SEPARATOR . $className . '.php';

                if (file_exists($file)) {
                    if ($massDecision === 'a') {
                        $this->stdout("OVERWRITE ALL\n");
                        $prompt = 'y';
                    } elseif ($massDecision === 's') {
                        $this->stdout("SKIP ALL\n");
                        $prompt = 'n';
                    } else {
                        $message = "\n > (!) File " . Yii::getAlias($file) . " already exists - overwrite? ([y]es / [n]o";
                        if (count($tables) > 1) {
                            $message .= " / overwrite [a]ll / [s]kip all";
                        }
                        $message .= ")";
                        $prompt = $this->prompt($message, [
                            'required' => false, 
                            'default' => in_array($this->defaultDecision, ['y', 'n']) ? $this->defaultDecision : 'n', 
                            'validator' => function ($input, &$error) {
                                if (!in_array(strtolower($input), ['y', 'n', 'a', 's'])) {
                                    $error = 'Available options are: y = yes, n = no (default), a = overwrite all, s = skip all';
                                    return false;
                                }
                                return true;
                            }
                        ]);
                    }
                    switch (strtolower($prompt)) {
                        case 'a':
                            $massDecision = 'a';
                            break;
                        case 's':
                            $massDecision = 's';
                            // no break
                        case 'n':
                            $this->stdout(" > Migration for table $name not generated!\n\n");
                            continue 2;
                    }
                    $this->stdout(" > Overwriting migration file ...");
                }

                $generator = new Generator([
                    'db' => $this->db, 
                    'view' => $this->view,
                    'useTablePrefix' => $this->useTablePrefix,
                    'templateFile' => $this->templateFile,
                    'tableName' => $name,
                    'className' => $className,
                    'namespace' => $this->migrationNamespace,
                ]);
                file_put_contents($file, $generator->generateMigration());
                $migrationsGenerated++;
                $this->stdout("DONE!\n");
                $this->stdout(" > Saved as " . Yii::getAlias($file) . "\n\n");
            } catch (Exception $exc) {
                $this->stdout("ERROR!\n");
                $this->stdout(" > " . $exc->getMessage() . "\n\n");
            }
        }

        if ($migrationsGenerated) {
            $this->stdout("Generated $migrationsGenerated file(s).\n");
            $this->stdout("(!) Remember to verify files before applying migration.\n\n");
        } else {
            $this->stdout("No files generated.\n\n");
        }
    }
}
