<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureBuilderInterface
{
    public function getStructure(): StructureInterface;

    public function build(array $changes): void;
}
