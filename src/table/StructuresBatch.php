<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class StructuresBatch implements StructuresBatchInterface
{
    /** @var array<StructureInterface> */
    private $batch = [];

    public function add(StructureInterface $structure): void
    {
        $this->batch[$structure->getName()] = $structure;
    }

    public function get(string $structureName): ?StructureInterface
    {
        return $this->batch[$structureName] ?? null;
    }
}
