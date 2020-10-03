<?php

namespace bizley\migration\table;

use yii\base\InvalidConfigException;
use yii\base\Object;
use yii\db\Connection;

/**
 * Class TableChange
 * @package bizley\migration\table
 *
 * @property-read array|string|TableColumn|TablePrimaryKey|TableForeignKey|TableIndex $value
 */
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

    /**
     * @var string
     * @since 2.4
     */
    public $schema;

    /**
     * @var Connection
     * @since 2.9.0
     */
    public $db;

    /**
     * Returns change value.
     * @return array|string|TableColumn|TablePrimaryKey|TableForeignKey|TableIndex
     * @throws InvalidConfigException
     */
    public function getValue()
    {
        switch ($this->method) {
            case 'createTable':
                $columns = [];
                foreach ((array)$this->data as $column => $schema) {
                    $columns[] = TableColumnFactory::build([
                        'schema' => $this->schema,
                        'name' => $column,
                        'type' => $schema['type'],
                        'defaultMapping' => $this->db->schema->queryBuilder->typeMap[$schema['type']],
                        'length' => isset($schema['length']) ? $schema['length'] : null,
                        'isNotNull' => isset($schema['isNotNull']) ? $schema['isNotNull'] : null,
                        'isUnique' => isset($schema['isUnique']) ? $schema['isUnique'] : false,
                        'autoIncrement' => isset($schema['autoIncrement']) ? $schema['autoIncrement'] : false,
                        'isPrimaryKey' => isset($schema['isPrimaryKey']) ? $schema['isPrimaryKey'] : false,
                        'check' => isset($schema['check']) ? $schema['check'] : null,
                        'default' => isset($schema['default']) ? $schema['default'] : null,
                        'append' => isset($schema['append']) ? $schema['append'] : null,
                        'isUnsigned' => isset($schema['isUnsigned']) ? $schema['isUnsigned'] : false,
                        'comment' => !empty($schema['comment']) ? $schema['comment'] : null,
                        'after' => !empty($schema['after']) ? $schema['after'] : null,
                        'isFirst' => isset($schema['isFirst']) ? $schema['isFirst'] : false,
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
                    'schema' => $this->schema,
                    'name' => $this->data[0],
                    'type' => $this->data[1]['type'],
                    'defaultMapping' => $this->db->schema->queryBuilder->typeMap[$this->data[1]['type']],
                    'length' => isset($this->data[1]['length']) ? $this->data[1]['length'] : null,
                    'isNotNull' => isset($this->data[1]['isNotNull']) ? $this->data[1]['isNotNull'] : null,
                    'isUnique' => isset($this->data[1]['isUnique']) ? $this->data[1]['isUnique'] : false,
                    'autoIncrement' => isset($this->data[1]['autoIncrement']) ? $this->data[1]['autoIncrement'] : false,
                    'isPrimaryKey' => isset($this->data[1]['isPrimaryKey']) ? $this->data[1]['isPrimaryKey'] : false,
                    'check' => isset($this->data[1]['check']) ? $this->data[1]['check'] : null,
                    'default' => isset($this->data[1]['default']) ? $this->data[1]['default'] : null,
                    'append' => isset($this->data[1]['append']) ? $this->data[1]['append'] : null,
                    'isUnsigned' => isset($this->data[1]['isUnsigned']) ? $this->data[1]['isUnsigned'] : false,
                    'comment' => !empty($this->data[1]['comment']) ? $this->data[1]['comment'] : null,
                    'after' => !empty($this->data[1]['after']) ? $this->data[1]['after'] : null,
                    'isFirst' => isset($this->data[1]['isFirst']) ? $this->data[1]['isFirst'] : false,
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