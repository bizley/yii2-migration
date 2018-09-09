<?php

declare(strict_types=1);

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
                        'length' => $schema['length'] ?? null,
                        'isNotNull' => $schema['isNotNull'] ?? null,
                        'isUnique' => $schema['isUnique'] ?? null,
                        'isPrimaryKey' => $schema['isPrimaryKey'] ?? null,
                        'check' => $schema['check'] ?? null,
                        'default' => $schema['default'] ?? null,
                        'append' => $schema['append'] ?? null,
                        'isUnsigned' => $schema['isUnsigned'] ?? null,
                        'comment' => $schema['comment'] ?? null,
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
                    'length' => $this->data[1]['length'] ?? null,
                    'isNotNull' => $this->data[1]['isNotNull'] ?? null,
                    'isUnique' => $this->data[1]['isUnique'] ?? null,
                    'isPrimaryKey' => $this->data[1]['isPrimaryKey'] ?? null,
                    'check' => $this->data[1]['check'] ?? null,
                    'default' => $this->data[1]['default'] ?? null,
                    'append' => $this->data[1]['append'] ?? null,
                    'isUnsigned' => $this->data[1]['isUnsigned'] ?? null,
                    'comment' => $this->data[1]['comment'] ?? null,
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
