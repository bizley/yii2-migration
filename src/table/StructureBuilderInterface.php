<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureBuilderInterface
{
    /**
     * Builds table structure based on the list of changes from the Inspector.
     * @param array<StructureChangeInterface> $changes
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return StructureInterface
     */
    public function build(array $changes, ?string $schema, ?string $engineVersion): StructureInterface;
}
