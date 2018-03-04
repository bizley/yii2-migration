<?php

namespace bizley\migration\table;

class TableColumnFloat extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "float({$this->length})";
    }
}
