<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class PrimaryKey implements PrimaryKeyInterface
{
    public const GENERIC_PRIMARY_KEY = 'PRIMARYKEY';

    /** @var string|null */
    private $name;

    /** @var array<string> */
    private $columns = [];

    /**
     * Checks whether the primary key is composite.
     */
    public function isComposite(): bool
    {
        return \count($this->columns) > 1;
    }

    /**
     * Returns name of the primary key.
     */
    public function getName(): string
    {
        return $this->name ?? self::GENERIC_PRIMARY_KEY;
    }

    /**
     * Sets name for the primary key.
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns columns of the primary key.
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Sets columns for the primary key.
     * @param array<string>|null $columns
     */
    public function setColumns(?array $columns): void
    {
        if ($columns !== null) {
            $this->columns = $columns;
        }
    }

    /**
     * Adds column to the primary key.
     */
    public function addColumn(string $name): void
    {
        if (!\in_array($name, $this->columns, true)) {
            $this->columns[] = $name;
        }
    }
}
