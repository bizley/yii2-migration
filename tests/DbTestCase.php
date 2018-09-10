<?php

declare(strict_types=1);

namespace bizley\migration\tests;

use Yii;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\console\Application;
use bizley\migration\controllers\MigrationController;

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
     * @param null $default
     * @return mixed
     */
    public static function getParam(string $name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require __DIR__ . '/config.php';
        }
        return static::$params[$name] ?? $default;
    }

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     * @throws \yii\db\Exception
     */
    public static function setUpBeforeClass() // BC declaration
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
    protected static function mockApplication(array $config = [], $appClass = Application::class): void
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'MigrationTest',
            'basePath' => __DIR__,
            'vendorPath' => __DIR__ . '/../vendor/',
            'controllerMap' => [
                'migration' => [
                    'class' => MigrationController::class,
                    'migrationPath' => null,
                    'migrationNamespace' => null,
                ],
                'migrate' => [
                    'class' => EchoMigrateController::class,
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

    /**
     * @param $route
     * @param array $params
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
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

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public static function tearDownAfterClass() // BC declaration
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
    public static function getConnection(): Connection
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
