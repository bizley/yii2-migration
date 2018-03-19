<?php

namespace bizley\migration\table;

class TableColumnDate extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'date()';
    }
}
