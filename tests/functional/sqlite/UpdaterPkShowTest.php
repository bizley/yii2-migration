<?php

declare(strict_types=1);

namespace bizley\tests\functional\sqlite;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

class UpdaterPkShowTest extends \bizley\tests\functional\UpdaterPkShowTest
{
    /** @var string */
    public static $schema = 'sqlite';

    /**
     * @test
     * @throws ConsoleException
     * @throws InvalidRouteException
     * @throws Exception
     */
    public function shouldShowUpdateTableByAddingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->dropTable('no_pk')->execute();
        $this->getDb()->createCommand()->createTable('no_pk', ['col' => $this->primaryKey()])->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        $this->assertStringContainsString(
            ' > Comparing current table \'no_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: NULL)
   - (!) ALTER COLUMN is not supported by SQLite: Migration must be created manually
   - different primary key definition
   - (!) ADD PRIMARY KEY is not supported by SQLite: Migration must be created manually

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
