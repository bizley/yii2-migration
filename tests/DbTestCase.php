<?php

namespace bizley\migration\tests;

use Yii;
use yii\console\ExitCode;
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

    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require __DIR__ . '/config.php';
        }
        return static::$params[$name] ?? $default;
    }

    public static function setUpBeforeClass()
    {
        static::mockApplication();
        if (static::$runMigrations) {
            static::runSilentMigration('migrate/up');
        }
    }

    protected static function mockApplication(array $config = [], $appClass = '\yii\console\Application'): void
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'MigrationTest',
            'basePath' => __DIR__,
            'vendorPath' => __DIR__ . '/../../../../vendor/',
            'controllerMap' => [
                'migration' => [
                    'class' => 'bizley\migration\controllers\MigrationController',
                    'migrationPath' => null,
                    'migrationNamespace' => null,
                ],
                'migrate' => [
                    'class' => 'bizley\migration\tests\EchoMigrateController',
                    'migrationNamespaces' => ['bizley\migration\tests\migrations'],
                    'migrationPath' => null,
                    'interactive' => false
                ],
            ],
            'components' => [
                'db' => static::getConnection(),
            ],
        ], $config));
    }

    protected static function runSilentMigration($route, array $params = []): void
    {
        ob_start();
        if (Yii::$app->runAction($route, $params) === ExitCode::OK) {
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
