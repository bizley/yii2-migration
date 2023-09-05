<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class DateColumn extends Column implements ColumnInterface
{
    /**
     * Sets length of the column.
     * @param mixed $value
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
    }

    /**
     * Returns length of the column.
     */
    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return null;
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'date()';
    }
}
