<?php

namespace bizley\migration\table;

use yii\base\Object;

class TableStructure extends Object
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var TablePrimaryKey
     */
    public $primaryKey;
    /**
     * @var TableColumn[]
     */
    public $columns = [];
    /**
     * @var TableIndex[]
     */
    public $indexes = [];
    /**
     * @var TableForeignKey[]
     */
    public $foreignKeys = [];
}
