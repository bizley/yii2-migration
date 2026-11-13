<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Yii;
use yii\base\Exception;
use yii\console\ExitCode;

/**
 * This test should be run separate with phpunit-no-yii-autoload.xml config (bootstrap-no-autoload.php bootstrap)
 * to make sure no standard Yii autoloader is registered first.
 */
#[PreserveGlobalState(false)]
#[Group('autoloader')]
#[RunTestsInSeparateProcesses]
class NoYiiAutoloaderTest extends DbLoaderTestCase
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @throws Exception
     */
    #[Test]
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
