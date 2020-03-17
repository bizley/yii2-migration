<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKeyInterface;

interface ColumnRendererInterface
{
    public function setColumn(ColumnInterface $column): void;

    public function setPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function render(
        string $schema,
        string $engineVersion = null,
        bool $generalSchema = true,
        int $indent = 0
    ): ?string;
}
