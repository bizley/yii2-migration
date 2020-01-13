<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\Connection;

class Change extends BaseObject
{
    /** @var string */
    public $table;

    /** @var string */
    public $method;

    /** @var array|string */
    public $data;

    /** @var string */
    public $schema;

    /** @var Connection */
    public $db;

    /**
     * Returns change value.
     * @return array|string|Column|PrimaryKey|ForeignKey|Index
     * @throws InvalidConfigException
     */
    public function getValue()
    {
        switch ($this->method) {
            case 'createTable':
                $columns = [];

                foreach ((array)$this->data as $column => $schema) {
                    $columns[] = ColumnFactory::build([
                        'schema' => $this->schema,
                        'name' => $column,
                        'type' => $schema['type'],
                        'defaultMapping' => $this->db->schema->queryBuilder->typeMap[$schema['type']],
                        'length' => $schema['length'] ?? null,
                        'isNotNull' => $schema['isNotNull'] ?? null,
                        'isUnique' => $schema['isUnique'] ?? false,
                        'autoIncrement' => $schema['autoIncrement'] ?? false,
                        'isPrimaryKey' => $schema['isPrimaryKey'] ?? false,
                        'check' => $schema['check'] ?? null,
                        'default' => $schema['default'] ?? null,
                        'append' => $schema['append'] ?? null,
                        'isUnsigned' => $schema['isUnsigned'] ?? false,
                        'comment' => !empty($schema['comment']) ? $schema['comment'] : null,
                        'after' => $schema['after'] ?? null,
                        'isFirst' => $schema['isFirst'] ?? false,
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
                return ColumnFactory::build([
                    'schema' => $this->schema,
                    'name' => $this->data[0],
                    'type' => $this->data[1]['type'],
                    'defaultMapping' => $this->db->schema->queryBuilder->typeMap[$this->data[1]['type']],
                    'length' => $this->data[1]['length'] ?? null,
                    'isNotNull' => $this->data[1]['isNotNull'] ?? null,
                    'isUnique' => $this->data[1]['isUnique'] ?? false,
                    'autoIncrement' => $this->data[1]['autoIncrement'] ?? false,
                    'isPrimaryKey' => $this->data[1]['isPrimaryKey'] ?? false,
                    'check' => $this->data[1]['check'] ?? null,
                    'default' => $this->data[1]['default'] ?? null,
                    'append' => $this->data[1]['append'] ?? null,
                    'isUnsigned' => $this->data[1]['isUnsigned'] ?? false,
                    'comment' => !empty($this->data[1]['comment']) ? $this->data[1]['comment'] : null,
                    'after' => $this->data[1]['after'] ?? null,
                    'isFirst' => $this->data[1]['isFirst'] ?? false,
                ]);

            case 'addPrimaryKey':
                return new PrimaryKey([
                    'name' => $this->data[0],
                    'columns' => $this->data[1],
                ]);

            case 'addForeignKey':
                return new ForeignKey([
                    'name' => $this->data[0],
                    'columns' => $this->data[1],
                    'refTable' => $this->data[2],
                    'refColumns' => $this->data[3],
                ]);

            case 'createIndex':
                return new Index([
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
