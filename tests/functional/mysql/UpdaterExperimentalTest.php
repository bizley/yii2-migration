<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

final class UpdaterExperimentalTest extends \bizley\tests\functional\UpdaterExperimentalTest
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldNotUpdateTableWhenItsNotChanged(): void
    {
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['exp_updater_base']));
        self::assertSame('', MigrationControllerStub::$content);
    }
}
