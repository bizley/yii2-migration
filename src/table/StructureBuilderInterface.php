<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureBuilderInterface
{
    public function setStructure(StructureInterface $structure): void;

    public function apply(array $changes): void;
}
