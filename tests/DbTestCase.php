<?php

namespace bizley\tests;

use Yii;
use yii\console\Controller;
use yii\db\Connection;
use yii\helpers\ArrayHelper;

abstract class DbTestCase extends \PHPUnit\Framework\TestCase
{
    public static $params;
    protected static $database;
    protected static $runMigrations = true;

    /**
     * @var Connection
     */
    protected static $db;

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require __DIR__ . '/config.php';
        }
        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     * @throws \yii\db\Exception
     */
    public static function setUpBeforeClass()
    {
        static::mockApplication();
        if (static::$runMigrations) {
            static::runSilentMigration('migrate/up');
        }
    }

    /**
     * @param array $config
     * @param string $appClass
     * @throws \yii\db\Exception
     */
    protected static function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'MigrationTest',
            'basePath' => __DIR__,
            'vendorPath' => __DIR__ . '/../vendor/',
            'controllerMap' => [
                'migration' => [
                    'class' => 'bizley\migration\controllers\MigrationController',
                    'migrationPath' => null,
                    'migrationNamespace' => null,
                ],
                'migrate' => [
                    'class' => 'bizley\tests\EchoMigrateController',
                    'migrationNamespaces' => ['bizley\tests\migrations'],
                    'migrationPath' => null,
                    'interactive' => false
                ],
            ],
            'components' => [
                'db' => static::getConnection(),
            ],
        ], $config));
    }

    /**
     * @param string $route
     * @param array $params
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
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

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public static function tearDownAfterClass()
    {
        static::runSilentMigration('migrate/down', ['all']);
        if (static::$db) {
            static::$db->close();
        }
        Yii::$app = null;
    }

    /**
     * @return Connection
     * @throws \yii\db\Exception
     */
    public static function getConnection()
    {
        if (static::$db === null) {
            $db = new Connection();
            $db->dsn = static::$database['dsn'];
            $db->charset = static::$database['charset'];
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
