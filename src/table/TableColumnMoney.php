<?php

namespace bizley\migration\table;

class TableColumnMoney extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = "money({$this->length})";
    }
}
