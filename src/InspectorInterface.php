<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureInterface;
use ErrorException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

interface InspectorInterface
{
    /**
     * Prepares a blueprint for the upcoming update.
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
     * @throws InvalidConfigException
     * @throws ErrorException
     * @throws NotSupportedException
     */
    public function prepareBlueprint(
        StructureInterface $newStructure,
        bool $onlyShow,
        array $migrationsToSkip,
        array $migrationPaths,
        ?string $schema,
        ?string $engineVersion
    ): BlueprintInterface;
}
