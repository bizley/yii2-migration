<?php

namespace bizley\migration\table;

use yii\base\BaseObject;

/**
 * Class TableForeignKey
 * @package bizley\migration\table
 */
class TableForeignKey extends BaseObject
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var array
     */
    public $columns;
    /**
     * @var string
     */
    public $refTable;
    /**
     * @var array
     */
    public $refColumns;
    /**
     * @var string
     */
    public $onDelete;
    /**
     * @var string
     */
    public $onUpdate;

    /**
     * Renders key name.
     * @param TableStructure $table
     * @return string
     */
    public function renderName($table)
    {
        if ($this->name === null || is_numeric($this->name)) {
            return "fk-{$table->name}-" . implode('-', $this->columns);
        }
        return $this->name;
    }

    /**
     * Renders reference table name.
     * @param TableStructure $table
     * @return string
     */
    public function renderRefTableName($table)
    {
        $tableName = $this->refTable;
        if (!$table->usePrefix) {
            return $tableName;
        }
        if ($table->dbPrefix && strpos($this->refTable, $table->dbPrefix) === 0) {
            $tableName = substr($this->refTable, mb_strlen($table->dbPrefix, 'UTF-8'));
        }
        return '{{%' . $tableName . '}}';
    }

    /**
     * Renders the key.
     * @param TableStructure $table
     * @param int $indent
     * @return string
     */
    public function render($table, $indent = 8)
    {
        return str_repeat(' ', $indent) . '$this->addForeignKey(\'' . $this->renderName($table) . "', '" . $table->renderName() . "', "
            . (count($this->columns) === 1 ? "'{$this->columns[0]}'" : "['" . implode("', '", $this->columns) . "']")
            . ", '" . $this->renderRefTableName($table) . "', "
            . (count($this->refColumns) === 1 ? "'{$this->refColumns[0]}'" : "['" . implode("', '", $this->refColumns) . "']")
            . ($this->onDelete ? ", '{$this->onDelete}'" : '')
            . ($this->onUpdate ? ($this->onDelete === null ? ', null' : '') . ", '{$this->onUpdate}'" : '')
            . ');';
    }
}
