<?php

namespace bizley\migration\table;

use yii\base\Object;
use yii\db\Schema;

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
     * @var bool|null
     */
    public $isNotNull;
    /**
     * @var int|string|array
     */
    public $length;
    /**
     * @var bool
     */
    public $isUnique = false;
    /**
     * @var bool
     */
    public $isUnsigned = false;
    /**
     * @var string
     */
    public $check;
    /**
     * @var mixed
     */
    public $default;
    /**
     * @var string
     */
    public $append;
    /**
     * @var string
     */
    public $comment;
    /**
     * @var bool
     */
    public $isPrimaryKey = false;

    protected $definition = [];

    protected function buildSpecificDefinition($general, $schema, $composite) {}

    protected $isUnsignedPossible = true;
    protected $isNotNullPossible = true;
    protected $isPkPossible = true;

    protected function buildGeneralDefinition($composite)
    {
        array_unshift($this->definition, '$this');

        if ($this->isUnsignedPossible && $this->isUnsigned) {
            $this->definition[] = 'unsigned()';
        }
        if ($this->isNotNullPossible && $this->isNotNull) {
            $this->definition[] = 'notNull()';
        }



//        if ($this->defaultValue !== null) {
//            if ($this->defaultValue instanceof Expression) {
//                $definition .= '->defaultExpression(\'' . $this->defaultValue->expression . '\')';
//            } else {
//                $definition .= '->defaultValue(\'' . $this->defaultValue . '\')';
//            }
//        }



        if ($this->comment) {
            $this->definition[] = "comment('{$this->comment}')";
        }
        if (!$composite && $this->isPkPossible && $this->isPrimaryKey) {
            $this->definition[] = 'append(\'' . $this->prepareSchemaAppend(true, $this->autoIncrement) . '\')';
        }
    }

    public function renderDefinition($general, $schema, $composite)
    {
        $this->buildSpecificDefinition($general, $schema, $composite);
        $this->buildGeneralDefinition($composite);
        return implode('->', $this->definition);
    }
}
