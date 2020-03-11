<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

interface ForeignKeyRendererInterface
{
    public function setForeignKey(ForeignKeyInterface $foreignKey): void;

    public function render(string $tableName, string $referencedTableName, int $indent = 0): ?string;
}
