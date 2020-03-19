<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface BlueprintInterface
{
    public function setStartFromScratch(bool $startFromScratch): void;

    public function addDescription(string $description): void;

    public function isPending(): bool;

    public function addColumn(ColumnInterface $column): void;

    public function alterColumn(ColumnInterface $column): void;

    public function dropColumn(string $name): void;

    public function addForeignKey(ForeignKeyInterface $foreignKey): void;

    public function dropForeignKey(string $name): void;

    public function dropPrimaryKey(string $name): void;

    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function createIndex(IndexInterface $index): void;

    public function dropIndex(string $name): void;

    public function getAddedColumns(): array;

    public function getAlteredColumns(): array;
}
