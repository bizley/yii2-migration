<?php

declare(strict_types=1);

namespace bizley\tests\unit\stubs;

use bizley\migration\dummy\MigrationChangesInterface;
use bizley\migration\table\StructureChange;

final class GoodMigration implements MigrationChangesInterface
{
    public function up()
    {
    }

    public function getChanges(): ?array
    {
        return ['table' => [new StructureChange()]];
    }

    public function down()
    {
    }
}
