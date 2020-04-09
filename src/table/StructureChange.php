<?php

declare(strict_types=1);

namespace bizley\migration\table;

use InvalidArgumentException;

use function array_key_exists;
use function count;
use function is_array;

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
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets table name for the change.
     * @param string $table
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Returns method of the change.
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Sets method for the change.
     * @param string $method
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
     * Returns create table value of the change.
     * @return array<ColumnInterface>
     */
    private function getValueForCreateTable(): array
    {
        $columns = [];

        $data = $this->getData();
        if (is_array($data) === false) {
            throw new InvalidArgumentException('Data for createTable method must be an array.');
        }

        foreach ($data as $columnName => $schema) {
            $column = ColumnFactory::build($schema['type'] ?? 'unknown');
            $column->setName($columnName);
            $column->setLength($schema['length'] ?? null);
            $column->setNotNull($schema['isNotNull'] ?? null);
            $column->setUnique($schema['isUnique'] ?? false);
            $column->setAutoIncrement($schema['autoIncrement'] ?? false);
            $column->setPrimaryKey($schema['isPrimaryKey'] ?? false);
            $column->setDefault($schema['default'] ?? null);
            $column->setAppend($schema['append'] ?? null);
            $column->setUnsigned($schema['isUnsigned'] ?? false);
            $column->setComment(
                (array_key_exists('comment', $schema) && !empty($schema['comment'])) ? $schema['comment'] : null
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
        $data = $this->getData();
        if (
            is_array($data) === false
            || count($data) !== 2
            || is_string($data[0]) === false
            || is_string($data[1]) === false
        ) {
            throw new InvalidArgumentException(
                'Data for renameColumn method must be 2-elements array, both being strings.'
            );
        }

        return [
            'old' => $data[0],
            'new' => $data[1],
        ];
    }

    /**
     * Returns add column value of the change.
     * @return ColumnInterface
     */
    private function getValueForAddColumn(): ColumnInterface
    {
        $data = $this->getData();
        if (
            is_array($data) === false
            || count($data) !== 2
            || is_string($data[0]) === false
            || is_array($data[1]) === false
        ) {
            throw new InvalidArgumentException(
                'Data for addColumn method must be 2-elements array, first being string, second being array.'
            );
        }

        $column = ColumnFactory::build($data[1]['type'] ?? 'unknown');
        $column->setName($data[0]);
        $column->setLength($data[1]['length'] ?? null);
        $column->setNotNull($data[1]['isNotNull'] ?? null);
        $column->setUnique($data[1]['isUnique'] ?? false);
        $column->setAutoIncrement($data[1]['autoIncrement'] ?? false);
        $column->setPrimaryKey($data[1]['isPrimaryKey'] ?? false);
        $column->setDefault($data[1]['default'] ?? null);
        $column->setAppend($data[1]['append'] ?? null);
        $column->setUnsigned($data[1]['isUnsigned'] ?? false);
        $column->setComment(!empty($data[1]['comment']) ? $data[1]['comment'] : null);
        $column->setAfter($data[1]['after'] ?? null);
        $column->setFirst($data[1]['isFirst'] ?? false);

        return $column;
    }

    /**
     * Returns add primary key value of the change.
     * @return PrimaryKeyInterface
     */
    private function getValueForAddPrimaryKey(): PrimaryKeyInterface
    {
        $data = $this->getData();
        if (
            is_array($data) === false
            || count($data) !== 2
            || is_string($data[0]) === false
            || is_array($data[1]) === false
        ) {
            throw new InvalidArgumentException(
                'Data for addPrimaryKey method must be 2-elements array, first being string, second being array.'
            );
        }

        $primaryKey = new PrimaryKey();
        $primaryKey->setName($data[0]);
        $primaryKey->setColumns($data[1]);

        return $primaryKey;
    }

    /**
     * Returns add foreign key value of the change.
     * @return ForeignKeyInterface
     */
    private function getValueForAddForeignKey(): ForeignKeyInterface
    {
        $data = $this->getData();
        if (
            is_array($data) === false
            || count($data) !== 6
            || is_string($data[0]) === false
            || is_array($data[1]) === false
            || is_string($data[2]) === false
            || is_array($data[3]) === false
        ) {
            throw new InvalidArgumentException(
                'Data for addForeignKey method must be 6-elements array, first and third being strings, second and fourth being arrays, and fifth and sixth being strings or nulls.'
            );
        }

        $foreignKey = new ForeignKey();
        $foreignKey->setName($data[0]);
        $foreignKey->setColumns($data[1]);
        $foreignKey->setReferredTable($data[2]);
        $foreignKey->setReferredColumns($data[3]);

        return $foreignKey;
    }

    /**
     * Returns create index value of the change.
     * @return IndexInterface
     */
    private function getValueForCreateIndex(): IndexInterface
    {
        $data = $this->getData();
        if (
            is_array($data) === false
            || count($data) !== 3
            || is_string($data[0]) === false
            || is_array($data[1]) === false
            || is_bool($data[2]) === false
        ) {
            throw new InvalidArgumentException(
                'Data for createIndex method must be 3-elements array, first being string, second being array, and third being boolean.'
            );
        }

        $index = new Index();
        $index->setName($data[0]);
        $index->setColumns($data[1]);
        $index->setUnique($data[2]);

        return $index;
    }

    /**
     * Returns add comment on column value of the change.
     * @return array<string, string>
     */
    private function getValueForAddCommentOnColumn(): array
    {
        $data = $this->getData();
        if (
            is_array($data) === false
            || count($data) !== 2
            || is_string($data[0]) === false
            || is_string($data[1]) === false
        ) {
            throw new InvalidArgumentException(
                'Data for addCommentOnColumn method must be 2-elements array, both being strings.'
            );
        }

        return [
            'name' => $data[0],
            'comment' => $data[1],
        ];
    }
}
