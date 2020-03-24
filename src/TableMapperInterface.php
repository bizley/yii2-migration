<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;
use yii\db\TableSchema;

interface TableMapperInterface
{
    /**
     * @param string $table
     * @param array<string> $referencesToPostpone
     * @return StructureInterface
     */
    public function getStructureOf(string $table, array $referencesToPostpone = []): StructureInterface;

    public function getTableSchema(string $table): ?TableSchema;

    public function getSchemaType(): string;

    public function getEngineVersion(): ?string;

    /** @return array<ForeignKeyInterface> */
    public function getSuppressedForeignKeys(): array;
}
