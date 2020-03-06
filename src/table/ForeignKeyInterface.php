<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface ForeignKeyInterface
{
    public function getReferencedTable(): string;
}
