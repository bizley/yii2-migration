<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function is_array;
use function preg_split;

class OldMoneyColumn extends Column
{
    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->precision . ($this->scale !== null ? ', ' . $this->scale : null);
    }

    /**
     * Sets length of the column.
     * @param array|string|int $value
     */
    public function setLength($value): void
    {
        $length = is_array($value) ? $value : preg_split('/\s*,\s*/', (string)$value);

        if (isset($length[0]) && !empty($length[0])) {
            $this->precision = $length[0];
        } else {
            $this->precision = 0;
        }

        if (isset($length[1]) && !empty($length[1])) {
            $this->scale = $length[1];
        } else {
            $this->scale = 0;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param Structure $table
     */
    protected function buildSpecificDefinition(Structure $table): void
    {
        $this->definition[] = 'money(' . $this->getRenderLength($table->generalSchema) . ')';
    }
}
