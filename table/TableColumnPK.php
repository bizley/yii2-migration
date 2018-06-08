<?php

namespace bizley\migration\table;

/**
 * Class TableColumnPK
 * @package bizley\migration\table
 */
class TableColumnPK extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table): void
    {
        $this->definition[] = 'primaryKey(' . ($table->generalSchema ? null : $this->length) . ')';
        if ($table->generalSchema) {
            $this->isPkPossible = false;
            $this->isNotNullPossible = false;
        }
    }
}
