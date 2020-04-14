<?php

declare(strict_types=1);

namespace bizley\tests\functional\mysql;

use bizley\tests\stubs\MigrationControllerStub;
use yii\base\Exception;
use yii\base\InvalidRouteException;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;

class UpdaterPkShowTest extends \bizley\tests\functional\UpdaterPkShowTest
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
    public function shouldShowUpdateTableByAddingPrimaryKey(): void
    {
        $this->getDb()->createCommand()->addPrimaryKey('primary-new', 'no_pk', 'col')->execute();

        $this->assertEquals(ExitCode::OK, $this->controller->runAction('update', ['no_pk']));
        $this->assertStringContainsString(
            ' > Comparing current table \'no_pk\' with its migrations ...Showing differences:
   - different \'col\' column property: not null (DB: TRUE != MIG: NULL)
   - different primary key definition

 No files generated.',
            MigrationControllerStub::$stdout
        );
        $this->assertSame('', MigrationControllerStub::$content);
    }
}
