<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;

final class Blueprint implements BlueprintInterface
{
    /** @var array */
    private $columnsToDrop = [];

    /** @var array */
    private $columnsToAdd = [];

    /** @var array */
    private $columnsToAlter = [];

    /** @var array */
    private $foreignKeysToDrop = [];

    /** @var array */
    private $foreignKeysToAdd = [];

    /** @var string */
    private $primaryKeyToDrop;

    /** @var PrimaryKey */
    private $primaryKeyToAdd;

    /** @var array */
    private $indexesToDrop = [];

    /** @var array */
    private $indexToAdd = [];

    /** @var array */
    private $description = [];

    /** @var bool */
    private $startFromScratch = false;

    public function setStartFromScratch(bool $startFromScratch): void
    {
        $this->startFromScratch = $startFromScratch;
    }

    public function addDescription(string $description): void
    {
        $this->description[] = $description;
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
        $this->indexToAdd[$index->getName()] = $index;
    }

    public function dropIndex(string $name): void
    {
        $this->indexesToDrop[] = $name;
    }

    public function getAddedColumns(): array
    {
        return $this->columnsToAdd;
    }

    public function getAlteredColumns(): array
    {
        return $this->columnsToAlter;
    }
}
