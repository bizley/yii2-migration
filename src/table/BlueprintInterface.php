<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface BlueprintInterface
{
    /**
     * Returns table name of the structure.
     * @return string
     */
    public function getTableName(): string;

    /**
     * Sets table name for the structure.
     * @param string $tableName
     */
    public function setTableName(string $tableName): void;

    /**
     * Sets flag to indicate that blueprint contains no changes because table requires creation migration.
     */
    public function startFromScratch(): void;

    /**
     * Checks if blueprint contains no changes because table requires creation migration.
     * @return bool
     */
    public function needsStartFromScratch(): bool;

    /**
     * Adds single description of the change.
     * @param string $description
     */
    public function addDescription(string $description): void;

    /**
     * Returns changes descriptions.
     * @return array<string>
     */
    public function getDescriptions(): array;

    /**
     * Checks if blueprint is ready to proceed with the update of table.
     * @return bool
     */
    public function isPending(): bool;

    /**
     * Adds added column.
     * @param ColumnInterface $column
     */
    public function addColumn(ColumnInterface $column): void;

    /**
     * Adds altered column.
     * @param ColumnInterface $column
     */
    public function alterColumn(ColumnInterface $column): void;

    /**
     * Adds unaltered column.
     * @param ColumnInterface $column
     */
    public function reverseColumn(ColumnInterface $column): void;

    /**
     * Adds dropped column.
     * @param ColumnInterface $column
     */
    public function dropColumn(ColumnInterface $column): void;

    /**
     * Adds added foreign key.
     * @param ForeignKeyInterface $foreignKey
     */
    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    /**
     * Adds dropped foreign key.
     * @param ForeignKeyInterface $foreignKey
     */
    public function dropForeignKey(ForeignKeyInterface $foreignKey): void;

    /**
     * Adds dropped primary key.
     * @param PrimaryKeyInterface $primaryKey
     */
    public function dropPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    /**
     * Adds added primary key.
     * @param PrimaryKeyInterface $primaryKey
     */
    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    /**
     * Adds added index.
     * @param IndexInterface $index
     */
    public function addIndex(IndexInterface $index): void;

    /**
     * Adds dropped index.
     * @param IndexInterface $index
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
     * @return PrimaryKeyInterface|null
     */
    public function getDroppedPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Returns added primary key.
     * @return PrimaryKeyInterface|null
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
     * @return PrimaryKeyInterface|null
     */
    public function getTableOldPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Sets old table's primary key.
     * @param PrimaryKeyInterface|null $tableOldPrimaryKey
     */
    public function setTableOldPrimaryKey(?PrimaryKeyInterface $tableOldPrimaryKey): void;

    /**
     * Returns new table's primary key.
     * @return PrimaryKeyInterface|null
     */
    public function getTableNewPrimaryKey(): ?PrimaryKeyInterface;

    /**
     * Sets new table's primary key.
     * @param PrimaryKeyInterface|null $tableNewPrimaryKey
     */
    public function setTableNewPrimaryKey(?PrimaryKeyInterface $tableNewPrimaryKey): void;

    /**
     * Returns dropped primary key type.
     * @return string|null
     */
    public function getDroppedPrimaryKeyType(): ?string;

    /**
     * Sets dropped primary key type.
     * @param string $droppedPrimaryKeyType
     */
    public function setDroppedPrimaryKeyType(string $droppedPrimaryKeyType): void;

    /**
     * Returns added primary key type.
     * @return string|null
     */
    public function getAddedPrimaryKeyType(): ?string;

    /**
     * Sets added primary key type.
     * @param string $addedPrimaryKeyType
     */
    public function setAddedPrimaryKeyType(string $addedPrimaryKeyType): void;
}
