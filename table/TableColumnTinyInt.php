<?php

namespace bizley\migration\table;

/**
 * Class TableColumnTinyInt
 * @package bizley\migration\table
 */
class TableColumnTinyInt extends TableColumn
{
    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'tinyInteger(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
