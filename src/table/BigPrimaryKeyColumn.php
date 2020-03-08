<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\SchemaEnum;

use function in_array;

class BigPrimaryKeyColumn extends Column implements ColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [
        SchemaEnum::MYSQL,
        SchemaEnum::OCI,
    ];

    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|string
     */
    public function getLength(string $schema = null, string $engineVersion = null)
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
        return 'bigPrimaryKey({renderLength})';
    }
}
