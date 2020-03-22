<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;

final class Blueprint implements BlueprintInterface
{
    /** @var string|null */
    private $tableName;

    /** @var PrimaryKeyInterface|null */
    private $tableOldPrimaryKey;

    /** @var PrimaryKeyInterface|null */
    private $tableNewPrimaryKey;

    /** @var array<ColumnInterface> */
    private $columnsToDrop = [];

    /** @var array<ColumnInterface> */
    private $columnsToAdd = [];

    /** @var array<ColumnInterface> */
    private $columnsToAlter = [];

    /** @var array<ColumnInterface> */
    private $columnsToUnalter = [];

    /** @var array<ForeignKeyInterface> */
    private $foreignKeysToDrop = [];

    /** @var array<ForeignKeyInterface> */
    private $foreignKeysToAdd = [];

    /** @var PrimaryKeyInterface|null */
    private $primaryKeyToDrop;

    /** @var PrimaryKeyInterface|null */
    private $primaryKeyToAdd;

    /** @var array<IndexInterface> */
    private $indexesToDrop = [];

    /** @var array<IndexInterface> */
    private $indexesToAdd = [];

    /** @var array<string> */
    private $description = [];

    /** @var bool */
    private $startFromScratch = false;

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setStartFromScratch(bool $startFromScratch): void
    {
        $this->startFromScratch = $startFromScratch;
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

    /** @return array<ColumnInterface> */
    public function getDroppedColumns(): array
    {
        return $this->columnsToDrop;
    }

    /** @return array<ColumnInterface> */
    public function getAddedColumns(): array
    {
        return $this->columnsToAdd;
    }

    /** @return array<ColumnInterface> */
    public function getAlteredColumns(): array
    {
        return $this->columnsToAlter;
    }

    /** @return array<ColumnInterface> */
    public function getUnalteredColumns(): array
    {
        return $this->columnsToUnalter;
    }

    /** @return array<ForeignKeyInterface> */
    public function getDroppedForeignKeys(): array
    {
        return $this->foreignKeysToDrop;
    }

    /** @return array<ForeignKeyInterface> */
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

    /** @return array<IndexInterface> */
    public function getDroppedIndexes(): array
    {
        return $this->indexesToDrop;
    }

    /** @return array<IndexInterface> */
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
