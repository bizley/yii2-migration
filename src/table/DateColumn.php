<?php

declare(strict_types=1);

namespace bizley\migration\table;

class DateColumn extends Column
{
    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    protected function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'date()';
    }
}
