<?php

namespace bizley\migration\table;

class TableColumnString extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "string({$this->length})";
    }
}
