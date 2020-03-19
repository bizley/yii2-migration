<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureInterface;

interface ComparatorInterface
{
    public function setBlueprint(BlueprintInterface $blueprint): void;

    public function compare(StructureInterface $newStructure, StructureInterface $oldStructure, bool $onlyShow): void;
}
