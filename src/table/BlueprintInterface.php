<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface BlueprintInterface
{
    public function getTableName(): ?string;

    public function setStartFromScratch(bool $startFromScratch): void;

    public function isStartFromScratch(): bool;

    public function addDescription(string $description): void;

    public function getDescriptions(): array;

    public function isPending(): bool;

    public function addColumn(ColumnInterface $column): void;

    public function alterColumn(ColumnInterface $column): void;

    public function reverseColumn(ColumnInterface $column): void;

    public function dropColumn(ColumnInterface $column): void;

    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    public function dropForeignKey(ForeignKeyInterface $foreignKey): void;

    public function dropPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function createIndex(IndexInterface $index): void;

    public function dropIndex(IndexInterface $index): void;

    /** @return array<ColumnInterface> */
    public function getDroppedColumns(): array;

    /** @return array<ColumnInterface> */
    public function getAddedColumns(): array;

    /** @return array<ColumnInterface> */
    public function getAlteredColumns(): array;

    /** @return array<ColumnInterface> */
    public function getUnalteredColumns(): array;

    /** @return array<ForeignKeyInterface> */
    public function getDroppedForeignKeys(): array;

    /** @return array<ForeignKeyInterface> */
    public function getAddedForeignKeys(): array;

    public function getDroppedPrimaryKey(): ?PrimaryKeyInterface;

    public function getAddedPrimaryKey(): ?PrimaryKeyInterface;

    /** @return array<IndexInterface> */
    public function getDroppedIndexes(): array;

    /** @return array<IndexInterface> */
    public function getAddedIndexes(): array;

    public function getTableOldPrimaryKey(): ?PrimaryKeyInterface;

    public function setTableOldPrimaryKey(?PrimaryKeyInterface $tablePrimaryKey): void;

    public function getTableNewPrimaryKey(): ?PrimaryKeyInterface;

    public function setTableNewPrimaryKey(?PrimaryKeyInterface $tablePrimaryKey): void;
}
