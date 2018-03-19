<?php

namespace bizley\migration\table;

class TableColumnText extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'text(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
