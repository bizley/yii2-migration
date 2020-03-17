<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureInterface;
use yii\db\TableSchema;

interface TableMapperInterface
{
    public function getStructureOf(string $table): StructureInterface;
    public function getSchema(string $table): ?TableSchema;
}
