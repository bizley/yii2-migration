<?php

namespace bizley\migration\table;

class TableColumnDateTime extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "dateTime({$this->length})";
    }
}
