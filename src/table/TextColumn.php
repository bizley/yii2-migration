<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function in_array;

class TextColumn extends Column
{
    /** @var array Schemas using length for this column */
    public $lengthSchemas = [Structure::SCHEMA_MSSQL];

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
    public function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'text(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
