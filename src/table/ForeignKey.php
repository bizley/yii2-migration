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

    /**
     * Returns name of the foreign key.
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets name for the foreign key.
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns columns of the foreign key.
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Sets columns for the foreign key.
     * @param array<string>|null $columns
     */
    public function setColumns(?array $columns): void
    {
        if ($columns !== null) {
            $this->columns = $columns;
        }
    }

    /**
     * Returns referred table name of the foreign key.
     * @return string
     */
    public function getReferredTable(): string
    {
        return $this->referredTable;
    }

    /**
     * Sets referred table name for the foreign key.
     * @param string $referredTable
     */
    public function setReferredTable(string $referredTable): void
    {
        $this->referredTable = $referredTable;
    }

    /**
     * Returns referred column of the foreign key.
     * @return array<string>
     */
    public function getReferredColumns(): array
    {
        return $this->referredColumns;
    }

    /**
     * Sets referred columns for the foreign key.
     * @param array<string> $referredColumns
     */
    public function setReferredColumns(array $referredColumns): void
    {
        $this->referredColumns = $referredColumns;
    }

    /**
     * Returns ON DELETE statement of the foreign key.
     * @return string|null
     */
    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    /**
     * Sets ON DELETE statement for the foreign key.
     * @param string|null $onDelete
     */
    public function setOnDelete(?string $onDelete): void
    {
        $this->onDelete = $onDelete;
    }

    /**
     * Returns ON UPDATE statement of the foreign key.
     * @return string|null
     */
    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }

    /**
     * Sets ON UPDATE statement for the foreign key.
     * @param string|null $onUpdate
     */
    public function setOnUpdate(?string $onUpdate): void
    {
        $this->onUpdate = $onUpdate;
    }

    /**
     * Returns table name of the foreign key.
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Sets table name for the foreign key.
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }
}
