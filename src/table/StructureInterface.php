<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureInterface
{
    public function getForeignKeys(): array;
}
