<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\IndexInterface;

class IndexRenderer
{
    /**
     * @var IndexInterface
     */
    private $index;

    public function __construct(IndexInterface $index)
    {
        $this->index = $index;
    }

    public function render(int $indent = 0): string
    {

    }
}
