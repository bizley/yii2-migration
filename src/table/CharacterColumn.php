<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class CharacterColumn extends Column implements ColumnInterface
{
    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return $this->getSize();
    }

    /**
     * @param string|int $value
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
        return 'char({renderLength})';
    }
}
