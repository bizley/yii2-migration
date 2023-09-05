<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureChangeInterface;
use ErrorException;

interface ExtractorInterface
{
    /**
     * Extracts migration data structures.
     * @param array<string> $migrationPaths
     * @throws ErrorException
     */
    public function extract(string $migration, array $migrationPaths): void;

    /**
     * Returns the changes extracted from migrations.
     * @return array<string, array<StructureChangeInterface>>|null
     */
    public function getChanges(): ?array;
}
