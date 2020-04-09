<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\helpers\Json;

use function is_array;

final class JsonColumn extends Column implements ColumnInterface
{
    /**
     * Sets default value.
     * In case the value is an array it's being JSON-encoded.
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        if (is_array($default)) {
            $default = Json::encode($default);
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
        return null;
    }

    /**
     * Sets length of the column.
     * @param mixed $value
     * @param string|null $schema
     * @param string|null $engineVersion
     */
    public function setLength($value, string $schema = null, string $engineVersion = null): void
    {
    }

    /**
     * Returns default column definition.
     * @return string
     */
    public function getDefinition(): string
    {
        return 'json()';
    }
}
