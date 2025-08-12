<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\Schema;
use yii\db\Expression;

final class DateTimeColumn extends Column implements ColumnInterface
{
    /** @var array<string> Schemas using length for this column */
    private $lengthSchemas = [Schema::PGSQL];

    /**
     * Sets default value.
     * In case the expression value is incorrectly detected as string it's being corrected.
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        if (\is_string($default) && \preg_match('/^current_timestamp\([0-9]*\)$/i', $default)) {
            // https://github.com/yiisoft/yii2/issues/17744
            $default = new Expression($default);
        }
        parent::setDefault($default);
    }

    /**
     * Returns length of the column.
     * @return int|string|null
     */
    public function getLength(?string $schema = null, ?string $engineVersion = null)
    {
        return $this->isSchemaLengthSupporting($schema, $engineVersion) ? $this->getPrecision() : null;
    }

    /**
     * Sets length of the column.
     * @param string|int|null $value
     */
    public function setLength($value, ?string $schema = null, ?string $engineVersion = null): void
    {
        if ($this->isSchemaLengthSupporting($schema, $engineVersion)) {
            $this->setPrecision($value);
        }
    }

    /**
     * Checks if schema supports length for this column.
     * In case of MySQL the engine version must be 5.6.4 or newer.
     */
    private function isSchemaLengthSupporting(?string $schema, ?string $engineVersion): bool
    {
        if ($engineVersion && $schema === Schema::MYSQL && \version_compare($engineVersion, '5.6.4', '>=')) {
            return true;
        }

        return \in_array($schema, $this->lengthSchemas, true);
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'dateTime({renderLength})';
    }
}
