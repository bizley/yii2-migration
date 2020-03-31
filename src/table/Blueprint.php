<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;

final class Blueprint implements BlueprintInterface
{
    /** @var string */
    private $tableName;

    /** @var PrimaryKeyInterface|null */
    private $tableOldPrimaryKey;

    /** @var PrimaryKeyInterface|null */
    private $tableNewPrimaryKey;

    /** @var array<string, ColumnInterface> */
    private $columnsToDrop = [];

    /** @var array<string, ColumnInterface> */
    private $columnsToAdd = [];

    /** @var array<string, ColumnInterface> */
    private $columnsToAlter = [];

    /** @var array<string, ColumnInterface> */
    private $columnsToUnalter = [];

    /** @var array<string, ForeignKeyInterface> */
    private $foreignKeysToDrop = [];

    /** @var array<string, ForeignKeyInterface> */
    private $foreignKeysToAdd = [];

    /** @var PrimaryKeyInterface|null */
    private $primaryKeyToDrop;

    /** @var PrimaryKeyInterface|null */
    private $primaryKeyToAdd;

    /** @var array<string, IndexInterface> */
    private $indexesToDrop = [];

    /** @var array<string, IndexInterface> */
    private $indexesToAdd = [];

    /** @var array<string> */
    private $description = [];

    /** @var bool */
    private $startFromScratch = false;

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function startFromScratch(): void
    {
        $this->startFromScratch = true;
    }

    public function needsStartFromScratch(): bool
    {
        return $this->startFromScratch;
    }

    public function addDescription(string $description): void
    {
        $this->description[] = $description;
    }

    public function getDescriptions(): array
    {
        return $this->description;
    }

    public function isPending(): bool
    {
        return $this->startFromScratch === true || count($this->description) > 0;
    }

    public function addColumn(ColumnInterface $column): void
    {
        $this->columnsToAdd[$column->getName()] = $column;
    }

    public function alterColumn(ColumnInterface $column): void
    {
        $this->columnsToAlter[$column->getName()] = $column;
    }

    public function reverseColumn(ColumnInterface $column): void
    {
        $this->columnsToUnalter[$column->getName()] = $column;
    }

    public function dropColumn(ColumnInterface $column): void
    {
        $this->columnsToDrop[$column->getName()] = $column;
    }

    public function addForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeysToAdd[$foreignKey->getName()] = $foreignKey;
    }

    public function dropForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeysToDrop[$foreignKey->getName()] = $foreignKey;
    }

    public function dropPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKeyToDrop = $primaryKey;
    }

    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKeyToAdd = $primaryKey;
    }

    public function createIndex(IndexInterface $index): void
    {
        $this->indexesToAdd[$index->getName()] = $index;
    }

    public function dropIndex(IndexInterface $index): void
    {
        $this->indexesToDrop[$index->getName()] = $index;
    }

    /** @return array<string, ColumnInterface> */
    public function getDroppedColumns(): array
    {
        return $this->columnsToDrop;
    }

    /** @return array<string, ColumnInterface> */
    public function getAddedColumns(): array
    {
        return $this->columnsToAdd;
    }

    /** @return array<string, ColumnInterface> */
    public function getAlteredColumns(): array
    {
        return $this->columnsToAlter;
    }

    /** @return array<string, ColumnInterface> */
    public function getUnalteredColumns(): array
    {
        return $this->columnsToUnalter;
    }

    /** @return array<string, ForeignKeyInterface> */
    public function getDroppedForeignKeys(): array
    {
        return $this->foreignKeysToDrop;
    }

    /** @return array<string, ForeignKeyInterface> */
    public function getAddedForeignKeys(): array
    {
        return $this->foreignKeysToAdd;
    }

    public function getDroppedPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->primaryKeyToDrop;
    }

    public function getAddedPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->primaryKeyToAdd;
    }

    /** @return array<string, IndexInterface> */
    public function getDroppedIndexes(): array
    {
        return $this->indexesToDrop;
    }

    /** @return array<string, IndexInterface> */
    public function getAddedIndexes(): array
    {
        return $this->indexesToAdd;
    }

    public function getTableOldPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->tableOldPrimaryKey;
    }

    public function setTableOldPrimaryKey(?PrimaryKeyInterface $tableOldPrimaryKey): void
    {
        $this->tableOldPrimaryKey = $tableOldPrimaryKey;
    }

    public function getTableNewPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->tableNewPrimaryKey;
    }

    public function setTableNewPrimaryKey(?PrimaryKeyInterface $tableNewPrimaryKey): void
    {
        $this->tableNewPrimaryKey = $tableNewPrimaryKey;
    }
}
