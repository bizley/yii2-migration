<?php

namespace bizley\migration\dummy;

use bizley\migration\table\StructureChangeInterface;

interface MigrationChangesInterface
{
    /** @return mixed */
    public function up();

    /** @return array<string, array<StructureChangeInterface>> */
    public function getChanges(): ?array;
}
