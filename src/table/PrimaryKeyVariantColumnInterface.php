<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface PrimaryKeyVariantColumnInterface extends ColumnInterface
{
    /**
     * Returns primary key variant column definition.
     */
    public function getPrimaryKeyDefinition(): string;
}
