<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidConfigException;

class StructureChange
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array|string
     */
    private $data;

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array|string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array|string $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * Returns change value.
     * @return array|string|Column|PrimaryKey|ForeignKey|Index
     * @throws InvalidConfigException
     */
    public function getValue()
    {
        switch ($this->getMethod()) {
            case 'createTable':
                return $this->getValueForCreateTable();

            case 'renameColumn':
                return $this->getValueForRenameColumn();

            case 'addColumn':
            case 'alterColumn':
                return $this->getValueForAddColumn();

            case 'addPrimaryKey':
                return $this->getValueForAddPrimaryKey();

            case 'addForeignKey':
                return $this->getValueForAddForeignKey();

            case 'createIndex':
                return $this->getValueForCreateIndex();

            case 'addCommentOnColumn':
                return $this->getValueForAddCommentOnColumn();

            case 'renameTable':
            case 'dropTable':
            case 'dropColumn':
            case 'dropPrimaryKey':
            case 'dropForeignKey':
            case 'dropIndex':
            case 'dropCommentFromColumn':
            default:
                return $this->getData();
        }
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    private function getValueForCreateTable(): array
    {
        $columns = [];

        foreach ((array)$this->getData() as $columnName => $schema) {
            $column = ColumnFactory::build($schema['type']);
            $column->setName($columnName);
            $column->setLength($schema['length'] ?? null);
            $column->setIsNotNull($schema['isNotNull'] ?? null);
            $column->setIsUnique($schema['isUnique'] ?? false);
            $column->setAutoIncrement($schema['autoIncrement'] ?? false);
            $column->setIsPrimaryKey($schema['isPrimaryKey'] ?? false);
            $column->setDefault($schema['default'] ?? null);
            $column->setAppend($schema['append'] ?? null);
            $column->setIsUnsigned($schema['isUnsigned'] ?? false);
            $column->setComment(!empty($schema['comment']) ? $schema['comment'] : null);
            $column->setAfter($schema['after'] ?? null);
            $column->setIsFirst($schema['isFirst'] ?? false);

            $columns[] = $column;
        }

        return $columns;
    }

    private function getValueForRenameColumn(): array
    {
        $data = $this->getData();

        return [
            'old' => $data[0],
            'new' => $data[1],
        ];
    }

    /**
     * @return Column
     * @throws InvalidConfigException
     */
    private function getValueForAddColumn(): Column
    {
        $data = $this->getData();

        $column = ColumnFactory::build($data[1]['type']);
        $column->setName($data[0]);
        $column->setLength($data[1]['length'] ?? null);
        $column->setIsNotNull($data[1]['isNotNull'] ?? null);
        $column->setIsUnique($data[1]['isUnique'] ?? false);
        $column->setAutoIncrement($data[1]['autoIncrement'] ?? false);
        $column->setIsPrimaryKey($data[1]['isPrimaryKey'] ?? false);
        $column->setDefault($data[1]['default'] ?? null);
        $column->setAppend($data[1]['append'] ?? null);
        $column->setIsUnsigned($data[1]['isUnsigned'] ?? false);
        $column->setComment(!empty($data[1]['comment']) ? $data[1]['comment'] : null);
        $column->setAfter($data[1]['after'] ?? null);
        $column->setIsFirst($data[1]['isFirst'] ?? false);

        return $column;
    }

    private function getValueForAddPrimaryKey(): PrimaryKey
    {
        $data = $this->getData();

        $primaryKey = new PrimaryKey();
        $primaryKey->setName($data[0]);
        $primaryKey->setColumns($data[1]);

        return $primaryKey;
    }

    private function getValueForAddForeignKey(): ForeignKey
    {
        $data = $this->getData();

        $foreignKey = new ForeignKey();
        $foreignKey->setName($data[0]);
        $foreignKey->setColumns($data[1]);
        $foreignKey->setReferencedTable($data[2]);
        $foreignKey->setReferencedColumns($data[3]);

        return $foreignKey;
    }

    private function getValueForCreateIndex(): Index
    {
        $data = $this->getData();

        $index = new Index();
        $index->setName($data[0]);
        $index->setColumns($data[1]);
        $index->setUnique($data[2]);

        return $index;
    }

    private function getValueForAddCommentOnColumn(): array
    {
        $data = $this->getData();

        return [
            'name' => $data[0],
            'comment' => $data[1],
        ];
    }
}
