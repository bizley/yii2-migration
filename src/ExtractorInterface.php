<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureChangeInterface;

interface ExtractorInterface
{
    /**
     * @param string $migration
     * @param array<string> $migrationPaths
     */
    public function extract(string $migration, array $migrationPaths): void;

    /** @return array<string, array<StructureChangeInterface>>|null */
    public function getChanges(): ?array;
}
