<?php

namespace bizley\migration\table;

/**
 * Class TableColumnTime
 * @package bizley\migration\table
 */
class TableColumnTime extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 2.4
     */
    public $lengthSchemas = [TableStructure::SCHEMA_PGSQL];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->isSchemaLengthSupporting() ? $this->precision : null;
    }

    /**
     * Sets length of the column.
     * @param int|string $value
     */
    public function setLength($value)
    {
        if ($this->isSchemaLengthSupporting()) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'time(' . $this->getRenderLength($table->generalSchema) . ')';
    }

    private function isSchemaLengthSupporting()
    {
        if (
            $this->engineVersion
            && $this->schema === TableStructure::SCHEMA_MYSQL
            && version_compare($this->engineVersion, '5.6.4', '>=')
        ) {
            return true;
        }

        return in_array($this->schema, $this->lengthSchemas, true);
    }
}