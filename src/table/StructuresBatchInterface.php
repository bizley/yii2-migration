<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructuresBatchInterface
{
    public function add(StructureInterface $structure): void;

    public function get(string $structureName): ?StructureInterface;
}
