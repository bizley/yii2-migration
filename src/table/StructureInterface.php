<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureInterface
{
    /**
     * @return array<ForeignKeyInterface>
     */
    public function getForeignKeys(): array;
}
