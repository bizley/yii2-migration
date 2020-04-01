<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class DateColumn extends Column implements ColumnInterface
{
    /**
     * Sets length of the column.
     * @param mixed $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
    }

    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|null
     */
    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return null;
    }

    public function getDefinition(): string
    {
        return 'date()';
    }
}
