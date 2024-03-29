<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

/** @group sqlite */
final class UpdaterTest extends \bizley\tests\functional\UpdaterTest
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
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer()->unsigned())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->unsigned()->after(\'col3\'));
    }

    public function safeDown()
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
    public function shouldUpdateTableByAlteringColumnWithUnique(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer()->unique(),
                'col2' => $this->string(),
                'col3' => $this->timestamp()->defaultValue(null)
            ]
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'sqlite_autoindex_updater_base_1\', \'{{%updater_base}}\', [\'col\'], true);
    }

    public function safeDown()
    {
        $this->dropIndex(\'sqlite_autoindex_updater_base_1\', \'{{%updater_base}}\');
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
    public function shouldNotUpdateTableByAddingForeignKey(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base_fk')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base_fk',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->integer()->unique(),
                'updater_base_id' => $this->integer(),
                'FOREIGN KEY(col) REFERENCES updater_base(id)'
            ]
        )->execute();
        $this->getDb()->createCommand()->createIndex('idx-col', 'updater_base_fk', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Updating table \'updater_base_fk\' requires manual migration!
 > ADD FOREIGN KEY is not supported by SQLite.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldNotUpdateTableByAlteringForeignKey(): void
    {
        $this->getDb()->createCommand()->dropTable('updater_base_fk')->execute();
        $this->getDb()->createCommand()->createTable(
            'updater_base_fk',
            [
                'id' => $this->primaryKey(),
                'col' => $this->integer(),
                'col2' => $this->integer()->unique(),
                'updater_base_id' => $this->integer(),
                'FOREIGN KEY(col) REFERENCES updater_base_fk_target(id)'
            ]
        )->execute();
        $this->getDb()->createCommand()->createIndex('idx-col', 'updater_base_fk', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            ' > Updating table \'updater_base_fk\' requires manual migration!
 > ADD FOREIGN KEY is not supported by SQLite.',
            MigrationControllerStub::$stdout
        );
        self::assertSame('', MigrationControllerStub::$content);
    }
}
