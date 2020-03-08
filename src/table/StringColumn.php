<?php

declare(strict_types=1);

namespace bizley\migration\table;

class StringColumn extends Column implements ColumnInterface
{
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return $this->getSize();
    }

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
