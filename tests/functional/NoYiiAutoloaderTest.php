<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\console\ExitCode;

/**
 * This test should be run separate with phpunit-no-yii-autoload.xml config (bootstrap-no-autoload.php bootstrap)
 * to make sure no standard Yii autoloader is registered first.
 * @group autoloader
 */
class NoYiiAutoloaderTest extends DbLoaderTestCase
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @test
     * @throws Exception
     */
    public function shouldProvideOwnYiiAutoloader(): void
    {
        $controller = new MigrationControllerStub('migration', Yii::$app);
        $controller->migrationPath = '@bizley/tests/migrations';
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;

        $this->addBase();

        self::assertEquals(ExitCode::OK, $controller->runAction('update', ['updater_base']));
    }
}
