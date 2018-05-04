<?php

namespace bizley\migration\table;

use yii\base\BaseObject;

/**
 * Class TableChange
 * @package bizley\migration\table
 *
 * @property-read array|string|TableColumn|TablePrimaryKey|TableForeignKey|TableIndex $value
 */
class TableChange extends BaseObject
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

    /**
     * Returns change value.
     * @return array|string|TableColumn|TablePrimaryKey|TableForeignKey|TableIndex
     * @throws \yii\base\InvalidConfigException
     */
    public function getValue()
    {
        switch ($this->method) {
            case 'createTable':
                $columns = [];
                foreach ((array)$this->data as $column => $schema) {
                    $columns[] = TableColumnFactory::build([
                        'name' => $column,
                        'type' => $schema['type'],
                        'length' => isset($schema['length']) ? $schema['length'] : null,
                        'isNotNull' => isset($schema['isNotNull']) ? $schema['isNotNull'] : null,
                        'isUnique' => isset($schema['isUnique']) ? $schema['isUnique'] : null,
                        'isPrimaryKey' => isset($schema['isPrimaryKey']) ? $schema['isPrimaryKey'] : null,
                        'check' => isset($schema['check']) ? $schema['check'] : null,
                        'default' => isset($schema['default']) ? $schema['default'] : null,
                        'append' => isset($schema['append']) ? $schema['append'] : null,
                        'isUnsigned' => isset($schema['isUnsigned']) ? $schema['isUnsigned'] : null,
                        'comment' => isset($schema['comment']) ? $schema['comment'] : null,
                    ]);
                }
                return $columns;
            case 'renameColumn':
                return [
                    'old' => $this->data[0],
                    'new' => $this->data[1],
                ];
            case 'addColumn':
            case 'alterColumn':
                return TableColumnFactory::build([
                    'name' => $this->data[0],
                    'type' => $this->data[1]['type'],
                    'length' => isset($this->data[1]['length']) ? $this->data[1]['length'] : null,
                    'isNotNull' => isset($this->data[1]['isNotNull']) ? $this->data[1]['isNotNull'] : null,
                    'isUnique' => isset($this->data[1]['isUnique']) ? $this->data[1]['isUnique'] : null,
                    'isPrimaryKey' => isset($this->data[1]['isPrimaryKey']) ? $this->data[1]['isPrimaryKey'] : null,
                    'check' => isset($this->data[1]['check']) ? $this->data[1]['check'] : null,
                    'default' => isset($this->data[1]['default']) ? $this->data[1]['default'] : null,
                    'append' => isset($this->data[1]['append']) ? $this->data[1]['append'] : null,
                    'isUnsigned' => isset($this->data[1]['isUnsigned']) ? $this->data[1]['isUnsigned'] : null,
                    'comment' => isset($this->data[1]['comment']) ? $this->data[1]['comment'] : null,
                ]);
            case 'addPrimaryKey':
                return new TablePrimaryKey([
                    'name' => $this->data[0],
                    'columns' => $this->data[1],
                ]);
            case 'addForeignKey':
                return new TableForeignKey([
                    'name' => $this->data[0],
                    'columns' => $this->data[1],
                    'refTable' => $this->data[2],
                    'refColumns' => $this->data[3],
                ]);
            case 'createIndex':
                return new TableIndex([
                    'name' => $this->data[0],
                    'columns' => $this->data[1],
                    'unique' => $this->data[2],
                ]);
            case 'addCommentOnColumn':
                return [
                    'name' => $this->data[0],
                    'comment' => $this->data[1],
                ];
            case 'renameTable':
            case 'dropTable':
            case 'dropColumn':
            case 'dropPrimaryKey':
            case 'dropForeignKey':
            case 'dropIndex':
            case 'dropCommentFromColumn':
            default:
                return $this->data;
        }
    }
}
