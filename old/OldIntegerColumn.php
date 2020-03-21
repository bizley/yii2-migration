<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function in_array;

class OldIntegerColumn extends Column
{
    /** @var array Schemas using length for this column */
    private $lengthSchemas = [Structure::SCHEMA_MYSQL, Structure::SCHEMA_OCI];

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
    public function setLength($value): void
    {
        if (in_array($this->schema, $this->lengthSchemas, true)) {
            $this->size = $value;
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    protected function buildSpecificDefinition(Structure $table): void
    {
        if ($table->generalSchema && !$table->primaryKey->isComposite() && $this->isColumnInPrimaryKey($table->primaryKey)) {
            $this->isPkPossible = false;
            $this->isNotNullPossible = false;
            $this->definition[] = 'primaryKey(' . $this->getRenderLength($table->generalSchema) . ')';
        } else {
            $this->definition[] = 'integer(' . $this->getRenderLength($table->generalSchema) . ')';
        }
    }
}