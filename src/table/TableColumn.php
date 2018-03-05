<?php

namespace bizley\migration\table;

use yii\base\Object;
use yii\db\Expression;

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
     */
    public $isPrimaryKey;
    /**
     * @var bool
     */
    public $autoIncrement;
    /**
     * @var string
     */
    public $append;
    /**
     * @var string
     */
    public $comment;

    /**
     * @return int|string
     */
    public function getLength()
    {
        return $this->size;
    }

    protected function buildSpecificDefinition($table) {}

    protected $definition = [];
    protected $isUnsignedPossible = true;
    protected $isNotNullPossible = true;
    protected $isPkPossible = true;

    /**
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
                $this->definition[] = "defaultExpression('{$this->default->expression}')";
            } else {
                $this->definition[] = "defaultValue('{$this->default}')";
            }
        }
        // TODO dodac append dodatkowy
        if ($this->isPkPossible && !$table->primaryKey->isComposite() && $this->isColumnInPK($table->primaryKey)) {
            $this->definition[] = "append('" . $this->prepareSchemaAppend($table, true, $this->autoIncrement) . "')";
        }
        if ($this->comment) {
            $this->definition[] = "comment('{$this->comment}')";
        }
    }

    /**
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
     * @param TableStructure $table
     * @return string
     */
    public function render($table)
    {
        return "            '{$this->name}' => " . $this->renderDefinition($table) . ",\n";
    }

    /**
     * @param TablePrimaryKey $pk
     * @return bool
     */
    public function isColumnInPK($pk)
    {
        return in_array($this->name, $pk->columns, true);
    }

    /**
     * Prepares append SQL based on schema.
     * @param TableStructure $table
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @return string
     */
    public function prepareSchemaAppend($table, $primaryKey, $autoIncrement)
    {
        switch ($table->schema) {
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
}
