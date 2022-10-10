<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\migration\controllers\MigrationController;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii\db\Connection;
use yii\db\Exception;

use function array_key_exists;

abstract class DbTestCase extends TestCase
{
    /** @var string */
    public static $schema;

    /** @var Connection */
    protected static $db;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function setUpBeforeClass(): void
    {
        Yii::$app = null;
        new Application(
            [
                'id' => 'MigrationTest',
                'basePath' => __DIR__ . '/../',
                'vendorPath' => __DIR__ . '/../../vendor/',
                'controllerMap' => [
                    'migration' => [
                        'class' => MigrationController::class,
                    ],
                ],
                'components' => [
                    'db' => static::getConnection(),
                ],
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
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
}
