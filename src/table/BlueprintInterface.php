<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface BlueprintInterface
{
    public function addColumn(string $name, ColumnInterface $column): void;

    public function alterColumn(string $name, ColumnInterface $column): void;

    public function dropColumn(string $name): void;

    public function addForeignKey(string $name, ForeignKeyInterface $foreignKey): void;

    public function dropForeignKey(string $name): void;

    public function dropPrimaryKey(string $name): void;

    public function addPrimaryKey(PrimaryKeyInterface $primaryKey): void;

    public function getAddedColumns(): array;

    public function getAlteredColumns(): array;
}
