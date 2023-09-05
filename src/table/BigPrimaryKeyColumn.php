<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

class BigPrimaryKeyColumn extends Column implements PrimaryKeyColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [Schema::OCI];

    /**
     * Checks if schema supports length for this column.
     * In case of MySQL the engine version must be lower than 8.0.17.
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
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return $this->isSchemaLengthSupporting($schema, $engineVersion) ? $this->getSize() : null;
    }

    /**
     * Sets length of the column.
     * @param string|int|null $value
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if ($this->isSchemaLengthSupporting($schema, $engineVersion)) {
            $this->setSize($value);
            $this->setPrecision($value);
        }
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'bigPrimaryKey({renderLength})';
    }
}
