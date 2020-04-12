<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

class UpdaterTest extends \bizley\tests\functional\UpdaterTest
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumnWithUnsigned(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer()->unsigned())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->unsigned()->after(\'col2\'));
    }

    public function down()
    {
        $this->dropColumn(\'{{%updater_base}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByDroppingIndex(): void
    {
        $this->addBase();
        $this->getDb()->createCommand()->dropIndex('idx-updater_base_fk', 'updater_base_fk')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->dropIndex(\'idx-col\', \'{{%updater_base_fk}}\');
    }

    public function down()
    {
        $this->createIndex(\'idx-col\', \'{{%updater_base_fk}}\', [\'col\']);
    }',
            MigrationControllerStub::$content
        );
    }
}
