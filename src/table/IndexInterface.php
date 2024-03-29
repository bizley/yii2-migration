<?php

declare(strict_types=1);

namespace bizley\migration\table;

interface IndexInterface
{
    /**
     * Return name of the index.
     */
    public function getName(): ?string;

    /**
     * Return columns of the index.
     * @return array<string>
     */
    public function getColumns(): array;

    /**
     * Checks whether the index is unique.
     */
    public function isUnique(): bool;
}
