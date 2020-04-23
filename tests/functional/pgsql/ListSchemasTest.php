<?php

declare(strict_types=1);

namespace bizley\tests\functional\pgsql;

use bizley\tests\functional\DbLoaderTestCase;
use bizley\tests\stubs\MigrationControllerStub;
use Yii;
use yii\base\InvalidRouteException;
use yii\base\NotSupportedException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\db\Exception;

/** @group pgsql */
class ListSchemasTest extends DbLoaderTestCase
{
    /** @var MigrationControllerStub */
    protected $controller;
    /** @var string */
    public static $schema = 'pgsql';

    /**
     * @throws NotSupportedException
     * @throws Exception
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
     * @throws InvalidRouteException
     * @throws ConsoleException
     */
    public function shouldShowListActionWithAllTables(): void
    {
        $this->assertEquals(ExitCode::OK, $this->controller->runAction('list'));

        $this->assertStringContainsString('Yii 2 Migration Generator Tool v', MigrationControllerStub::$stdout);
        $this->assertStringContainsString(' > Your database contains ', MigrationControllerStub::$stdout);
        $this->assertStringContainsString('   - table1', MigrationControllerStub::$stdout);
        $this->assertStringContainsString('   - schema1.table1', MigrationControllerStub::$stdout);
        $this->assertStringContainsString('   - schema2.table1', MigrationControllerStub::$stdout);
        $this->assertStringContainsString(
            '
 > Run
   migration/create <table>
      to generate creating migration for the specific table.
   migration/update <table>
      to generate updating migration for the specific table.

 > <table> can be:
   - * (asterisk) - for all the tables in database (except excluded ones)
   - string with * (one or more) - for all the tables in database matching the pattern (except excluded ones)
   - string without * - for the table of specified name
   - strings separated with comma - for multiple tables of specified names (with optional *)
',
            MigrationControllerStub::$stdout
        );
    }
}
