<?php

namespace bizley\migration\table;

class TableColumnTimestamp extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "timestamp({$this->length})";
    }
}
