<?php

namespace bizley\migration\table;

use yii\base\Object;

class TablePrimaryKey extends Object
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var array
     */
    public $columns;
}
