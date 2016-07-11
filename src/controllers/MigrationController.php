<?php

namespace bizley\migration\controllers;

use bizley\migration\components\Generator;
use Yii;
use yii\base\Action;
use yii\console\Controller;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\FileHelper;

/**
 * Migration creator.
 * Tested with MySQL DB.
 * Doesn't generate indexes.
 * Foreign keys ON UPDATE and ON DELETE are set to null.
 * 
 * @author Paweł Bizley Brzozowski
 * @version 1.0
 * @license Apache 2.0
 * https://github.com/bizley/yii2-migration
 * 
 */
class MigrationController extends Controller
{
    /**
     * @var string Default command action.
     */
    public $defaultAction = 'create';
    
    /**
     * @var string Directory storing the migration classes. This can be either
     * a path alias or a directory.
     */
    public $migrationPath = '@app/migrations';
    
    /**
     * @var string Template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php")
     * or a file path.
     */
    public $templateFile = '@vendor/bizley/migration/src/views/migration.php';
    
    /**
     * @var boolean Whether the table names generated should consider
     * the `tablePrefix` setting of the DB connection. For example, if the table
     * name is `post` the generator wil return `{{%post}}`.
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
            ['migrationPath', 'templateFile', 'useTablePrefix', 'db']
        );
    }
    
    /**
     * This method is invoked right before an action is to be executed (after 
     * all possible filters).
     * It checks the existence of the migrationPath and makes sure 
     * DB connection is prepared.
     * @param Action $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $path = Yii::getAlias($this->migrationPath);
            if (!is_dir($path)) {
                FileHelper::createDirectory($path);
            }
            $this->migrationPath = $path;
            $this->db = Instance::ensure($this->db, Connection::className());
            $this->stdout("Yii 2 Migration Generator Tool\n\n");
            return true;
        }
        return false;
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
        foreach ($tables as $name) {
            $this->stdout(" > Creating migration for table $name ...");

            $className = 'm' . gmdate('ymd_His') . '_create_table_' . $name;
            $file = $this->migrationPath . DIRECTORY_SEPARATOR . $className . '.php';

            $generator = new Generator([
                'db'             => $this->db, 
                'view'           => $this->view,
                'useTablePrefix' => $this->useTablePrefix,
                'templateFile'   => $this->templateFile,
                'tableName'      => $name,
                'className'      => $className,
            ]);
            file_put_contents($file, $generator->generateMigration());

            $this->stdout("DONE!\n");
            $this->stdout(" > Saved as " . Yii::getAlias($file) . "\n\n");
        }
        
        $this->stdout("(!) Remember to verify generated files before applying migration.\n\n");
    }
}
