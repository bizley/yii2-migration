<?php

namespace bizley\migration\table;

/**
 * Class TableColumnBinary
 * @package bizley\migration\table
 */
class TableColumnBinary extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 2.4
     */
    public $lengthSchemas = [TableStructure::SCHEMA_MSSQL];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return in_array($this->schema, $this->lengthSchemas, true) ? $this->size : null;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value)
    {
        if (in_array($this->schema, $this->lengthSchemas, true)) {
            $this->size = $value;
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'binary(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}