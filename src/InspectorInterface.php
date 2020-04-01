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
     * @param StructureInterface $newStructure
     * @param bool $onlyShow
     * @param array<string> $migrationsToSkip
     * @param array<string> $migrationPaths
     * @param string|null $schema
     * @param string|null $engineVersion
     * @return BlueprintInterface
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
