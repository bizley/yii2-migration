<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKeyInterface;

interface ColumnRendererInterface
{
    public function render(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null
    ): ?string;

    public function renderDefinition(
        ColumnInterface $column,
        PrimaryKeyInterface $primaryKey = null,
        string $schema = null,
        string $engineVersion = null
    ): ?string;
}
