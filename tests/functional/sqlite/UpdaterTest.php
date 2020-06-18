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

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->unsigned()->after(\'col3\'));
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

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->createIndex(\'sqlite_autoindex_updater_base_1\', \'{{%updater_base}}\', [\'col\'], true);
    }

    public function down()
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

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        $this->assertStringContainsString(
            ' > Updating table \'updater_base_fk\' requires manual migration!
 > ADD FOREIGN KEY is not supported by SQLite.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
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

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        $this->assertStringContainsString(
            ' > Updating table \'updater_base_fk\' requires manual migration!
 > ADD FOREIGN KEY is not supported by SQLite.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
