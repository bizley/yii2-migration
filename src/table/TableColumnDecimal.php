<?php

namespace bizley\migration\table;

class TableColumnDecimal extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "decimal({$this->length})";
    }
}
