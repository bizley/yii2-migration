<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

use function in_array;

final class BinaryColumn extends Column implements ColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [Schema::MSSQL];

    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return in_array($schema, $this->lengthSchemas, true) ? $this->getSize() : null;
    }

    /**
     * Sets length of the column.
     * @param string|int|null $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (in_array($schema, $this->lengthSchemas, true)) {
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
        return 'binary({renderLength})';
    }
}
