<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\StructureInterface;
use yii\db\TableSchema;

interface TableMapperInterface
{
    public function getStructureOf(string $table, array $referencesToPostpone = []): StructureInterface;

    public function getTableSchema(string $table): ?TableSchema;

    public function getSchemaType(): string;

    public function getEngineVersion(): ?string;

    public function getSuppressedForeignKeys(): array;
}
