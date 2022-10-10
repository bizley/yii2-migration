<?php

declare(strict_types=1);

namespace bizley\migration\dummy;

use bizley\migration\table\StructureChangeInterface;
use yii\db\MigrationInterface;

interface MigrationChangesInterface extends MigrationInterface
{
    /** @return array<string, array<StructureChangeInterface>> */
    public function getChanges(): ?array;
}
