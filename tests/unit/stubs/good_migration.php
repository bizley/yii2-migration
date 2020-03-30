<?php

declare(strict_types=1);

use bizley\migration\dummy\MigrationChangesInterface;
use bizley\migration\table\StructureChange;

final class good_migration implements MigrationChangesInterface
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
