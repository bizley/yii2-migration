<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

interface IndexRendererInterface
{
    public function renderUp(IndexInterface $index, string $tableName, int $indent = 0): ?string;
}
