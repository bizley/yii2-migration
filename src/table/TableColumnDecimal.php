<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnDecimal
 * @package bizley\migration\table
 */
class TableColumnDecimal extends TableColumn
{
    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision . ($this->scale ? ', ' . $this->scale : null);
    }

    /**
     * Sets length of the column.
     * @param array|string|int $value
     */
    public function setLength($value): void
    {
        if (\in_array(
            $this->schema,
            [
                TableStructure::SCHEMA_MYSQL,
                TableStructure::SCHEMA_CUBRID,
                TableStructure::SCHEMA_PGSQL,
                TableStructure::SCHEMA_SQLITE,
                TableStructure::SCHEMA_MSSQL,
            ],
            true
        )) {
            if (\is_array($value)) {
                $length = $value;
            } else {
                $length = preg_split('\s*,\s*', $value);
            }
            if (isset($length[0]) && !empty($length[0])) {
                $this->precision = $length[0];
            }
            if (isset($length[1]) && !empty($length[1])) {
                $this->scale = $length[1];
            }
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'decimal(' . ($table->generalSchema ? null : $this->length) . ')';
    }
}
