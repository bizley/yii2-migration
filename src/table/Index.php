<?php

declare(strict_types=1);

namespace bizley\migration\table;

final class Index implements IndexInterface
{
    private ?string $name = null;

    private bool $unique = false;

    private ?string $method = null;

    private ?string $definition = null;

    /** @var array<string> */
    private array $columns = [];

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
     * Returns the index method (like "hash" or "gin" in PostgreSQL) or null for the default one.
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Sets the index method or null for the default one.
     */
    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    /**
     * Returns the raw index definition (the part of CREATE INDEX statement starting with USING) or null.
     */
    public function getDefinition(): ?string
    {
        return $this->definition;
    }

    /**
     * Sets the raw index definition (the part of CREATE INDEX statement starting with USING) or null.
     */
    public function setDefinition(?string $definition): void
    {
        $this->definition = $definition;
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
