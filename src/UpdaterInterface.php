<?php

declare(strict_types=1);

namespace bizley\migration;

interface UpdaterInterface
{
    public function isUpdateRequired(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths
    ): bool;
}
