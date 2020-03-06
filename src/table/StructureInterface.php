<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureInterface
{
    /**
     * @return array<ForeignKeyInterface>
     */
    public function getForeignKeys(): array;
    public function getPrimaryKey(): ?PrimaryKey;
    public function setPrimaryKey(PrimaryKey $primaryKey): void;
}
