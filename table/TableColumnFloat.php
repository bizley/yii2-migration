<?php

namespace bizley\migration\table;

/**
 * Class TableColumnFloat
 * @package bizley\migration\table
 */
class TableColumnFloat extends TableColumn
{
    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision;
    }

    /**
     * Sets length of the column.
     * @param $value
     */
    public function setLength($value): void
    {
        $this->precision = $value;
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table): void
    {
        $this->definition[] = 'float(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
