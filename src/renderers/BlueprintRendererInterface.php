<?php

declare(strict_types=1);

namespace bizley\migration\renderers;

use bizley\migration\table\BlueprintInterface;

interface BlueprintRendererInterface
{
    public function setBlueprint(BlueprintInterface $blueprint): void;
}
