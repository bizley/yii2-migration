<?php

namespace bizley\tests\cases;

use Yii;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii\console\Controller;
use yii\db\Connection;
use yii\db\Exception;

abstract class DbTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    public static $schema;

    /**
     * @var bool
     */
    protected static $runMigrations = true;

    /**
     * @var Connection
     */
    protected static $db;

    /**
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function setUpBeforeClass()
    {
        new Application([
            'id' => 'MigrationTest',
            'basePath' => __DIR__ . '/../',
            'vendorPath' => __DIR__ . '/../../vendor/',
            'controllerMap' => [
                'migration' => [
                    'class' => 'bizley\migration\controllers\MigrationController',
                    'migrationPath' => null,
                    'migrationNamespace' => null,
                ],
                'migrate' => [
                    'class' => 'bizley\tests\controllers\EchoMigrateController',
                    'migrationNamespaces' => ['bizley\tests\migrations'],
                    'migrationPath' => null,
                    'interactive' => false
                ],
            ],
            'components' => [
                'db' => static::getConnection(),
            ],
        ]);

        if (static::$runMigrations) {
            static::runSilentMigration('migrate/up');
        }
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
            static::$db = null;
        }

        Yii::$app = null;
    }

    /**
     * @return Connection
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function getConnection()
    {
        if (static::$db === null) {
            if (static::$schema === null) {
                throw new InvalidConfigException('You must specify DBMS name');
            }

            $params = require __DIR__ . '/../config.php';

            if (!array_key_exists(static::$schema, $params)) {
                throw new InvalidConfigException('There is no configuration for requested DBMS');
            }

            $database = $params[static::$schema];

            $db = new Connection();
            $db->dsn = $database['dsn'];

            if (isset($database['charset'])) {
                $db->charset = $database['charset'];
            }

            if (isset($database['username'])) {
                $db->username = $database['username'];
                $db->password = $database['password'];
            }

            if (isset($database['attributes'])) {
                $db->attributes = $database['attributes'];
            }

            if (!$db->isActive) {
                $db->open();
            }

            static::$db = $db;
        }

        return static::$db;
    }

    /**
     * @return Connection
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function getDb()
    {
        return static::getConnection();
    }
}
