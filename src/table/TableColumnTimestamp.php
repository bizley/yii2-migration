<?php

namespace bizley\migration\table;

use yii\db\Expression;

/**
 * Class TableColumnTimestamp
 * @package bizley\migration\table
 */
class TableColumnTimestamp extends TableColumn
{
    /**
     * @var array Schemas using length for this column
     * @since 2.4
     */
    public $lengthSchemas = [TableStructure::SCHEMA_PGSQL];

    public function init()
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
    public function setLength($value)
    {
        if ($this->isSchemaLengthSupporting()) {
            $this->precision = $value;
        }
    }

    /**
     * Builds methods chain for column definition.
     * @param TableStructure $table
     */
    public function buildSpecificDefinition($table)
    {
        $this->definition[] = 'timestamp(' . $this->getRenderLength($table->generalSchema) . ')';
    }

    private function isSchemaLengthSupporting()
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
