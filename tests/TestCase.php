<?php

namespace bizley\migration\tests;

use Yii;
use yii\console\Controller;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public static $params;
    protected static $driverName = 'mysql';
    protected static $database = [
        'dsn' => 'mysql:host=127.0.0.1;dbname=test',
        'username' => 'root',
        'password' => '',
    ];

    /**
     * @var Connection
     */
    protected static $db;

    public static function setUpBeforeClass()
    {
        static::mockApplication();
        static::runSilentMigration('migrate/up');
    }

    protected static function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'MigrationTest',
            'basePath' => __DIR__,
            'vendorPath' => __DIR__ . '/../../../',
            'controllerMap' => [
                'migration' => [
                    'class' => 'bizley\migration\controllers\MigrationController',
                    'interactive' => false
                ],
                'migrate' => [
                    'class' => 'bizley\migration\tests\EchoMigrateController',
                    'migrationNamespaces' => ['bizley\migration\tests\data'],
                    'migrationPath' => null,
                    'interactive' => false
                ],
            ],
            'components' => [
                'db' => static::getConnection(),
            ],
        ], $config));
    }

    protected static function runSilentMigration($route, $params = [])
    {
        ob_start();
        if (Yii::$app->runAction($route, $params) === Controller::EXIT_CODE_NORMAL) {
            ob_end_clean();
        } else {
            fwrite(STDOUT, "\nMigration failed!\n");
            ob_end_flush();
        }
    }

    public static function tearDownAfterClass()
    {
        static::runSilentMigration('migrate/down', ['all']);
        if (static::$db) {
            static::$db->close();
        }
        Yii::$app = null;
    }

    public static function getConnection()
    {
        if (static::$db === null) {
            $db = new Connection();
            $db->dsn = static::$database['dsn'];
            if (isset(static::$database['username'])) {
                $db->username = static::$database['username'];
                $db->password = static::$database['password'];
            }
            if (isset(static::$database['attributes'])) {
                $db->attributes = static::$database['attributes'];
            }
            if (!$db->isActive) {
                $db->open();
            }
            static::$db = $db;
        }
        return static::$db;
    }
}
