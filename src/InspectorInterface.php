<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureInterface;

interface InspectorInterface
{
    public function prepareBlueprint(
        StructureInterface $newStructure,
        bool $onlyShow,
        array $migrationsToSkip = [],
        array $migrationPaths = []
    ): BlueprintInterface;
}
