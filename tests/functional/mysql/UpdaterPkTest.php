<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

#[Group('mysql')]
final class UpdaterPkTest extends \bizley\tests\functional\UpdaterPkTestCase
{
    /** @var string */
    public static $schema = 'mysql';

    /** @var string */
    public static $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    /**
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
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
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    #[Test]
    public function shouldUpdateTableByAddingCompositePrimaryKey(): void
    {
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', ['col', 'col2'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->integer()->notNull());
        $this->alterColumn(\'{{%no_pk}}\', \'col2\', $this->integer()->notNull());

        $this->addPrimaryKey(\'PRIMARYKEY\', \'{{%no_pk}}\', [\'col\', \'col2\']);
    }

    public function safeDown()
    {
        $this->dropPrimaryKey(\'PRIMARYKEY\', \'{{%no_pk}}\');

        $this->alterColumn(\'{{%no_pk}}\', \'col\', $this->integer());
        $this->alterColumn(\'{{%no_pk}}\', \'col2\', $this->integer());
    }',
            MigrationControllerStub::$content
        );
    }
}
