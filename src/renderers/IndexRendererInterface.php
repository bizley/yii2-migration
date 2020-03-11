<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

interface IndexRendererInterface
{
    public function setIndex(IndexInterface $index): void;

    public function render(string $tableName, int $indent = 0): ?string;
}
