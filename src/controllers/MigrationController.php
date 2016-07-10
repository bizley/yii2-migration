<?php

namespace bizley\migration\controllers;

use Yii;
use yii\console\Controller;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\FileHelper;

/**
 * Description of MigrationController
 *
 * @author Bizley
 */
class MigrationController extends Controller
{
    /**
     * @var string the default command action.
     */
    public $defaultAction = 'create';
    
    /**
     * @var string the directory storing the migration classes. This can be either
     * a path alias or a directory.
     */
    public $migrationPath = '@app/migrations';
    
    /**
     * @var string the template file for generating new migrations.
     * This can be either a path alias (e.g. "@app/migrations/template.php")
     * or a file path.
     */
    public $templateFile = '@vendor/bizley/migration/views/migration.php';
    
    /**
     * @var boolean indicates whether the table names generated should consider
     * the `tablePrefix` setting of the DB connection. For example, if the table
     * name is `post` the generator wil return `{{%post}}`.
     */
    public $useTablePrefix = true;
    
    /**
     * @var Connection|array|string the DB connection object or the application 
     * component ID of the DB connection to use when applying migrations. 
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
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * It checks the existence of the [[migrationPath]].
     * @param \yii\base\Action $action the action to be executed.
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
    
    public function actionCreate($table)
    {
        
    }
}