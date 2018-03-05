<?php

namespace bizley\migration\table;

class TableColumnTimestamp extends TableColumn
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
        $this->definition[] = 'timestamp(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
