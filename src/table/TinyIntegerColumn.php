<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\SchemaEnum;

use function in_array;

final class TinyIntegerColumn extends Column implements ColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [
        SchemaEnum::MYSQL,
        SchemaEnum::OCI,
    ];

    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return in_array($schema, $this->lengthSchemas, true) ? $this->getSize() : null;
    }

    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (in_array($schema, $this->lengthSchemas, true)) {
            $this->setSize($value);
            $this->setPrecision($value);
        }
    }

    public function getDefinition(): string
    {
        return 'tinyInteger({renderLength})';
    }
}
