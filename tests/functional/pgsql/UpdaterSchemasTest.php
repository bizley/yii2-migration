<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\functional\DbLoaderTestCase;
use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception as DbException;

/** @group pgsql */
class UpdaterSchemasTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;
    /** @var string */
    public static $schema = 'pgsql';

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

        $this->addSchemasBase();
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldFindMatchingSchemasTables(): void
    {
        MigrationControllerStub::$confirmControl = false;
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['schema*']));
        self::assertStringContainsString(
            ' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - schema1.table1
   - schema2.table1
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
    public function shouldFindMatchingTablesWithoutSchemasToo(): void
    {
        $this->createTables([
            'schematable' => ['col' => $this->integer()]
        ]);

        MigrationControllerStub::$confirmControl = false;
        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['schema*']));
        self::assertStringContainsString(
            ' > 1 table excluded by the config.
 > Are you sure you want to generate migrations for the following tables?
   - schematable
   - schema1.table1
   - schema2.table1
 Operation cancelled by user.
',
            MigrationControllerStub::$stdout
        );

        $this->getDb()->createCommand()->dropTable('schematable')->execute();
    }

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldCreateNewSchemaTable(): void
    {
        $this->createSchema('test');
        $this->createTables([
            'test.table' => ['col' => $this->integer()]
        ]);
        $transaction = $this->getDb()->getTransaction();
        if ($transaction) {
            $transaction->commit();
        }

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['test.table']));
        self::assertStringContainsString(
            ' > 1 table excluded by the config.

 > Comparing current table \'test.table\' with its migrations ...DONE!

 > Generating migration for creating table \'test.table\' ...DONE!
 > Saved as \'',
            MigrationControllerStub::$stdout
        );

        self::assertStringContainsString(
            '_create_table_test_table.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_create_table_test_table extends Migration',
            MigrationControllerStub::$content
        );
        self::assertStringContainsString(
            '
        $this->createTable(
            \'{{%test.table}}\',
            [
                \'col\' => $this->integer(),
            ],
            $tableOptions
        );
    }

    public function down()
    {
        $this->dropTable(\'{{%test.table}}\');
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
    public function shouldUpdateSchemaTable(): void
    {
        $this->getDb()->createCommand()->addColumn('schema1.table1', 'added', $this->integer())->execute();

        self::assertEquals(ExitCode::OK, $this->controller->runAction('update', ['schema1.table1']));
        self::assertStringContainsString(
            ' > 1 table excluded by the config.

 > Comparing current table \'schema1.table1\' with its migrations ...DONE!

 > Generating migration for updating table \'schema1.table1\' ...DONE!
 > Saved as \'',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_update_table_schema1_table1.php\'

 Generated 1 file
 (!) Remember to verify files before applying migration.
',
            MigrationControllerStub::$stdout
        );
        self::assertStringContainsString(
            '_update_table_schema1_table1 extends Migration',
            MigrationControllerStub::$content
        );
        self::assertStringContainsString(
            'public function up()
    {
        $this->addColumn(\'{{%schema1.table1}}\', \'added\', $this->integer()->after(\'col2\'));
    }

    public function down()
    {
        $this->dropColumn(\'{{%schema1.table1}}\', \'added\');
    }',
            MigrationControllerStub::$content
        );
    }
}
