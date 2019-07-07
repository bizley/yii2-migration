<?php

namespace bizley\migration\table;

use yii\base\Object;

/**
 * Class TableIndex
 * @package bizley\migration\table
 */
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
     * Renders the index.
     * @param TableStructure $table
     * @param int $indent
     * @return string
     */
    public function render($table, $indent = 8)
    {
        return str_repeat(' ', $indent) . sprintf(
            '$this->createIndex(\'%s\', \'%s\', %s%s);',
            $this->name,
            $table->renderName(),
            count($this->columns) === 1 ? "'{$this->columns[0]}'" : "['" . implode("', '", $this->columns) . "']",
            $this->unique ? ', true' : ''
        );
    }
}
