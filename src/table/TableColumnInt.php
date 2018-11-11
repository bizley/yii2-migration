<?php declare(strict_types=1);

namespace bizley\migration\table;

/**
 * Class TableColumnInt
 * @package bizley\migration\table
 */
class TableColumnInt extends TableColumn
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
        if (\in_array($this->schema, [TableStructure::SCHEMA_MYSQL, TableStructure::SCHEMA_OCI], true)) {
            $this->size = $value;
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        if ($table->generalSchema && !$table->primaryKey->isComposite() && $this->isColumnInPK($table->primaryKey)) {
            $this->isPkPossible = false;
            $this->isNotNullPossible = false;
            $this->definition[] = 'primaryKey()';
        } else {
            $this->definition[] = 'integer(' . ($table->generalSchema ? null : $this->length) . ')';
        }
    }
}
