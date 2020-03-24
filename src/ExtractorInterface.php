<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureChangeInterface;

interface ExtractorInterface
{
    public function extract(string $migration, array $migrationPaths): void;

    /** @return array<string, array<StructureChangeInterface>>|null */
    public function getChanges(): ?array;
}
