<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface PrimaryKeyInterface
{
    /**
     * Returns name of the primary key.
     */
    public function getName(): string;

    /**
     * Returns columns of the primary key.
     * @return array<string>
     */
    public function getColumns(): array;

    /**
     * Checks whether the primary key is composite.
     */
    public function isComposite(): bool;

    /**
     * Adds column to the primary key.
     */
    public function addColumn(string $name): void;
}
