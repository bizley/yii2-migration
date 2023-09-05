<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class Index implements IndexInterface
{
    /** @var string|null */
    private $name;

    /** @var bool */
    private $unique = false;

    /** @var array<string> */
    private $columns = [];

    /**
     * Return name of the index.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets name for the index.
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Checks whether the index is unique.
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * Sets unique flag for the index.
     */
    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    /**
     * Return columns of the index.
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Sets columns for the index.
     * @param array<string>|null $columns
     */
    public function setColumns(?array $columns): void
    {
        if ($columns !== null) {
            $this->columns = $columns;
        }
    }
}
