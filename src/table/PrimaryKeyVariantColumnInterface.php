<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface PrimaryKeyVariantColumnInterface extends ColumnInterface
{
    public function getPrimaryKeyDefinition(): string;
}
