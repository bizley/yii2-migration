<?php

namespace bizley\migration\table;

class TableColumnDecimal extends TableColumn
{
    /**
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision . ($this->scale ? ', ' . $this->scale : null);
    }

    /**
     * @param $value
     */
    public function setLength($value)
    {
        $length = preg_split('\s*,\s*', $value);
        $this->precision = $length[0];
        if (isset($length[1]) && !empty($length[1])) {
            $this->scale = $length[1];
        }
    }

    /**
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'decimal(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
