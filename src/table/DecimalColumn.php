<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

final class DecimalColumn extends Column implements ColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [
        Schema::MYSQL,
        Schema::CUBRID,
        Schema::PGSQL,
        Schema::SQLITE,
        Schema::MSSQL,
    ];

    /**
     * Returns length of the column.
     * @return int|string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        if (!\in_array($schema, $this->lengthSchemas, true)) {
            return null;
        }

        $scale = $this->getScale();
        return $this->getPrecision() . ($scale !== null ? ', ' . $scale : null);
    }

    /**
     * Sets length of the column.
     * @param string|int|array<string|int>|null $value
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (\in_array($schema, $this->lengthSchemas, true)) {
            if (\is_array($value)) {
                $length = $value;
            } else {
                /** @var array<string|int> $length */
                $length = \preg_split('/\s*,\s*/', (string)$value);
            }

            if (!empty($length[0])) {
                $this->setPrecision((int)$length[0]);
            } else {
                $this->setPrecision(null);
            }

            if (!empty($length[1])) {
                $this->setScale((int)$length[1]);
            } else {
                $this->setScale(null);
            }
        }
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'decimal({renderLength})';
    }
}
