<?php

namespace bizley\migration\table;

use yii\base\Object;

class TableChange extends Object
{
    /**
     * @var string
     */
    public $table;
    /**
     * @var string
     */
    public $method;
    /**
     * @var array|string
     */
    public $data;
}
