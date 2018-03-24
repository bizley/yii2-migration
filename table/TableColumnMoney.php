<?php

namespace bizley\migration\table;

/**
 * Class TableColumnMoney
 * @package bizley\migration\table
 */
class TableColumnMoney extends TableColumn
{
    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision . ($this->scale ? ', ' . $this->scale : null);
    }

    /**
     * Sets length of the column.
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
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'money(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
