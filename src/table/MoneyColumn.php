<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function is_array;
use function preg_split;

class MoneyColumn extends Column implements ColumnInterface
{
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        $scale = $this->getScale();
        return $this->getPrecision() . ($scale !== null ? ', ' . $scale : null);
    }

    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        $length = is_array($value) ? $value : preg_split('/\s*,\s*/', (string)$value);

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
