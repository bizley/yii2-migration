<?php

namespace bizley\migration\table;

class TableColumnFloat extends TableColumn
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
        $this->definition[] = 'float(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
