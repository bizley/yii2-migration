<?php declare(strict_types=1);

namespace bizley\tests\cases;

use bizley\migration\controllers\MigrationController;
use bizley\tests\controllers\EchoMigrateController;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii\console\ExitCode;
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
    public static function setUpBeforeClass(): void
    {
        new Application([
            'id' => 'MigrationTest',
            'basePath' => __DIR__,
            'vendorPath' => __DIR__ . '/../../vendor/',
            'controllerMap' => [
                'migration' => [
                    'class' => MigrationController::class,
                    'migrationPath' => null,
                    'migrationNamespace' => null,
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
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
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
     * @throws \yii\base\InvalidRouteException
     * @throws \yii\console\Exception
     */
    public static function tearDownAfterClass(): void
    {
        static::runSilentMigration('migrate/down', ['all']);

        if (static::$db) {
            static::$db->close();
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
            $db->charset = $database['charset'];

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
}
