<?php

namespace bizley\migration\table;

class TableColumnBoolean extends TableColumn
{
    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'boolean()';
    }
}
