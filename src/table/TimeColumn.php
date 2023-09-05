<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

final class TimeColumn extends Column implements ColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [Schema::PGSQL];

    /**
     * Checks if schema supports length for this column.
     * In case of MySQL the engine version must be 5.6.4 or newer.
     */
    private function isSchemaLengthSupporting(?string $schema, ?string $engineVersion): bool
    {
        if ($engineVersion && $schema === Schema::MYSQL && \version_compare($engineVersion, '5.6.4', '>=')) {
            return true;
        }

        return \in_array($schema, $this->lengthSchemas, true);
    }

    /**
     * Returns length of the column.
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return $this->isSchemaLengthSupporting($schema, $engineVersion) ? $this->getPrecision() : null;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if ($this->isSchemaLengthSupporting($schema, $engineVersion)) {
            $this->setPrecision($value);
        }
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'time({renderLength})';
    }
}
