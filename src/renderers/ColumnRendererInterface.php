<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;

interface ColumnRendererInterface
{
    public function setColumn(ColumnInterface $column): void;

    public function render(int $indent = 0): string;
}
