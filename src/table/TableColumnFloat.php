<?php

namespace bizley\migration\table;

/**
 * Class TableColumnFloat
 * @package bizley\migration\table
 */
class TableColumnFloat extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 2.4
     */
    public $lengthSchemas = [TableStructure::SCHEMA_CUBRID];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return in_array($this->schema, $this->lengthSchemas, true) ? $this->precision : null;
    }

    /**
     * Sets length of the column.
     * @param int|string $value
     */
    public function setLength($value)
    {
        if (in_array($this->schema, $this->lengthSchemas, true)) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'float(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
