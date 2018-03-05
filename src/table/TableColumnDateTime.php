<?php

namespace bizley\migration\table;

class TableColumnDateTime extends TableColumn
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
        $this->definition[] = 'dateTime(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
