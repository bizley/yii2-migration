<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class Structure implements StructureInterface
{
    /** @var string */
    private $name;

    /** @var PrimaryKeyInterface|null */
    private $primaryKey;

    /** @var array<string, ColumnInterface> */
    private $columns = [];

    /** @var array<string, IndexInterface> */
    private $indexes = [];

    /** @var array<string, ForeignKeyInterface> */
    private $foreignKeys = [];

    /**
     * Returns name of the structure.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets name of the structure.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns primary key of the structure.
     */
    public function getPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->primaryKey;
    }

    /**
     * Sets primary key of the structure.
     */
    public function setPrimaryKey(?PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * Returns columns of the structure.
     * @return array<string, ColumnInterface>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Sets columns of the structure.
     * @param array<string, ColumnInterface> $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * Adds column to the structure.
     */
    public function addColumn(ColumnInterface $column): void
    {
        $this->columns[$column->getName()] = $column;
    }

    /**
     * Removes column from the structure.
     */
    public function removeColumn(string $name): void
    {
        unset($this->columns[$name]);
    }

    /**
     * Returns column of given name of the structure.
     */
    public function getColumn(string $name): ?ColumnInterface
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * Returns indexes of the structure.
     * @return array<string, IndexInterface>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Returns index of given name of the structure.
     */
    public function getIndex(string $name): ?IndexInterface
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * Sets indexes for the structure.
     * @param array<string, IndexInterface> $indexes
     */
    public function setIndexes(array $indexes): void
    {
        $this->indexes = $indexes;
    }

    /**
     * Adds index to the structure.
     */
    public function addIndex(IndexInterface $index): void
    {
        $this->indexes[$index->getName()] = $index;
    }

    /**
     * Removes index from the structure.
     */
    public function removeIndex(string $name): void
    {
        unset($this->indexes[$name]);
    }

    /**
     * Returns foreign keys of the structure.
     * @return array<string, ForeignKeyInterface>
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Returns foreign key of given name of the structure.
     */
    public function getForeignKey(string $name): ?ForeignKeyInterface
    {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * Sets foreign keys for the structure.
     * @param array<string, ForeignKeyInterface> $foreignKeys
     */
    public function setForeignKeys(array $foreignKeys): void
    {
        $this->foreignKeys = $foreignKeys;
    }

    /**
     * Adds foreign key to the structure.
     */
    public function addForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * Removes foreign key from the structure.
     */
    public function removeForeignKey(string $name): void
    {
        unset($this->foreignKeys[$name]);
    }
}
