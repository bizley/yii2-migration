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
}
