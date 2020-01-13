<?php

declare(strict_types=1);

namespace bizley\migration\table;

class UnsignedPrimaryKeyColumn extends PrimaryKeyColumn
{
    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    public function buildSpecificDefinition(Structure $table): void
    {
        parent::buildSpecificDefinition($table);

        if ($table->generalSchema) {
            $this->definition[] = 'unsigned()';
            $this->isUnsignedPossible = false;
        }
    }
}
