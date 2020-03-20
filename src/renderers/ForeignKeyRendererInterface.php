<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ForeignKeyInterface;

interface ForeignKeyRendererInterface
{
    public function render(
        ForeignKeyInterface $foreignKey,
        string $tableName,
        string $referencedTableName,
        int $indent = 0
    ): ?string;
}
