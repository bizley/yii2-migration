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
    /**
     * @var string
     */
    public $isUnique;
    /**
     * @var string
     */
    public $check;
    /**
     * @var string
     */
    public $default;
    /**
     * @var string
     */
    public $append;
    /**
     * @var string
     */
    public $isUnsigned;
    /**
     * @var string
     */
    public $comment;
}
