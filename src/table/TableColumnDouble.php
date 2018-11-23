<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnDouble
 * @package bizley\migration\table
 */
class TableColumnDouble extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 3.1
     */
    public $lengthSchemas = [TableStructure::SCHEMA_CUBRID];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return \in_array($this->schema, $this->lengthSchemas, true) ? $this->precision : null;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value): void
    {
        if (\in_array($this->schema, $this->lengthSchemas, true)) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'double(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
