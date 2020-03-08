<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\SchemaEnum;

use function in_array;

class FloatColumn extends Column implements ColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [SchemaEnum::CUBRID];

    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return in_array($schema, $this->lengthSchemas, true) ? $this->getPrecision() : null;
    }

    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (in_array($schema, $this->lengthSchemas, true)) {
            $this->setPrecision($value);
        }
    }

    public function getDefinition(): string
    {
        return 'float({renderLength})';
    }
}
