<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\BlueprintInterface;
use bizley\migration\table\StructureInterface;
use yii\base\NotSupportedException;

interface ComparatorInterface
{
    /**
     * Compares migration virtual structure with database structure and gathers required modifications.
     * @param StructureInterface $newStructure
     * @param StructureInterface $oldStructure
     * @param BlueprintInterface $blueprint
     * @param bool $onlyShow whether changes should be only displayed
     * @param string|null $schema
     * @param string|null $engineVersion
     * @throws NotSupportedException
     */
    public function compare(
        StructureInterface $newStructure,
        StructureInterface $oldStructure,
        BlueprintInterface $blueprint,
        bool $onlyShow,
        ?string $schema,
        ?string $engineVersion
    ): void;
}
