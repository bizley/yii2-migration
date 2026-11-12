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

    /**
     * Returns the index method (like "hash" or "gin" in PostgreSQL) or null for the default one.
     */
    public function getMethod(): ?string;

    /**
     * Returns the raw index definition (the part of CREATE INDEX statement starting with USING, like
     * "USING gin (name gin_trgm_ops)") for indexes that cannot be created with createIndex() or null for others.
     * Such index has no columns.
     */
    public function getDefinition(): ?string;
}
