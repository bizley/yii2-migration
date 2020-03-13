<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function in_array;

class OldBigIntegerColumn extends Column implements PrimaryKeyVariantColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [
        Structure::SCHEMA_MYSQL,
        Structure::SCHEMA_OCI,
    ];

    /**
     * @param string $schema
     * @return int|string
     */
    public function getLength(string $schema)
    {
        return in_array($schema, $this->lengthSchemas, true) ? $this->getSize() : null;
    }

    /**
     * @param string $schema
     * @param string|int $value
     */
    public function setLength(string $schema, $value): void
    {
        if (in_array($schema, $this->lengthSchemas, true)) {
            $this->setSize($value);
            $this->setPrecision($value);
        }
    }

    public function getDefinition(): string
    {
        return 'bigInteger({renderLength})';
    }

    public function getPrimaryKeyDefinition(): string
    {
        return 'bigPrimaryKey({renderLength})';
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
            $this->definition[] = 'bigPrimaryKey(' . $this->getRenderLength($table->generalSchema) . ')';
        } else {
            $this->definition[] = 'bigInteger(' . $this->getRenderLength($table->generalSchema) . ')';
        }
    }
}
