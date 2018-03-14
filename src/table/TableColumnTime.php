<?php

namespace bizley\migration\table;

class TableColumnTime extends TableColumn
{
    /**
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision;
    }

    /**
     * @param $value
     */
    public function setLength($value)
    {
        $this->precision = $value;
    }

    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'time(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
