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
     * @var bool isNotNull
     */
    public $allowNull;
    /**
     * @var string length
     */
    public $size;
    /**
     * @var int precision of the column data, if it is numeric.
     */
    public $precision;
    /**
     * @var int scale of the column data, if it is numeric.
     */
    public $scale;
    /**
     * @var string WYWALIC
     */
    //public $isUnique;
    /**
     * @var bool whether this column is a primary key
     */
    public $isPrimaryKey;
    /**
     * @var bool whether this column is auto-incremental
     */
    public $autoIncrement = false;
    /**
     * @var bool
     */
    public $isUnsigned;
    /**
     * @var string
     */
    //public $check;
    /**
     * @var string default
     */
    public $defaultValue;
    /**
     * @var string
     */
    //public $append;
    /**
     * @var string
     */
    public $comment;

    public function renderSize()
    {
        return empty($column->size) && !is_numeric($column->size) ? null : $column->size;
    }

    public function render()
    {
/*
'<?= $name ?>' => $this<?= $definition ?>,
*/

        $definition = '';
        $size = '';
        $checkPrimaryKey = true;
        $checkNotNull = true;
        $checkUnsigned = true;
        $schema = $this->db->schema;
        switch ($this->type) {
            case Schema::TYPE_PK:
            case Schema::TYPE_UPK:
            case Schema::TYPE_BIGPK:
            case Schema::TYPE_UBIGPK:
            case Schema::TYPE_CHAR:
            case Schema::TYPE_STRING:
            case Schema::TYPE_TEXT:
            case Schema::TYPE_SMALLINT:
            case Schema::TYPE_INTEGER:
            case Schema::TYPE_BIGINT:
            case Schema::TYPE_BINARY:
                $size = $this->renderSize($this);
                break;
            case Schema::TYPE_FLOAT:
            case Schema::TYPE_DOUBLE:
            case Schema::TYPE_DATETIME:
            case Schema::TYPE_TIMESTAMP:
            case Schema::TYPE_TIME:
                $size = $this->renderPrecision($this);
                break;
            case Schema::TYPE_DECIMAL:
            case Schema::TYPE_MONEY:
                $size = $this->renderPrecision($this) . ',' . $this->renderScale($this);
        }
        if ($this->generalSchema) {
            $size = '';
        }
        switch ($this->type) {
            case Schema::TYPE_UPK:
                if ($this->generalSchema) {
                    $checkUnsigned = false;
                    $definition .= '->unsigned()';
                }
            // no break
            case Schema::TYPE_PK:
                if ($this->generalSchema) {
                    $checkPrimaryKey = false;
                    if ($schema::className() !== 'yii\db\mssql\Schema') {
                        $checkNotNull = false;
                    }
                }
                $definition .= '->primaryKey(' . $size . ')';
                break;
            case Schema::TYPE_UBIGPK:
                if ($this->generalSchema) {
                    $checkUnsigned = false;
                    $definition .= '->unsigned()';
                }
            // no break
            case Schema::TYPE_BIGPK:
                if ($this->generalSchema) {
                    $checkPrimaryKey = false;
                    if ($schema::className() !== 'yii\db\mssql\Schema') {
                        $checkNotNull = false;
                    }
                }
                $definition .= '->bigPrimaryKey(' . $size . ')';
                break;
            case Schema::TYPE_CHAR:
                $definition .= '->char(' . $size . ')';
                break;
            case Schema::TYPE_STRING:
                $definition .= '->string(' . $size . ')';
                break;
            case Schema::TYPE_TEXT:
                $definition .= '->text(' . $size . ')';
                break;
            case Schema::TYPE_SMALLINT:
                $definition .= '->smallInteger(' . $size . ')';
                break;
            case Schema::TYPE_INTEGER:
                if ($this->generalSchema) {
                    if (!$compositePk && $this->isPrimaryKey) {
                        $checkPrimaryKey = false;
                        if ($schema::className() !== 'yii\db\mssql\Schema') {
                            $checkNotNull = false;
                        }
                        $definition .= '->primaryKey()';
                    } else {
                        $definition .= '->integer()';
                    }
                } else {
                    $definition .= '->integer(' . $size . ')';
                }
                break;
            case Schema::TYPE_BIGINT:
                if ($this->generalSchema) {
                    if (!$compositePk && $this->isPrimaryKey) {
                        $checkPrimaryKey = false;
                        if ($schema::className() !== 'yii\db\mssql\Schema') {
                            $checkNotNull = false;
                        }
                        $definition .= '->bigPrimaryKey()';
                    } else {
                        $definition .= '->bigInteger()';
                    }
                } else {
                    $definition .= '->bigInteger(' . $size . ')';
                }
                break;
            case Schema::TYPE_FLOAT:
                $definition .= '->float(' . $size . ')';
                break;
            case Schema::TYPE_DOUBLE:
                $definition .= '->double(' . $size . ')';
                break;
            case Schema::TYPE_DECIMAL:
                $definition .= '->decimal(' . $size . ')';
                break;
            case Schema::TYPE_DATETIME:
                $definition .= '->dateTime(' . $size . ')';
                break;
            case Schema::TYPE_TIMESTAMP:
                $definition .= '->timestamp(' . $size . ')';
                break;
            case Schema::TYPE_TIME:
                $definition .= '->time(' . $size . ')';
                break;
            case Schema::TYPE_DATE:
                $definition .= '->date()';
                break;
            case Schema::TYPE_BINARY:
                $definition .= '->binary(' . $size . ')';
                break;
            case Schema::TYPE_BOOLEAN:
                $definition .= '->boolean()';
                break;
            case Schema::TYPE_MONEY:
                $definition .= '->money(' . $size . ')';
                break;
        }
        if ($checkUnsigned && $this->unsigned) {
            $definition .= '->unsigned()';
        }
        if ($checkNotNull && !$this->allowNull) {
            $definition .= '->notNull()';
        }
        if ($this->defaultValue !== null) {
            if ($this->defaultValue instanceof Expression) {
                $definition .= '->defaultExpression(\'' . $this->defaultValue->expression . '\')';
            } else {
                $definition .= '->defaultValue(\'' . $this->defaultValue . '\')';
            }
        }
        if ($this->comment) {
            $definition .= '->comment(\'' . $this->comment . '\')';
        }
        if (!$compositePk && $checkPrimaryKey && $this->isPrimaryKey) {
            $definition .= '->append(\'' . $this->prepareSchemaAppend(true, $this->autoIncrement) . '\')';
        }

        return $definition;



    }
}
