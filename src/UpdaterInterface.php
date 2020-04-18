<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use ErrorException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

interface UpdaterInterface
{
    /**
     * Prepares a blueprint for update.
     * @param string $tableName
     * @param bool $onlyShow
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
     * @return BlueprintInterface
     * @throws TableMissingException
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function prepareBlueprint(
        string $tableName,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths
    ): BlueprintInterface;

    /**
     * Generates migration based on the blueprint.
     * @param BlueprintInterface $blueprint
     * @param string $migrationName
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
     * @throws NotSupportedException
     */
    public function generateFromBlueprint(
        BlueprintInterface $blueprint,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string;
}
