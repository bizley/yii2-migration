<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;

class ColumnRenderer
{
    /**
     * @var ColumnInterface
     */
    private $column;

    public function __construct(ColumnInterface $column)
    {
        $this->column = $column;
    }

    public function render(int $indent = 0): string
    {

    }
}
