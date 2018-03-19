<?php

namespace bizley\migration\table;

use yii\base\Object;

class TableIndex extends Object
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var bool
     */
    public $unique = false;
    /**
     * @var array
     */
    public $columns = [];

    /**
     * @param TableStructure $table
     * @return string
     */
    public function render($table)
    {
        return "        \$this->createIndex('{$this->name}', '" . $table->renderName() . "', "
            . (count($this->columns) === 1 ? "'{$this->columns[0]}'" : "['" . implode("', '", $this->columns) . "']")
            . ($this->unique ? ', true' : '') . ");\n";
    }
}
