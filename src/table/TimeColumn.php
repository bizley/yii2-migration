<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function in_array;
use function version_compare;

class TimeColumn extends Column
{
    /** @var array Schemas using length for this column */
    public $lengthSchemas = [Structure::SCHEMA_PGSQL];

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->isSchemaLengthSupporting() ? $this->precision : null;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value): void
    {
        if ($this->isSchemaLengthSupporting()) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    public function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'time(' . $this->getRenderLength($table->generalSchema) . ')';
    }

    private function isSchemaLengthSupporting(): bool
    {
        if (
            $this->engineVersion
            && $this->schema === Structure::SCHEMA_MYSQL
            && version_compare($this->engineVersion, '5.6.4', '>=')
        ) {
            return true;
        }

        return in_array($this->schema, $this->lengthSchemas, true);
    }
}
