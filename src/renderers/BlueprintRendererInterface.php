<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;

interface BlueprintRendererInterface
{
    public function renderUp(
        BlueprintInterface $blueprint,
        int $indent = 0,
        string $schema = null,
        string $engineVersion = null,
        bool $usePrefix = true,
        string $dbPrefix = null
    ): string;
}
