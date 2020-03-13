<?php

declare(strict_types=1);

namespace bizley\migration\table;

use function count;
use function in_array;

final class PrimaryKey implements PrimaryKeyInterface
{
    public const GENERIC_PRIMARY_KEY = 'PRIMARYKEY';

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * Checks if primary key is composite.
     * @return bool
     */
    public function isComposite(): bool
    {
        return count($this->columns) > 1;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? self::GENERIC_PRIMARY_KEY;
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
