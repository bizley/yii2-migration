<?php

namespace bizley\migration\table;

use yii\base\Object;

class TableForeignKey extends Object
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
     * @param TableStructure $table
     * @return string
     */
    public function renderName($table)
    {
        if ($this->name === null) {
            return "fk-{$table->name}-" . implode('-', $this->columns);
        }
        return $this->name;
    }

    /**
     * @param TableStructure $table
     * @return string
     */
    public function renderRefTableName($table)
    {
        if (!$table->usePrefix) {
            return $this->refTable;
        }
        return '{{%' . $this->refTable . '}}';
    }

    /**
     * @param TableStructure $table
     * @return string
     */
    public function render($table)
    {
        return '        $this->addForeignKey(\'' . $this->renderName($table) . "', '" . $table->renderName() . "', "
            . (count($this->columns) === 1 ? "'{$this->columns[0]}'" : "['" . implode("', '", $this->columns) . "']")
            . ", '" . $this->renderRefTableName($table) . "', "
            . (count($this->refColumns) === 1 ? "'{$this->refColumns[0]}'" : "['" . implode("', '", $this->refColumns) . "']")
            . ($this->onDelete ? ", '{$this->onDelete}'" : '')
            . ($this->onUpdate ? ($this->onDelete === null ? ', null' : '') . ", '{$this->onUpdate}'" : '')
            . ");\n";
    }
}
