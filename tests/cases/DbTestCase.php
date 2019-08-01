<?php

declare(strict_types=1);

namespace bizley\tests\cases;

use bizley\migration\controllers\MigrationController;
use bizley\tests\controllers\EchoMigrateController;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\console\Application;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\db\Exception;
use function array_key_exists;
use function fwrite;
use function ob_end_clean;
use function ob_end_flush;
use function ob_start;

abstract class DbTestCase extends TestCase
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
     * @throws InvalidRouteException
     * @throws ConsoleException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function setUpBeforeClass(): void
    {
        new Application([
            'id' => 'MigrationTest',
            'basePath' => __DIR__ . '/../',
            'vendorPath' => __DIR__ . '/../../vendor/',
            'controllerMap' => [
                'migration' => [
                    'class' => MigrationController::class,
                ],
                'migrate' => [
                    'class' => EchoMigrateController::class,
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
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    protected static function runSilentMigration(string $route, array $params = []): void
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
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public static function tearDownAfterClass(): void
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
    public static function getConnection(): Connection
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
    protected function getDb() // BC signature
    {
        return static::getConnection();
    }
}
