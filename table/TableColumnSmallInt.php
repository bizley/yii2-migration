<?php

namespace bizley\migration\table;

class TableColumnSmallInt extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'smallInteger(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
