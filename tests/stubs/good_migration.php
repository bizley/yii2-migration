<?php

declare(strict_types=1);

use bizley\migration\dummy\MigrationChangesInterface;
use bizley\migration\table\StructureChange;
use bizley\migration\table\StructureChangeInterface;

final class good_migration implements MigrationChangesInterface
{
    public function up(): bool
    {
        return true;
    }

    /** @return array<string, array<StructureChangeInterface>> */
    public function getChanges(): array
    {
        return ['table' => [new StructureChange()]];
    }

    public function down(): bool
    {
        return true;
    }
}
