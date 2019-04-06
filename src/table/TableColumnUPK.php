<?php

declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnUPK
 * @package bizley\migration\table
 */
class TableColumnUPK extends TableColumnPK
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        parent::buildSpecificDefinition($table);

        if ($table->generalSchema) {
            $this->definition[] = 'unsigned()';
            $this->isUnsignedPossible = false;
        }
    }
}
