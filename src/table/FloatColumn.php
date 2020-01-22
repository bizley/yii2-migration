<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function in_array;

class FloatColumn extends Column
{
    /** @var array Schemas using length for this column */
    private $lengthSchemas = [Structure::SCHEMA_CUBRID];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return in_array($this->schema, $this->lengthSchemas, true) ? $this->precision : null;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value): void
    {
        if (in_array($this->schema, $this->lengthSchemas, true)) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    protected function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'float(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
