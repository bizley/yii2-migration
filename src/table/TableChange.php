<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Connection;

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
     * @var string
     * @since 3.1
     */
    public $schema;

    /**
     * @var Connection
     * @since 3.6.0
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
                        'length' => $schema['length'] ?? null,
                        'isNotNull' => $schema['isNotNull'] ?? null,
                        'isUnique' => $schema['isUnique'] ?? null,
                        'isPrimaryKey' => $schema['isPrimaryKey'] ?? null,
                        'check' => $schema['check'] ?? null,
                        'default' => $schema['default'] ?? null,
                        'append' => $schema['append'] ?? null,
                        'isUnsigned' => $schema['isUnsigned'] ?? null,
                        'comment' => !empty($schema['comment']) ? $schema['comment'] : null,
                        'after' => $schema['after'] ?? null,
                        'isFirst' => $schema['isFirst'] === true,
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
                    'length' => $this->data[1]['length'] ?? null,
                    'isNotNull' => $this->data[1]['isNotNull'] ?? null,
                    'isUnique' => $this->data[1]['isUnique'] ?? null,
                    'isPrimaryKey' => $this->data[1]['isPrimaryKey'] ?? null,
                    'check' => $this->data[1]['check'] ?? null,
                    'default' => $this->data[1]['default'] ?? null,
                    'append' => $this->data[1]['append'] ?? null,
                    'isUnsigned' => $this->data[1]['isUnsigned'] ?? null,
                    'comment' => !empty($this->data[1]['comment']) ? $this->data[1]['comment'] : null,
                    'after' => $this->data[1]['after'] ?? null,
                    'isFirst' => $this->data[1]['isFirst'] === true,
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
