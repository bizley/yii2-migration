<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;
use function in_array;

final class PrimaryKey implements PrimaryKeyInterface
{
    public const GENERIC_PRIMARY_KEY = 'PRIMARYKEY';

    /** @var string|null */
    private $name;

    /** @var array<string> */
    private $columns = [];

    /**
     * Checks if primary key is composite.
     * @return bool
     */
    public function isComposite(): bool
    {
        return count($this->columns) > 1;
    }

    public function getName(): string
    {
        return $this->name ?? self::GENERIC_PRIMARY_KEY;
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

    /**
     * Adds column to the key.
     * @param string $name
     */
    public function addColumn(string $name): void
    {
        if (!in_array($name, $this->columns, true)) {
            $this->columns[] = $name;
        }
    }
}
