<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;
use yii\db\Expression;

use function in_array;
use function is_string;
use function preg_match;
use function version_compare;

final class DateTimeColumn extends Column implements ColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [Schema::PGSQL];

    public function setDefault($default): void
    {
        if (is_string($default) && preg_match('/^current_timestamp\([0-9]*\)$/i', $default)) {
            // https://github.com/yiisoft/yii2/issues/17744
            $default = new Expression($default);
        }
        parent::setDefault($default);
    }

    /**
     * Returns length of the column.
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|null
     */
    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return $this->isSchemaLengthSupporting($schema, $engineVersion) ? $this->getPrecision() : null;
    }

    /**
     * @param string|int $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
        if ($this->isSchemaLengthSupporting($schema, $engineVersion)) {
            $this->setPrecision($value);
        }
    }

    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return bool
     */
    private function isSchemaLengthSupporting(?string $schema, ?string $engineVersion): bool
    {
        if ($engineVersion && $schema === Schema::MYSQL && version_compare($engineVersion, '5.6.4', '>=')) {
            return true;
        }

        return in_array($schema, $this->lengthSchemas, true);
    }

    public function getDefinition(): string
    {
        return 'dateTime({renderLength})';
    }
}
