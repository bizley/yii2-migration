<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;

final class Blueprint implements BlueprintInterface
{
    /** @var string|null */
    private $tableName;

    /** @var array */
    private $columnsToDrop = [];

    /** @var array */
    private $columnsToAdd = [];

    /** @var array */
    private $columnsToAlter = [];

    /** @var array */
    private $columnsToReverse = [];

    /** @var array */
    private $foreignKeysToDrop = [];

    /** @var array */
    private $foreignKeysToAdd = [];

    /** @var PrimaryKeyInterface|null */
    private $primaryKeyToDrop;

    /** @var PrimaryKeyInterface|null */
    private $primaryKeyToAdd;

    /** @var array */
    private $indexesToDrop = [];

    /** @var array */
    private $indexesToAdd = [];

    /** @var array */
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
        $this->columnsToReverse[$column->getName()] = $column;
    }

    public function dropColumn(string $name): void
    {
        $this->columnsToDrop[] = $name;
    }

    public function addForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeysToAdd[$foreignKey->getName()] = $foreignKey;
    }

    public function dropForeignKey(string $name): void
    {
        $this->foreignKeysToDrop[] = $name;
    }

    public function dropPrimaryKey(string $name): void
    {
        $this->primaryKeyToDrop = $name;
    }

    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKeyToAdd = $primaryKey;
    }

    public function createIndex(IndexInterface $index): void
    {
        $this->indexesToAdd[$index->getName()] = $index;
    }

    public function dropIndex(string $name): void
    {
        $this->indexesToDrop[] = $name;
    }

    public function getDroppedColumns(): array
    {
        return $this->columnsToDrop;
    }

    public function getAddedColumns(): array
    {
        return $this->columnsToAdd;
    }

    public function getAlteredColumns(): array
    {
        return $this->columnsToAlter;
    }

    public function getReversedColumns(): array
    {
        return $this->columnsToReverse;
    }

    public function getDroppedForeignKeys(): array
    {
        return $this->foreignKeysToDrop;
    }

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

    public function getDroppedIndexes(): array
    {
        return $this->indexesToDrop;
    }

    public function getAddedIndexes(): array
    {
        return $this->indexesToAdd;
    }
}
