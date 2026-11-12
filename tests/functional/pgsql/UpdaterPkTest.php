<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

/** @group pgsql */
final class UpdaterPkTest extends \bizley\tests\functional\UpdaterPkTest
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->primaryKey());
    }

    public function safeDown()
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

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['string_pk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%string_pk}}\', \'col\', $this->string()->notNull());
    }

    public function safeDown()
    {
        $this->alterColumn(\'{{%string_pk}}\', \'col\', $this->string()->append(\'PRIMARY KEY\'));
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
    public function shouldUpdateTableByAddingCompositePrimaryKey(): void
    {
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', ['col', 'col2'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->integer()->notNull());
        $this->alterColumn(\'{{%no_pk}}\', \'col2\', $this->integer()->notNull());

        $this->addPrimaryKey(\'primary-new\', \'{{%no_pk}}\', [\'col\', \'col2\']);
    }

    public function safeDown()
    {
        $this->dropPrimaryKey(\'primary-new\', \'{{%no_pk}}\');

        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->integer());
        $this->alterColumn(\'{{%no_pk}}\', \'col2\', $this->integer());
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
    public function shouldAddNewColumnBeforeAddingCompositePrimaryKeyWithIt(): void
    {
        $this->getDb()->createCommand()->addColumn('no_pk', 'col3', $this->integer()->notNull())->execute();
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', ['col', 'col3'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        $content = MigrationControllerStub::$content;
        $addColumn = \strpos($content, '$this->addColumn(\'{{%no_pk}}\', \'col3\'');
        $addPrimaryKey = \strpos($content, '$this->addPrimaryKey(\'primary-new\'');
        self::assertNotFalse($addColumn);
        self::assertNotFalse($addPrimaryKey);
        self::assertLessThan($addPrimaryKey, $addColumn);
    }
}
