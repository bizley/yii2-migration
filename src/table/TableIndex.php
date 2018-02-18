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
}
