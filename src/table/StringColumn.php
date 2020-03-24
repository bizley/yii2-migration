<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class StringColumn extends Column implements ColumnInterface
{
    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return $this->getSize();
    }

    /**
     * @param string|null $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        $this->setSize($value);
        $this->setPrecision($value);
    }

    public function getDefinition(): string
    {
        return 'string({renderLength})';
    }
}
