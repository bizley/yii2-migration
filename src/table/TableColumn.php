<?php

namespace bizley\migration\table;

use yii\base\Object;
use yii\db\Expression;
use yii\helpers\Json;

/**
 * Class TableColumn
 * @package bizley\migration\table
 *
 * @property-read int|string $length
 */
class TableColumn extends Object
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     * @since 2.9.0
     */
    public $defaultMapping;

    /**
     * @var bool|null
     */
    public $isNotNull;

    /**
     * @var int
     */
    public $size;

    /**
     * @var int
     */
    public $precision;

    /**
     * @var int
     */
    public $scale;

    /**
     * @var bool
     */
    public $isUnique = false;

    /**
     * @var bool
     */
    public $isUnsigned = false;

    /**
     * @var string
     */
    public $check;

    /**
     * @var mixed
     */
    public $default;

    /**
     * @var bool
     * Starting from 2.9.0 it's false by default.
     */
    public $isPrimaryKey = false;

    /**
     * @var bool
     * Starting from 2.9.0 it's false by default.
     */
    public $autoIncrement = false;

    /**
     * @var string
     */
    public $append;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var string
     * @since 2.4
     */
    public $schema;

    /**
     * Sets length of the column.
     * @param string|int $value
     */
    public function setLength($value) {}

    /**
     * Returns length of the column.
     * @return int|string|null
     */
    public function getLength()
    {
        return null;
    }

    protected function buildSpecificDefinition($table) {}

    protected $definition = [];
    protected $isUnsignedPossible = true;
    protected $isNotNullPossible = true;
    protected $isPkPossible = true;

    /**
     * Builds general methods chain for column definition.
     * @param TableStructure $table
     */
    protected function buildGeneralDefinition($table)
    {
        array_unshift($this->definition, '$this');

        if ($this->isUnsignedPossible && $this->isUnsigned) {
            $this->definition[] = 'unsigned()';
        }

        if ($this->isNotNullPossible && $this->isNotNull) {
            $this->definition[] = 'notNull()';
        }

        if ($this->default !== null) {
            if ($this->default instanceof Expression) {
                $this->definition[] = "defaultExpression('" . $this->escapeQuotes($this->default->expression) . "')";
            } elseif (is_array($this->default)) {
                $this->definition[] = "defaultValue('" . $this->escapeQuotes(Json::encode($this->default)) . "')";
            } else {
                $this->definition[] = "defaultValue('" . $this->escapeQuotes((string) $this->default) . "')";
            }
        }

        if ($this->isPkPossible && !$table->primaryKey->isComposite() && $this->isColumnInPK($table->primaryKey)) {
            $append = $this->prepareSchemaAppend(true, $this->autoIncrement);
            if (!empty($this->append)) {
                $append .= ' ' . $this->append;
            }
            $this->definition[] = "append('" . $this->escapeQuotes((string) $append) . "')";
        } elseif (!empty($this->append)) {
            $this->definition[] = "append('" . $this->escapeQuotes((string) $this->append) . "')";
        }

        if ($this->comment) {
            $this->definition[] = "comment('" . $this->escapeQuotes((string) $this->comment) . "')";
        }
    }

    /**
     * Renders column definition.
     * @param TableStructure $table
     * @return string
     */
    public function renderDefinition($table)
    {
        $this->buildSpecificDefinition($table);
        $this->buildGeneralDefinition($table);

        return implode('->', $this->definition);
    }

    /**
     * Renders the column.
     * @param TableStructure $table
     * @param int $indent
     * @return string
     */
    public function render($table, $indent = 12)
    {
        return str_repeat(' ', $indent) . "'{$this->name}' => " . $this->renderDefinition($table) . ',';
    }

    /**
     * Checks if column is a part of primary key.
     * @param TablePrimaryKey $pk
     * @return bool
     */
    public function isColumnInPK($pk)
    {
        return in_array($this->name, $pk->columns, true);
    }

    /**
     * Checks if information of primary key is set in append property.
     * @return bool
     */
    public function isColumnAppendPK()
    {
        if (empty($this->append)) {
            return false;
        }

        if ($this->schema === TableStructure::SCHEMA_MSSQL) {
            if (stripos($this->append, 'IDENTITY') !== false
                && stripos($this->append, 'PRIMARY KEY') !== false
            ) {
                return true;
            }
        } elseif (stripos($this->append, 'PRIMARY KEY') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Prepares append SQL based on schema.
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @return string|null
     */
    public function prepareSchemaAppend($primaryKey, $autoIncrement)
    {
        switch ($this->schema) {
            case TableStructure::SCHEMA_MSSQL:
                $append = $primaryKey ? 'IDENTITY PRIMARY KEY' : '';
                break;

            case TableStructure::SCHEMA_OCI:
            case TableStructure::SCHEMA_PGSQL:
                $append = $primaryKey ? 'PRIMARY KEY' : '';
                break;

            case TableStructure::SCHEMA_SQLITE:
                $append = trim(($primaryKey ? 'PRIMARY KEY ' : '') . ($autoIncrement ? 'AUTOINCREMENT' : ''));
                break;

            case TableStructure::SCHEMA_CUBRID:
            case TableStructure::SCHEMA_MYSQL:
            default:
                $append = trim(($autoIncrement ? 'AUTO_INCREMENT ' : '') . ($primaryKey ? 'PRIMARY KEY' : ''));
        }

        return empty($append) ? null : $append;
    }

    /**
     * Escapes single quotes.
     * @param string $value
     * @return mixed
     */
    public function escapeQuotes($value)
    {
        return str_replace('\'', '\\\'', $value);
    }

    /**
     * Removes information of primary key in append property.
     * @return null|string
     */
    public function removePKAppend()
    {
        if (!$this->isColumnAppendPK()) {
            return null;
        }

        $uppercaseAppend = preg_replace('/\s+/', ' ', mb_strtoupper($this->append, 'UTF-8'));

        switch ($this->schema) {
            case TableStructure::SCHEMA_MSSQL:
                $formattedAppend = str_replace(['PRIMARY KEY', 'IDENTITY'], '', $uppercaseAppend);
                break;

            case TableStructure::SCHEMA_OCI:
            case TableStructure::SCHEMA_PGSQL:
                $formattedAppend = str_replace('PRIMARY KEY', '', $uppercaseAppend);
                break;

            case TableStructure::SCHEMA_SQLITE:
                $formattedAppend = str_replace(['PRIMARY KEY', 'AUTOINCREMENT'], '', $uppercaseAppend);
                break;

            case TableStructure::SCHEMA_CUBRID:
            case TableStructure::SCHEMA_MYSQL:
            default:
                $formattedAppend = str_replace(['PRIMARY KEY', 'AUTO_INCREMENT'], '', $uppercaseAppend);
        }

        $formattedAppend = trim($formattedAppend);

        return !empty($formattedAppend) ? $formattedAppend : null;
    }

    /**
     * @param bool $generalSchema
     * @return string|null
     * @since 2.9.0
     */
    public function getRenderLength($generalSchema)
    {
        $length = $this->length;

        if ($length === null) {
            return $length;
        }

        if (!$generalSchema) {
            if ($length === 'max') {
                return '\'max\'';
            }

            return (string)$length;
        }

        if (str_replace(' ', '', (string)$length) !== $this->getDefaultLength()) {
            if ($length === 'max') {
                return '\'max\'';
            }

            return (string)$length;
        }

        return null;
    }

    private function getDefaultLength()
    {
        if ($this->defaultMapping !== null) {
            if (preg_match('/\(([\d,]+)\)/', $this->defaultMapping, $matches)) {
                return $matches[1];
            }

            if (preg_match('/\(max\)/', $this->defaultMapping)) {
                // MSSQL
                return 'max';
            }
        }

        return null;
    }
}
