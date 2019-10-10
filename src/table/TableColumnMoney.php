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
        return $this->precision . ($this->scale !== null ? ', ' . $this->scale : null);
    }

    /**
     * Sets length of the column.
     * @param array|int|string $value
     */
    public function setLength($value)
    {
        $length = is_array($value) ? $value : preg_split('/\s*,\s*/', $value);

        if (isset($length[0]) && !empty($length[0])) {
            $this->precision = $length[0];
        } else {
            $this->precision = 0;
        }

        if (isset($length[1]) && !empty($length[1])) {
            $this->scale = $length[1];
        } else {
            $this->scale = 0;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'money(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
