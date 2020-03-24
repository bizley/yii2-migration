<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureBuilderInterface
{
    /**
     * @param array<StructureChangeInterface> $changes
     * @param string|null $schema
     * @return StructureInterface
     */
    public function build(array $changes, ?string $schema): StructureInterface;
}
