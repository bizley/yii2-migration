<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class Structure implements StructureInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var PrimaryKeyInterface
     */
    private $primaryKey;

    /**
     * @var array<ColumnInterface>
     */
    private $columns = [];

    /**
     * @var array<IndexInterface>
     */
    private $indexes = [];

    /**
     * @var array<ForeignKeyInterface>
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
     * @return PrimaryKeyInterface|null
     */
    public function getPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->primaryKey;
    }

    /**
     * @param PrimaryKeyInterface|null $primaryKey
     */
    public function setPrimaryKey(?PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return array<ColumnInterface>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array<ColumnInterface> $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @param ColumnInterface $column
     */
    public function addColumn(ColumnInterface $column): void
    {
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @param string $name
     */
    public function removeColumn(string $name): void
    {
        unset($this->columns[$name]);
    }

    /**
     * @param string $name
     * @return ColumnInterface|null
     */
    public function getColumn(string $name): ?ColumnInterface
    {
        return $this->columns[$name] ?? null;
    }

    /**
     * @return array<IndexInterface>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getIndex(string $name): ?IndexInterface
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * @param array<IndexInterface> $indexes
     */
    public function setIndexes(array $indexes): void
    {
        $this->indexes = $indexes;
    }

    public function addIndex(IndexInterface $index): void
    {
        $this->indexes[$index->getName()] = $index;
    }

    public function removeIndex(string $name): void
    {
        unset($this->indexes[$name]);
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getForeignKey(string $name): ?ForeignKeyInterface
    {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * @param array<ForeignKeyInterface> $foreignKeys
     */
    public function setForeignKeys(array $foreignKeys): void
    {
        $this->foreignKeys = $foreignKeys;
    }

    public function addForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
    }

    public function removeForeignKey(string $name): void
    {
        unset($this->foreignKeys[$name]);
    }
}
