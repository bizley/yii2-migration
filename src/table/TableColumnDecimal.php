<?php

namespace bizley\migration\table;

/**
 * Class TableColumnDecimal
 * @package bizley\migration\table
 */
class TableColumnDecimal extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 2.4
     */
    public $lengthSchemas = [
        TableStructure::SCHEMA_MYSQL,
        TableStructure::SCHEMA_CUBRID,
        TableStructure::SCHEMA_PGSQL,
        TableStructure::SCHEMA_SQLITE,
        TableStructure::SCHEMA_MSSQL,
    ];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return in_array($this->schema, $this->lengthSchemas, true)
            ? ($this->precision . (($this->scale || (int)$this->scale === 0) ? ', ' . $this->scale : null))
            : null;
    }

    /**
     * Sets length of the column.
     * @param array|int|string $value
     */
    public function setLength($value)
    {
        if (in_array($this->schema, $this->lengthSchemas, true)) {
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
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'decimal(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
