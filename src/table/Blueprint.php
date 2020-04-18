<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;

final class Blueprint implements BlueprintInterface
{
    /** @var string */
    private $tableName;

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

    /** @var PrimaryKeyInterface|null */
    private $tableOldPrimaryKey;

    /** @var PrimaryKeyInterface|null */
    private $tableNewPrimaryKey;

    /** @var array<string, IndexInterface> */
    private $indexesToDrop = [];

    /** @var array<string, IndexInterface> */
    private $indexesToAdd = [];

    /** @var array<string> */
    private $description = [];

    /** @var bool */
    private $startFromScratch = false;

    /**
     * Returns table name of the structure.
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Sets table name for the structure.
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Sets flag to indicate that blueprint contains no changes because table requires creation migration.
     */
    public function startFromScratch(): void
    {
        $this->startFromScratch = true;
    }

    /**
     * Checks if blueprint contains no changes because table requires creation migration.
     * @return bool
     */
    public function needsStartFromScratch(): bool
    {
        return $this->startFromScratch;
    }

    /**
     * Adds single description of the change.
     * @param string $description
     */
    public function addDescription(string $description): void
    {
        $this->description[] = $description;
    }

    /**
     * Returns changes descriptions.
     * @return array<string>
     */
    public function getDescriptions(): array
    {
        return $this->description;
    }

    /**
     * Checks if blueprint is ready to proceed with the update of table.
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->startFromScratch === true || count($this->description) > 0;
    }

    /**
     * Adds added column.
     * @param ColumnInterface $column
     */
    public function addColumn(ColumnInterface $column): void
    {
        $this->columnsToAdd[$column->getName()] = $column;
    }

    /**
     * Adds altered column.
     * @param ColumnInterface $column
     */
    public function alterColumn(ColumnInterface $column): void
    {
        $this->columnsToAlter[$column->getName()] = $column;
    }

    /**
     * Adds unaltered column.
     * @param ColumnInterface $column
     */
    public function reverseColumn(ColumnInterface $column): void
    {
        $this->columnsToUnalter[$column->getName()] = $column;
    }

    /**
     * Adds dropped column.
     * @param ColumnInterface $column
     */
    public function dropColumn(ColumnInterface $column): void
    {
        $this->columnsToDrop[$column->getName()] = $column;
    }

    /**
     * Adds added foreign key.
     * @param ForeignKeyInterface $foreignKey
     */
    public function addForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeysToAdd[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * Adds dropped foreign key.
     * @param ForeignKeyInterface $foreignKey
     */
    public function dropForeignKey(ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeysToDrop[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * Adds dropped primary key.
     * @param PrimaryKeyInterface $primaryKey
     */
    public function dropPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKeyToDrop = $primaryKey;
    }

    /**
     * Adds added primary key.
     * @param PrimaryKeyInterface $primaryKey
     */
    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void
    {
        $this->primaryKeyToAdd = $primaryKey;
    }

    /**
     * Adds added index.
     * @param IndexInterface $index
     */
    public function addIndex(IndexInterface $index): void
    {
        $this->indexesToAdd[$index->getName()] = $index;
    }

    /**
     * Adds dropped index.
     * @param IndexInterface $index
     */
    public function dropIndex(IndexInterface $index): void
    {
        $this->indexesToDrop[$index->getName()] = $index;
    }

    /**
     * Returns dropped columns.
     * @return array<string, ColumnInterface>
     */
    public function getDroppedColumns(): array
    {
        return $this->columnsToDrop;
    }

    /**
     * Returns added columns.
     * @return array<string, ColumnInterface>
     */
    public function getAddedColumns(): array
    {
        return $this->columnsToAdd;
    }

    /**
     * Returns altered columns.
     * @return array<string, ColumnInterface>
     */
    public function getAlteredColumns(): array
    {
        return $this->columnsToAlter;
    }

    /**
     * Returns unaltered columns.
     * @return array<string, ColumnInterface>
     */
    public function getUnalteredColumns(): array
    {
        return $this->columnsToUnalter;
    }

    /**
     * Returns dropped columns.
     * @return array<string, ForeignKeyInterface>
     */
    public function getDroppedForeignKeys(): array
    {
        return $this->foreignKeysToDrop;
    }

    /**
     * Returns added foreign keys.
     * @return array<string, ForeignKeyInterface>
     */
    public function getAddedForeignKeys(): array
    {
        return $this->foreignKeysToAdd;
    }

    /**
     * Returns dropped primary key.
     * @return PrimaryKeyInterface|null
     */
    public function getDroppedPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->primaryKeyToDrop;
    }

    /**
     * Returns added primary key.
     * @return PrimaryKeyInterface|null
     */
    public function getAddedPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->primaryKeyToAdd;
    }

    /**
     * Returns dropped indexes.
     * @return array<string, IndexInterface>
     */
    public function getDroppedIndexes(): array
    {
        return $this->indexesToDrop;
    }

    /**
     * Returns added indexes.
     * @return array<string, IndexInterface>
     */
    public function getAddedIndexes(): array
    {
        return $this->indexesToAdd;
    }

    /**
     * Returns old table's primary key.
     * @return PrimaryKeyInterface|null
     */
    public function getTableOldPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->tableOldPrimaryKey;
    }

    /**
     * Sets old table's primary key.
     * @param PrimaryKeyInterface|null $tableOldPrimaryKey
     */
    public function setTableOldPrimaryKey(?PrimaryKeyInterface $tableOldPrimaryKey): void
    {
        $this->tableOldPrimaryKey = $tableOldPrimaryKey;
    }

    /**
     * Returns new table's primary key.
     * @return PrimaryKeyInterface|null
     */
    public function getTableNewPrimaryKey(): ?PrimaryKeyInterface
    {
        return $this->tableNewPrimaryKey;
    }

    /**
     * Sets new table's primary key.
     * @param PrimaryKeyInterface|null $tableNewPrimaryKey
     */
    public function setTableNewPrimaryKey(?PrimaryKeyInterface $tableNewPrimaryKey): void
    {
        $this->tableNewPrimaryKey = $tableNewPrimaryKey;
    }
}
