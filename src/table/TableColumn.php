<?php

namespace bizley\migration\table;

use yii\base\Object;

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
     * @var bool
     */
    public $isNotNull;
    /**
     * @var string
     */
    public $length;

// 'isNotNull', 'isUnique', 'check', 'default', 'append', 'isUnsigned'
}
