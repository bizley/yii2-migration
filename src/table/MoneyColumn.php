<?php

declare(strict_types=1);

namespace bizley\migration\table;

use RuntimeException;

use function is_array;
use function preg_split;

final class MoneyColumn extends Column implements ColumnInterface
{
    /**
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
     * @param string|int|array<string|int> $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (is_array($value)) {
            $length = $value;
        } else {
            $length = preg_split('/\s*,\s*/', (string)$value);
            if ($length === false) {
                throw new RuntimeException('Error while splitting length value!');
            }
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
