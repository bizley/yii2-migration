<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class DateColumn extends Column implements ColumnInterface
{
    /**
     * @param mixed $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
    }

    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return null;
    }

    public function getDefinition(): string
    {
        return 'date()';
    }
}
