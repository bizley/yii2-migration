<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

class UpdaterPkTest extends \bizley\tests\functional\UpdaterPkTest
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

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByDroppingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->dropPrimaryKey('string_pk-primary-key', 'string_pk')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['string_pk']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->alterColumn(\'{{%string_pk}}\', \'col\', $this->string()->notNull());
        
        $this->dropPrimaryKey(\'string_pk-primary-key\', \'{{%string_pk}}\');
    }

    public function down()
    {
        $this->addPrimaryKey(\'string_pk-primary-key\', \'{{%string_pk}}\', [\'col\']);

        $this->alterColumn(\'{{%string_pk}}\', \'col\', $this->string()->append(\'PRIMARY KEY\'));
    }',
            MigrationControllerStub::$content
        );
    }
}
