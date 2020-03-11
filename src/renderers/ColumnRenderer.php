<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\ColumnInterface;

class ColumnRenderer implements ColumnRendererInterface
{
    /**
     * @var ColumnInterface
     */
    private $column;

    public function render(int $indent = 0): string
    {

    }

    /**
     * @return ColumnInterface
     */
    public function getColumn(): ColumnInterface
    {
        return $this->column;
    }

    /**
     * @param ColumnInterface $column
     */
    public function setColumn(ColumnInterface $column): void
    {
        $this->column = $column;
    }
}
