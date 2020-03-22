<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureInterface;

interface ComparatorInterface
{
    public function compare(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema,
        ?string $engineVersion
    ): void;
}
