<?php

namespace bizley\migration\dummy;

interface MigrationChangesInterface
{
    public function up();

    public function getChanges(): array;
}
