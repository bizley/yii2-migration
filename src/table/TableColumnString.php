<?php declare(strict_types=1);

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
    public function setLength($value): void
    {
        $this->size = $value;
        $this->precision = $value;
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'string(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
