<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class Blueprint implements BlueprintInterface
{
    /**
     * @var array
     */
    private $columnsToDrop = [];

    /**
     * @var array<ColumnInterface>
     */
    private $columnsToAdd = [];

    /**
     * @var array
     */
    private $columnsToAlter = [];

    /**
     * @var array
     */
    private $foreignKeysToDrop = [];

    /**
     * @var array
     */
    private $foreignKeysToAdd = [];

    /**
     * @var string
     */
    private $primaryKeyToDrop;

    /**
     * @var PrimaryKey
     */
    private $primaryKeyToAdd;

    /**
     * @var array
     */
    private $indexesToDrop = [];

    /**
     * @var array
     */
    private $indexToAdd = [];

    public function addColumn(string $name, ColumnInterface $column): void
    {
        $this->columnsToAdd[$name] = $column;
    }

    public function alterColumn(string $name, ColumnInterface $column): void
    {
        $this->columnsToAlter[$name] = $column;
    }

    public function dropColumn(string $name): void
    {
        $this->columnsToDrop[] = $name;
    }

    public function addForeignKey(string $name, ForeignKeyInterface $foreignKey): void
    {
        $this->foreignKeysToAdd[$name] = $foreignKey;
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

    public function getAddedColumns(): array
    {
        return $this->columnsToAdd;
    }

    public function getAlteredColumns(): array
    {
        return $this->columnsToAlter;
    }
}
