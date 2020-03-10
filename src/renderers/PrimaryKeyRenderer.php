<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\PrimaryKeyInterface;

class PrimaryKeyRenderer
{
    /**
     * @var PrimaryKeyInterface
     */
    private $primaryKey;

    public function __construct(PrimaryKeyInterface $primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    public function render(int $indent = 0): string
    {

    }
}
