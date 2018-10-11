<?php

namespace bizley\migration\table;

/**
 * Class TableColumnText
 * @package bizley\migration\table
 */
class TableColumnText extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'text(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
