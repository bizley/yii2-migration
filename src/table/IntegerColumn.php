<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

use function in_array;

final class IntegerColumn extends Column implements PrimaryKeyVariantColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [
        Schema::MYSQL,
        Schema::OCI,
    ];

    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return in_array($schema, $this->lengthSchemas, true) ? $this->getSize() : null;
    }

    /**
     * @param string|int $value
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

    public function getDefinition(): string
    {
        return 'integer({renderLength})';
    }

    public function getPrimaryKeyDefinition(): string
    {
        return 'primaryKey({renderLength})';
    }
}
