<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureInterface
{
    /**
     * Returns name of the structure.
     * @return string
     */
    public function getName(): string;

    /**
     * Returns primary key of the structure.
     * @return PrimaryKeyInterface|null
     */
    public function getPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Sets primary key of the structure.
     * @param PrimaryKeyInterface|null $primaryKey
     */
    public function setPrimaryKey(?PrimaryKeyInterface $primaryKey): void;

    /**
     * Adds column to the structure.
     * @param ColumnInterface $column
     */
    public function addColumn(ColumnInterface $column): void;

    /**
     * Removes column from the structure.
     * @param string $name
     */
    public function removeColumn(string $name): void;

    /**
     * Returns columns of the structure.
     * @return array<ColumnInterface>
     */
    public function getColumns(): array;

    /**
     * Returns column of given name of the structure.
     * @param string $name
     * @return ColumnInterface|null
     */
    public function getColumn(string $name): ?ColumnInterface;

    /**
     * Returns foreign keys of the structure.
     * @return array
     */
    public function getForeignKeys(): array;

    /**
     * Returns foreign key of given name of the structure.
     * @param string $name
     * @return ForeignKeyInterface|null
     */
    public function getForeignKey(string $name): ?ForeignKeyInterface;

    /**
     * Adds foreign key to the structure.
     * @param ForeignKeyInterface $foreignKey
     */
    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    /**
     * Removes foreign key from the structure.
     * @param string $name
     */
    public function removeForeignKey(string $name): void;

    /**
     * Adds index to the structure.
     * @param IndexInterface $index
     */
    public function addIndex(IndexInterface $index): void;

    /**
     * Returns indexes of the structure.
     * @return array<IndexInterface>
     */
    public function getIndexes(): array;

    /**
     * Returns index of given name of the structure.
     * @param string $name
     * @return IndexInterface|null
     */
    public function getIndex(string $name): ?IndexInterface;

    /**
     * Removes index from the structure.
     * @param string $name
     */
    public function removeIndex(string $name): void;
}
