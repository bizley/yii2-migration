<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface BlueprintInterface
{
    /**
     * Returns table name of the structure.
     */
    public function getTableName(): string;

    /**
     * Sets table name for the structure.
     */
    public function setTableName(string $tableName): void;

    /**
     * Sets flag to indicate that blueprint contains no changes because table requires creation migration.
     */
    public function startFromScratch(): void;

    /**
     * Checks if blueprint contains no changes because table requires creation migration.
     */
    public function needsStartFromScratch(): bool;

    /**
     * Adds single description of the change.
     */
    public function addDescription(string $description): void;

    /**
     * Returns changes descriptions.
     * @return array<string>
     */
    public function getDescriptions(): array;

    /**
     * Checks if blueprint is ready to proceed with the update of table.
     */
    public function isPending(): bool;

    /**
     * Adds added column.
     */
    public function addColumn(ColumnInterface $column): void;

    /**
     * Adds altered column.
     */
    public function alterColumn(ColumnInterface $column): void;

    /**
     * Adds unaltered column.
     */
    public function reverseColumn(ColumnInterface $column): void;

    /**
     * Adds dropped column.
     */
    public function dropColumn(ColumnInterface $column): void;

    /**
     * Adds added foreign key.
     */
    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    /**
     * Adds dropped foreign key.
     */
    public function dropForeignKey(ForeignKeyInterface $foreignKey): void;

    /**
     * Adds dropped primary key.
     */
    public function dropPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    /**
     * Adds added primary key.
     */
    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    /**
     * Adds added index.
     */
    public function addIndex(IndexInterface $index): void;

    /**
     * Adds dropped index.
     */
    public function dropIndex(IndexInterface $index): void;

    /**
     * Returns dropped columns.
     * @return array<string, ColumnInterface>
     */
    public function getDroppedColumns(): array;

    /**
     * Returns added columns.
     * @return array<string, ColumnInterface>
     */
    public function getAddedColumns(): array;

    /**
     * Returns altered columns.
     * @return array<string, ColumnInterface>
     */
    public function getAlteredColumns(): array;

    /**
     * Returns unaltered columns.
     * @return array<string, ColumnInterface>
     */
    public function getUnalteredColumns(): array;

    /**
     * Returns dropped columns.
     * @return array<string, ForeignKeyInterface>
     */
    public function getDroppedForeignKeys(): array;

    /**
     * Returns added foreign keys.
     * @return array<string, ForeignKeyInterface>
     */
    public function getAddedForeignKeys(): array;

    /**
     * Returns dropped primary key.
     */
    public function getDroppedPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Returns added primary key.
     */
    public function getAddedPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Returns dropped indexes.
     * @return array<string, IndexInterface>
     */
    public function getDroppedIndexes(): array;

    /**
     * Returns added indexes.
     * @return array<string, IndexInterface>
     */
    public function getAddedIndexes(): array;

    /**
     * Returns old table's primary key.
     */
    public function getTableOldPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Sets old table's primary key.
     */
    public function setTableOldPrimaryKey(?PrimaryKeyInterface $tableOldPrimaryKey): void;

    /**
     * Returns new table's primary key.
     */
    public function getTableNewPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Sets new table's primary key.
     */
    public function setTableNewPrimaryKey(?PrimaryKeyInterface $tableNewPrimaryKey): void;
}
