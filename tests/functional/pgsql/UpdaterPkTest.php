<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

class UpdaterPkTest extends \bizley\tests\functional\UpdaterPkTest
{
    /** @var string */
    public static $schema = 'pgsql';

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', 'col')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->primaryKey());
    }

    public function down()
    {
        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }
}
