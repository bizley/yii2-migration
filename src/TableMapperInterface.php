<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureInterface;

interface TableMapperInterface
{
    public function mapTable(string $table): void;
    public function getStructure(): StructureInterface;
}
