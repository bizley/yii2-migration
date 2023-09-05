<?php

declare(strict_types=1);

namespace bizley\migration\table;

use InvalidArgumentException;

final class StructureChange implements StructureChangeInterface
{
    /** @var string */
    private $table;

    /** @var string */
    private $method;

    /** @var mixed */
    private $data;

    /**
     * Returns table name of the change.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets table name for the change.
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Returns method of the change.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets method for the change.
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * Returns data of the change.
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets data for the change.
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * Returns value of the change based on the method.
     * @return mixed Change value
     */
    public function getValue(string $schema = null, string $engineVersion = null)
    {
        switch ($this->getMethod()) {
            case 'createTable':
                return $this->getValueForCreateTable($schema, $engineVersion);

            case 'renameColumn':
                return $this->getValueForRenameColumn();

            case 'addColumn':
            case 'alterColumn':
                return $this->getValueForAddColumn($schema, $engineVersion);

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
     * Returns create table value of the change.
     * @return array<ColumnInterface>
     */
    private function getValueForCreateTable(string $engineName = null, string $engineVersion = null): array
    {
        $columns = [];

        $data = $this->getData();
        if (!\is_array($data)) {
            throw new InvalidArgumentException('Wrong data for createTable method.');
        }

        foreach ($data as $columnName => $schema) {
            $column = ColumnFactory::build($schema['type'] ?? 'unknown');
            $column->setName($columnName);
            $column->setLength($schema['length'] ?? null, $engineName, $engineVersion);
            $column->setNotNull($schema['isNotNull'] ?? null);
            $column->setUnique($schema['isUnique'] ?? false);
            $column->setAutoIncrement($schema['autoIncrement'] ?? false);
            $column->setPrimaryKey($schema['isPrimaryKey'] ?? false);
            $column->setDefault($schema['default'] ?? null);
            $column->setAppend($schema['append'] ?? null);
            $column->setUnsigned($schema['isUnsigned'] ?? false);
            $column->setComment(
                (\array_key_exists('comment', $schema) && !empty($schema['comment'])) ? $schema['comment'] : null
            );
            $column->setAfter($schema['after'] ?? null);
            $column->setFirst($schema['isFirst'] ?? false);

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Returns rename column value of the change.
     * @return array<string, string>
     */
    private function getValueForRenameColumn(): array
    {
        /** @var array<string, string> $data */
        $data = $this->getData();
        if (
            !\is_array($data)
            || !\array_key_exists('new', $data)
            || !\array_key_exists('old', $data)
            || !\is_string($data['new'])
            || !\is_string($data['old'])
        ) {
            throw new InvalidArgumentException('Wrong data for renameColumn method.');
        }

        return $data;
    }

    /**
     * Returns add column value of the change.
     */
    private function getValueForAddColumn(string $engineName = null, string $engineVersion = null): ColumnInterface
    {
        $data = $this->getData();
        if (
            !\is_array($data)
            || !\array_key_exists('name', $data)
            || !\array_key_exists('schema', $data)
            || !\is_string($data['name'])
            || !\is_array($data['schema'])
        ) {
            throw new InvalidArgumentException('Wrong data for addColumn method.');
        }

        $column = ColumnFactory::build($data['schema']['type'] ?? 'unknown');
        $column->setName($data['name']);
        $column->setLength($data['schema']['length'] ?? null, $engineName, $engineVersion);
        $column->setNotNull($data['schema']['isNotNull'] ?? null);
        $column->setUnique($data['schema']['isUnique'] ?? false);
        $column->setAutoIncrement($data['schema']['autoIncrement'] ?? false);
        $column->setPrimaryKey($data['schema']['isPrimaryKey'] ?? false);
        $column->setDefault($data['schema']['default'] ?? null);
        $column->setAppend($data['schema']['append'] ?? null);
        $column->setUnsigned($data['schema']['isUnsigned'] ?? false);
        $column->setComment(!empty($data['schema']['comment']) ? $data['schema']['comment'] : null);
        $column->setAfter($data['schema']['after'] ?? null);
        $column->setFirst($data['schema']['isFirst'] ?? false);

        return $column;
    }

    /**
     * Returns add primary key value of the change.
     */
    private function getValueForAddPrimaryKey(): PrimaryKeyInterface
    {
        $data = $this->getData();
        if (
            !\is_array($data)
            || !\array_key_exists('name', $data)
            || !\array_key_exists('columns', $data)
            || !\is_string($data['name'])
            || !\is_array($data['columns'])
        ) {
            throw new InvalidArgumentException('Wrong data for addPrimaryKey method.');
        }

        $primaryKey = new PrimaryKey();
        $primaryKey->setName($data['name']);
        $primaryKey->setColumns($data['columns']);

        return $primaryKey;
    }

    /**
     * Returns add foreign key value of the change.
     */
    private function getValueForAddForeignKey(): ForeignKeyInterface
    {
        $data = $this->getData();
        if (
            !\is_array($data)
            || !\array_key_exists('name', $data)
            || !\array_key_exists('columns', $data)
            || !\array_key_exists('referredTable', $data)
            || !\array_key_exists('referredColumns', $data)
            || !\array_key_exists('onDelete', $data)
            || !\array_key_exists('onUpdate', $data)
            || !\array_key_exists('tableName', $data)
            || !\is_string($data['name'])
            || !\is_array($data['columns'])
            || !\is_string($data['referredTable'])
            || !\is_array($data['referredColumns'])
            || ($data['onDelete'] !== null && !\is_string($data['onDelete']))
            || ($data['onUpdate'] !== null && !\is_string($data['onUpdate']))
            || !\is_string($data['tableName'])
        ) {
            throw new InvalidArgumentException('Wrong data for addForeignKey method.');
        }

        $foreignKey = new ForeignKey();
        $foreignKey->setName($data['name']);
        $foreignKey->setColumns($data['columns']);
        $foreignKey->setReferredTable($data['referredTable']);
        $foreignKey->setReferredColumns($data['referredColumns']);
        $foreignKey->setOnDelete($data['onDelete']);
        $foreignKey->setOnUpdate($data['onUpdate']);
        $foreignKey->setTableName($data['tableName']);

        return $foreignKey;
    }

    /**
     * Returns create index value of the change.
     */
    private function getValueForCreateIndex(): IndexInterface
    {
        $data = $this->getData();
        if (
            !\is_array($data)
            || !\array_key_exists('name', $data)
            || !\array_key_exists('columns', $data)
            || !\array_key_exists('unique', $data)
            || !\is_string($data['name'])
            || !\is_array($data['columns'])
            || !\is_bool($data['unique'])
        ) {
            throw new InvalidArgumentException('Wrong data for createIndex method.');
        }

        $index = new Index();
        $index->setName($data['name']);
        $index->setColumns($data['columns']);
        $index->setUnique($data['unique']);

        return $index;
    }

    /**
     * Returns add comment on column value of the change.
     * @return array<string, string>
     */
    private function getValueForAddCommentOnColumn(): array
    {
        /** @var array<string, string> $data */
        $data = $this->getData();
        if (
            !\is_array($data)
            || !\array_key_exists('column', $data)
            || !\array_key_exists('comment', $data)
            || !\is_string($data['column'])
            || !\is_string($data['comment'])
        ) {
            throw new InvalidArgumentException('Wrong data for addCommentOnColumn.');
        }

        return $data;
    }
}
