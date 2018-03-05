<?php

namespace bizley\migration\table;

class TableColumnDouble extends TableColumn
{
    /**
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision;
    }

    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'double(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
