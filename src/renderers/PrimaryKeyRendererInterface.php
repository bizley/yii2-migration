<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

interface PrimaryKeyRendererInterface
{
    public function renderUp(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string;

    public function renderDown(?PrimaryKeyInterface $primaryKey, string $tableName, int $indent = 0): ?string;
}
