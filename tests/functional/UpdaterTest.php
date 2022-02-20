<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\console\ExitCode;

abstract class UpdaterTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    protected function setUp(): void
    {
        $this->controller = new MigrationControllerStub('migration', Yii::$app);
        $this->controller->migrationPath = '@bizley/tests/migrations';
        MigrationControllerStub::$stdout = '';
        MigrationControllerStub::$content = '';
        MigrationControllerStub::$confirmControl = true;

        $this->addBase();
    }

    /**
     * @test
     */
    public function shouldHandleNonExistingTable(): void
    {
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['non-existing-table']));

        self::assertStringContainsString(' > No matching tables in database.', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     */
    public function shouldFindMatchingTables(): void
    {
        MigrationControllerStub::$confirmControl = false;
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_*']));
        self::assertStringContainsString(
            ' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - updater_base_fk
   - updater_base_fk_target
   - updater_base_fk_with_idx
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     */
    public function shouldUpdateTableByAddingColumn(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            ' > 1 table excluded by the config.

 > Comparing current table \'updater_base\' with its migrations ...DONE!

 > Generating migration for updating table \'updater_base\' ...DONE!
 > Saved as \'',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_update_table_updater_base.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->after(\'col3\'));
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
     */
    public function shouldUpdateTableByAddingIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add', 'updater_base', 'col')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'idx-add\', \'{{%updater_base}}\', [\'col\']);
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-add\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     */
    public function shouldUpdateTableByAddingUniqueIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add-unique', 'updater_base', 'col', true)->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'idx-add-unique\', \'{{%updater_base}}\', [\'col\'], true);
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-add-unique\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     */
    public function shouldUpdateTableByAddingMultiIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add-multi', 'updater_base', ['col', 'col2'])->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'idx-add-multi\', \'{{%updater_base}}\', [\'col\', \'col2\']);
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-add-multi\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     */
    public function shouldUpdateTableByAddingMultiUniqueIndex(): void
    {
        $this->getDb()->createCommand()->createIndex(
            'idx-add-multi-unique',
            'updater_base',
            ['col', 'col2'],
            true
        )->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->createIndex(\'idx-add-multi-unique\', \'{{%updater_base}}\', [\'col\', \'col2\'], true);
    }

    public function safeDown()
    {
        $this->dropIndex(\'idx-add-multi-unique\', \'{{%updater_base}}\');
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     */
    public function shouldUpdateTableByDroppingIndex(): void
    {
        $this->getDb()->createCommand()->dropIndex('idx-col', 'updater_base_fk')->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk']));
        self::assertStringContainsString(
            'public function safeUp()
    {
        $this->dropIndex(\'idx-col\', \'{{%updater_base_fk}}\');
    }

    public function safeDown()
    {
        $this->createIndex(\'idx-col\', \'{{%updater_base_fk}}\', [\'col\']);
    }',
            MigrationControllerStub::$content
        );
    }

    /**
     * @test
     */
    public function shouldNotUpdateTableWithTimestampColumnWhenItsNotChanged(): void
    {
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     */
    public function shouldNotUpdateTableWithForeignKeyAndExplicitIndexWhenItsNotChanged(): void
    {
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_fk_with_idx']));
        self::assertSame('', MigrationControllerStub::$content);
    }

    /**
     * @test
     */
    public function shouldNotCreateNewTableWhenTableIsRenamed(): void
    {
        $this->getDb()->createCommand()->renameTable('updater_base', 'renamed_base')->execute();
        $this->addHistoryEntry('m20201027_135000_rename_table_updater_base');

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['renamed_base']));
        self::assertSame('', MigrationControllerStub::$content);
    }
}
