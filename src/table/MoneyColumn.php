<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function is_array;
use function preg_split;

final class MoneyColumn extends Column implements ColumnInterface
{
    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        $scale = $this->getScale();
        return $this->getPrecision() . ($scale !== null ? ', ' . $scale : null);
    }

    /**
     * Sets length of the column.
     * @param string|int|array<string|int> $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (is_array($value)) {
            $length = $value;
        } else {
            /** @var array<string|int> $length */
            $length = preg_split('/\s*,\s*/', (string)$value);
        }

        if (isset($length[0]) && !empty($length[0])) {
            $this->setPrecision((int)$length[0]);
        } else {
            $this->setPrecision(null);
        }

        if (isset($length[1]) && !empty($length[1])) {
            $this->setScale((int)$length[1]);
        } else {
            $this->setScale(null);
        }
    }

    public function getDefinition(): string
    {
        return 'money({renderLength})';
    }
}
