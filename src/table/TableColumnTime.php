<?php

namespace bizley\migration\table;

class TableColumnTime extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "time({$this->length})";
    }
}
