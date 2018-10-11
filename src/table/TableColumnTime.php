<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnTime
 * @package bizley\migration\table
 */
class TableColumnTime extends TableColumn
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
        $this->precision = $value;
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'time(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
