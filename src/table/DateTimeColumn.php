<?php

declare(strict_types=1);

namespace bizley\migration\table;

use bizley\migration\SchemaEnum;
use yii\db\Expression;

use function in_array;
use function is_string;
use function preg_match;
use function version_compare;

class DateTimeColumn extends Column implements ColumnInterface
{
    /**
     * @var array Schemas using length for this column
     */
    private $lengthSchemas = [SchemaEnum::PGSQL];

    public function __construct()
    {
        $default = $this->getDefault();
        if (is_string($default) && preg_match('/^current_timestamp\([0-9]*\)$/i', $default)) {
            // https://github.com/yiisoft/yii2/issues/17744
            $this->setDefault(new Expression($default));
        }
    }

    /**
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return int|null
     */
    public function getLength(string $schema = null, string $engineVersion = null)
    {
        return $this->isSchemaLengthSupporting($schema, $engineVersion) ? $this->getPrecision() : null;
    }

    /**
     * @param $value
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
        if ($engineVersion && $schema === SchemaEnum::MYSQL && version_compare($engineVersion, '5.6.4', '>=')) {
            return true;
        }

        return in_array($schema, $this->lengthSchemas, true);
    }

    public function getDefinition(): string
    {
        return 'dateTime({renderLength})';
    }
}
