<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\helpers\Json;

final class JsonColumn extends Column implements ColumnInterface
{
    /**
     * Sets default value.
     * In case the value is an array it's being JSON-encoded.
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        if (\is_array($default)) {
            $default = Json::encode($default);
        }
        parent::setDefault($default);
    }

    /**
     * Returns length of the column.
     */
    public function getLength(string $schema = null, string $engineVersion = null): ?int
    {
        return null;
    }

    /**
     * Sets length of the column.
     * @param mixed $value
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
    }

    /**
     * Returns default column definition.
     */
    public function getDefinition(): string
    {
        return 'json()';
    }
}
