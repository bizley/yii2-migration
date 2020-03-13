<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;

use function in_array;
use function is_array;
use function preg_split;

final class DecimalColumn extends Column implements ColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [
        Schema::MYSQL,
        Schema::CUBRID,
        Schema::PGSQL,
        Schema::SQLITE,
        Schema::MSSQL,
    ];

    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return string|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        if (in_array($schema, $this->lengthSchemas, true) === false) {
            return null;
        }

        $scale = $this->getScale();
        return $this->getPrecision() . ($scale !== null ? ', ' . $scale : null);
    }

    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if (in_array($schema, $this->lengthSchemas, true)) {
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
    }

    public function getDefinition(): string
    {
        return 'decimal({renderLength})';
    }
}
