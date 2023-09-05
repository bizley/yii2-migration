<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureInterface
{
    /**
     * Returns name of the structure.
     */
    public function getName(): string;

    /**
     * Returns primary key of the structure.
     */
    public function getPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Sets primary key of the structure.
     */
    public function setPrimaryKey(?PrimaryKeyInterface $primaryKey): void;

    /**
     * Adds column to the structure.
     */
    public function addColumn(ColumnInterface $column): void;

    /**
     * Removes column from the structure.
     */
    public function removeColumn(string $name): void;

    /**
     * Returns columns of the structure.
     * @return array<string, ColumnInterface>
     */
    public function getColumns(): array;

    /**
     * Returns column of given name of the structure.
     */
    public function getColumn(string $name): ?ColumnInterface;

    /**
     * Returns foreign keys of the structure.
     * @return array<string, ForeignKeyInterface>
     */
    public function getForeignKeys(): array;

    /**
     * Returns foreign key of given name of the structure.
     */
    public function getForeignKey(string $name): ?ForeignKeyInterface;

    /**
     * Adds foreign key to the structure.
     */
    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    /**
     * Removes foreign key from the structure.
     */
    public function removeForeignKey(string $name): void;

    /**
     * Adds index to the structure.
     */
    public function addIndex(IndexInterface $index): void;

    /**
     * Returns indexes of the structure.
     * @return array<string, IndexInterface>
     */
    public function getIndexes(): array;

    /**
     * Returns index of given name of the structure.
     */
    public function getIndex(string $name): ?IndexInterface;

    /**
     * Removes index from the structure.
     */
    public function removeIndex(string $name): void;
}
