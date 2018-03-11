<?php

namespace bizley\migration\table;

use yii\base\Object;

class TablePrimaryKey extends Object
{
    const GENERIC_PRIMARY_KEY = 'PRIMARYKEY';

    /**
     * @var string
     */
    public $name;
    /**
     * @var array
     */
    public $columns = [];

    /**
     * Checks if primary key is composite.
     * @return bool
     */
    public function isComposite()
    {
        return count($this->columns) > 1;
    }

    /**
     * @param TableStructure $table
     * @return string
     */
    public function render($table)
    {
        return "\n        \$this->addPrimaryKey('"
            . ($this->name ?: self::GENERIC_PRIMARY_KEY)
            . "', '" . $table->renderName() . "', ['"
            . implode("', '", $this->columns)
            . "']);\n";
    }

    /**
     * @param $name
     */
    public function addColumn($name)
    {
        if (!in_array($name, $this->columns, true)) {
            $this->columns[] = $name;
        }
    }
}
