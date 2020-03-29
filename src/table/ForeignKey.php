<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class ForeignKey implements ForeignKeyInterface
{
    /** @var string|null */
    private $name;

    /** @var array<string> */
    private $columns = [];

    /** @var string */
    private $referredTable;

    /** @var array<string> */
    private $referredColumns = [];

    /** @var string|null */
    private $onDelete;

    /** @var string|null */
    private $onUpdate;

    /** @var string */
    private $tableName;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /** @return array<string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @param array<string>|null $columns */
    public function setColumns(?array $columns): void
    {
        if ($columns !== null) {
            $this->columns = $columns;
        }
    }

    public function getReferredTable(): string
    {
        return $this->referredTable;
    }

    public function setReferredTable(string $referredTable): void
    {
        $this->referredTable = $referredTable;
    }

    /** @return array<string> */
    public function getReferredColumns(): array
    {
        return $this->referredColumns;
    }

    /** @param array<string> $referredColumns */
    public function setReferredColumns(array $referredColumns): void
    {
        $this->referredColumns = $referredColumns;
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

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }
}
