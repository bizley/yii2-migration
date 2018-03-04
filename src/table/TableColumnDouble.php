<?php

namespace bizley\migration\table;

class TableColumnDouble extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "double({$this->length})";
    }
}
