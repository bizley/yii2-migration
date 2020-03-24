<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class Index implements IndexInterface
{
    /** @var string */
    private $name;

    /** @var bool */
    private $unique = false;

    /** @var array<string> */
    private $columns = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
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
}
