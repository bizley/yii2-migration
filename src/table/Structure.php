<?php

declare(strict_types=1);

namespace bizley\migration\table;

class Structure implements StructureInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var PrimaryKey
     */
    private $primaryKey;

    /**
     * @var array<Column>
     */
    private $columns = [];

    /**
     * @var array<Index>
     */
    private $indexes = [];

    /**
     * @var array<ForeignKey>
     */
    private $foreignKeys = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return PrimaryKey|null
     */
    public function getPrimaryKey(): ?PrimaryKey
    {
        return $this->primaryKey;
    }

    /**
     * @param PrimaryKey $primaryKey
     */
    public function setPrimaryKey(PrimaryKey $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @param Column $column
     */
    public function addColumn(Column $column): void
    {
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param array $indexes
     */
    public function setIndexes(array $indexes): void
    {
        $this->indexes = $indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function setForeignKeys(array $foreignKeys): void
    {
        $this->foreignKeys = $foreignKeys;
    }
}
