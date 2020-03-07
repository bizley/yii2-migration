<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface StructureInterface
{
    public function getPrimaryKey(): ?PrimaryKeyInterface;

    public function setPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function addColumn(ColumnInterface $column): void;

    public function removeColumn(string $name): void;

    /**
     * @return array<ColumnInterface>
     */
    public function getColumns(): array;

    public function getColumn(string $name): ?ColumnInterface;

    /**
     * @return array<ForeignKeyInterface>
     */
    public function getForeignKeys(): array;

    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    public function removeForeignKey(string $name): void;

    public function addIndex(IndexInterface $index): void;

    public function getIndex(string $name): ?IndexInterface;

    public function removeIndex(string $name): void;
}
