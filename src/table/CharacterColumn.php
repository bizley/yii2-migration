<?php

declare(strict_types=1);

namespace bizley\migration\table;

class CharacterColumn extends Column implements ColumnInterface
{
    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|string
     */
    public function getLength(string $schema = null, string $engineVersion = null)
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
