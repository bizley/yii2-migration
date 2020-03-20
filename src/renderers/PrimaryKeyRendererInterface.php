<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

interface PrimaryKeyRendererInterface
{
    public function render(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string;
}
