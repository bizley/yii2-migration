<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class ForeignKey implements ForeignKeyInterface
{
    /** @var string */
    private $name;

    /** @var array<string> */
    private $columns;

    /** @var string */
    private $referencedTable;

    /** @var array<string> */
    private $referencedColumns;

    /** @var string */
    private $onDelete;

    /** @var string */
    private $onUpdate;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @param array<string> $columns */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    public function setReferencedTable(string $referencedTable): void
    {
        $this->referencedTable = $referencedTable;
    }

    /** @return array<string> */
    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    /** @param array<string> $referencedColumns */
    public function setReferencedColumns(array $referencedColumns): void
    {
        $this->referencedColumns = $referencedColumns;
    }

    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    public function setOnDelete(?string $onDelete): void
    {
        $this->onDelete = $onDelete;
    }

    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }

    public function setOnUpdate(?string $onUpdate): void
    {
        $this->onUpdate = $onUpdate;
    }
}
