<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;

interface UpdaterInterface
{
    /**
     * @param string $tableName
     * @param bool $onlyShow
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
     * @return BlueprintInterface
     */
    public function prepareBlueprint(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths
    ): BlueprintInterface;

    public function generateFromBlueprint(
        BlueprintInterface $blueprint,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string;
}
