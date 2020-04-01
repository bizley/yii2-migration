<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidArgumentException;
use yii\helpers\Json;

use function is_array;

final class JsonColumn extends Column implements ColumnInterface
{
    public function setDefault($default): void
    {
        if ($default !== '' && $default !== null && !is_array($default)) {
            try {
                $defaultArray = Json::decode($default);
                if (is_array($defaultArray)) {
                    parent::setDefault($defaultArray);
                    return;
                }
            } catch (InvalidArgumentException $exception) {
            }
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
     * @param string|int $value
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
