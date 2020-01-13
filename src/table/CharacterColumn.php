<?php

declare(strict_types=1);

namespace bizley\migration\table;

class CharacterColumn extends Column
{
    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->size;
    }

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value): void
    {
        $this->size = $value;
        $this->precision = $value;
    }

    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    public function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'char(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
