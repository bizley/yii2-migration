<?php

namespace bizley\migration\table;

class TableColumnTinyInt extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'tinyInteger(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
