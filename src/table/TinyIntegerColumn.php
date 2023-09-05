<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

final class TinyIntegerColumn extends Column implements ColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [Schema::OCI];

    /**
     * Checks if schema supports length for this column.
     * In case of MySQL the engine version must be lower than 8.0.17.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return bool
     */
    private function isSchemaLengthSupporting(?string $schema, ?string $engineVersion): bool
    {
        if ($engineVersion && $schema === Schema::MYSQL && \version_compare($engineVersion, '8.0.17', '<')) {
            return true;
        }

        return \in_array($schema, $this->lengthSchemas, true);
    }

    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        $size = $this->getSize();
        if ($this->isSchemaLengthSupporting($schema, $engineVersion)) {
            return $size;
        }
        if ($schema === Schema::MYSQL && (string)$size === '1') {
            // MySQL 8.0.17+ allows tiny integer to be set with size 1 for boolean columns
            return $size;
        }

        return null;
    }

    /**
     * Sets length of the column.
     * @param string|int|null $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (
            ($schema === Schema::MYSQL && (string)$value === '1')
            || $this->isSchemaLengthSupporting($schema, $engineVersion)
        ) {
            $this->setSize($value);
            $this->setPrecision($value);
        }
    }

    /**
     * Returns default column definition.
     * @return string
     */
    public function getDefinition(): string
    {
        return 'tinyInteger({renderLength})';
    }
}
