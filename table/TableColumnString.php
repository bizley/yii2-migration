<?php

namespace bizley\migration\table;

/**
 * Class TableColumnString
 * @package bizley\migration\table
 */
class TableColumnString extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table): void
    {
        $this->definition[] = 'string(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
