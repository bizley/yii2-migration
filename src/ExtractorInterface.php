<?php

declare(strict_types=1);

namespace bizley\migration;

interface ExtractorInterface
{
    public function extract(string $migration, array $migrationPaths): void;

    public function getChanges(): ?array;
}
