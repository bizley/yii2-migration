<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\SchemaEnum;

use function in_array;
use function version_compare;

final class TimeColumn extends Column implements ColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [SchemaEnum::PGSQL];

    private function isSchemaLengthSupporting(?string $schema, ?string $engineVersion): bool
    {
        if ($engineVersion && $schema === SchemaEnum::MYSQL && version_compare($engineVersion, '5.6.4', '>=')) {
            return true;
        }

        return in_array($schema, $this->lengthSchemas, true);
    }

    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return $this->isSchemaLengthSupporting($schema, $engineVersion) ? $this->getPrecision() : null;
    }

    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if ($this->isSchemaLengthSupporting($schema, $engineVersion)) {
            $this->setPrecision($value);
        }
    }

    public function getDefinition(): string
    {
        return 'time({renderLength})';
    }
}
