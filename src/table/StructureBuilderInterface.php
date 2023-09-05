<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureBuilderInterface
{
    /**
     * Builds table structure based on the list of changes from the Inspector.
     * @param array<StructureChangeInterface> $changes
     */
    public function build(array $changes, ?string $schema, ?string $engineVersion): StructureInterface;
}
