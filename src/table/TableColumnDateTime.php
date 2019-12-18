<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\db\Expression;

use function in_array;
use function is_string;
use function preg_match;
use function version_compare;

/**
 * Class TableColumnDateTime
 * @package bizley\migration\table
 */
class TableColumnDateTime extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 3.1
     */
    public $lengthSchemas = [TableStructure::SCHEMA_PGSQL];

    public function init(): void
    {
        parent::init();

        if (is_string($this->default) && preg_match('/^current_timestamp\([0-9]*\)$/i', $this->default)) {
            // https://github.com/yiisoft/yii2/issues/17744
            $this->default = new Expression($this->default);
        }
    }

    /**
     * Returns length of the column.
     * @return int|string
     */
    public function getLength()
    {
        return $this->isSchemaLengthSupporting() ? $this->precision : null;
    }

    /**
     * Sets length of the column.
     * @param int|string $value
     */
    public function setLength($value): void
    {
        if ($this->isSchemaLengthSupporting()) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition(TableStructure $table): void
    {
        $this->definition[] = 'dateTime(' . $this->getRenderLength($table->generalSchema) . ')';
    }

    private function isSchemaLengthSupporting(): bool
    {
        if (
            $this->engineVersion
            && $this->schema === TableStructure::SCHEMA_MYSQL
            && version_compare($this->engineVersion, '5.6.4', '>=')
        ) {
            return true;
        }

        return in_array($this->schema, $this->lengthSchemas, true);
    }
}
