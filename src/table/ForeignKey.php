<?php

declare(strict_types=1);

namespace bizley\migration\table;

class ForeignKey implements ForeignKeyInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var string
     */
    private $referencedTable;

    /**
     * @var array
     */
    private $referencedColumns;

    /**
     * @var string
     */
    private $onDelete;

    /**
     * @var string
     */
    private $onUpdate;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    /**
     * @return string
     */
    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    /**
     * @param string $referencedTable
     */
    public function setReferencedTable(string $referencedTable): void
    {
        $this->referencedTable = $referencedTable;
    }

    /**
     * @return array
     */
    public function getReferencedColumns(): array
    {
        return $this->referencedColumns;
    }

    /**
     * @param array $referencedColumns
     */
    public function setReferencedColumns(array $referencedColumns): void
    {
        $this->referencedColumns = $referencedColumns;
    }

    /**
     * @return string
     */
    public function getOnDelete(): string
    {
        return $this->onDelete;
    }

    /**
     * @param string $onDelete
     */
    public function setOnDelete(string $onDelete): void
    {
        $this->onDelete = $onDelete;
    }

    /**
     * @return string
     */
    public function getOnUpdate(): string
    {
        return $this->onUpdate;
    }

    /**
     * @param string $onUpdate
     */
    public function setOnUpdate(string $onUpdate): void
    {
        $this->onUpdate = $onUpdate;
    }
}
