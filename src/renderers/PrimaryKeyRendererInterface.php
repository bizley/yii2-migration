<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

interface PrimaryKeyRendererInterface
{
    public function setPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function render(string $tableName, int $indent = 0): ?string;
}
