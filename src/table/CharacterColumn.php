<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class CharacterColumn extends Column implements ColumnInterface
{
    /**
     * Returns length of the column.
     * @return int|string|null
     */
    public function getLength(?string $schema = null, ?string $engineVersion = null)
    {
        return $this->getSize();
    }

    /**
     * Sets length of the column.
     * @param string|int|null $value
     */
    public function setLength($value, ?string $schema = null, ?string $engineVersion = null): void
    {
        $this->setSize($value);
        $this->setPrecision($value);
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'char({renderLength})';
    }
}
