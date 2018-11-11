<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnTimestamp
 * @package bizley\migration\table
 */
class TableColumnTimestamp extends TableColumn
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
     * @param string|int $value
     */
    public function setLength($value): void
    {
        if ($this->schema === TableStructure::SCHEMA_PGSQL) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'timestamp(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
