<?php

declare(strict_types=1);

namespace bizley\tests\functional;

use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception as DbException;

abstract class UpdaterTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;

    /**
     * @throws NotSupportedException
     * @throws DbException
     */
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
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldHandleNonExistingTable(): void
    {
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['non-existing-table']));

        $this->assertStringContainsString(' > No matching tables in database.', MigrationControllerStub::$stdout);
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldFindMatchingTables(): void
    {
        MigrationControllerStub::$confirmControl = false;
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base_*']));
        $this->assertStringContainsString(
            ' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - updater_base_fk
   - updater_base_fk_target
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldUpdateTableByAddingColumn(): void
    {
        $this->getDb()->createCommand()->addColumn('updater_base', 'added', $this->integer())->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            ' > 1 table excluded by the config.

 > Comparing current table \'updater_base\' with its migrations ...DONE!

 > Generating migration for updating table \'updater_base\' ...DONE!
 > Saved as \'',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            '_update_table_updater_base.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
        $this->assertStringContainsString(
            'public function up()
    {
        $this->addColumn(\'{{%updater_base}}\', \'added\', $this->integer()->after(\'col2\'));
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
    public function shouldUpdateTableByAddingIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add', 'updater_base', 'col')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->createIndex(\'idx-add\', \'{{%updater_base}}\', [\'col\']);
    }

    public function down()
    {
        $this->dropIndex(\'idx-add\', \'{{%updater_base}}\');
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
    public function shouldUpdateTableByAddingUniqueIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add-unique', 'updater_base', 'col', true)->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->createIndex(\'idx-add-unique\', \'{{%updater_base}}\', [\'col\'], true);
    }

    public function down()
    {
        $this->dropIndex(\'idx-add-unique\', \'{{%updater_base}}\');
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
    public function shouldUpdateTableByAddingMultiIndex(): void
    {
        $this->getDb()->createCommand()->createIndex('idx-add-multi', 'updater_base', ['col', 'col2'])->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->createIndex(\'idx-add-multi\', \'{{%updater_base}}\', [\'col\', \'col2\']);
    }

    public function down()
    {
        $this->dropIndex(\'idx-add-multi\', \'{{%updater_base}}\');
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
    public function shouldUpdateTableByAddingMultiUniqueIndex(): void
    {
        $this->getDb()->createCommand()->createIndex(
            'idx-add-multi-unique',
            'updater_base',
            ['col', 'col2'],
            true
        )->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['updater_base']));
        $this->assertStringContainsString(
            'public function up()
    {
        $this->createIndex(\'idx-add-multi-unique\', \'{{%updater_base}}\', [\'col\', \'col2\'], true);
    }

    public function down()
    {
        $this->dropIndex(\'idx-add-multi-unique\', \'{{%updater_base}}\');
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
        $this->getDb()->createCommand()->dropIndex('idx-col', 'updater_base_fk')->execute();

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
