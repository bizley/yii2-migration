<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\StructureInterface;

interface StructureRendererInterface
{
    public function setStructure(StructureInterface $structure): void;
    public function render(
        string $schema,
        string $engineVersion = null,
        bool $generalSchema = true,
        int $indent = 0
    ): string;
}
