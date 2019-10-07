<?php

namespace bizley\migration\table;

/**
 * Class TableColumnString
 * @package bizley\migration\table
 */
class TableColumnString extends TableColumn
{
    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->size;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value)
    {
        $this->size = $value;
        $this->precision = $value;
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'string(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
