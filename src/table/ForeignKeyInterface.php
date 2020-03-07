<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ForeignKeyInterface
{
    public function getColumns(): array;

    public function getReferencedTable(): string;

    public function getReferencedColumns(): array;

    public function getOnDelete(): ?string;

    public function getOnUpdate(): ?string;
}
