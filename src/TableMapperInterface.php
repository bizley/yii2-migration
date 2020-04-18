<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ForeignKeyInterface;
use bizley\migration\table\StructureInterface;
use yii\base\NotSupportedException;
use yii\db\TableSchema;

interface TableMapperInterface
{
    /**
     * Returns a structure of the table.
     * @param string $table
     * @param array<string> $referencesToPostpone
     * @return StructureInterface
     * @throws NotSupportedException
     */
    public function getStructureOf(string $table, array $referencesToPostpone = []): StructureInterface;

    /**
     * Returns a table schema of the table.
     * @param string $table
     * @return TableSchema|null
     */
    public function getTableSchema(string $table): ?TableSchema;

    /**
     * Returns a schema type.
     * @return string
     * @throws NotSupportedException
     */
    public function getSchemaType(): string;

    /**
     * Returns a DB engine version.
     * @return string|null
     */
    public function getEngineVersion(): ?string;

    /**
     * Returns the suppressed foreign keys that must be added in migration at the end.
     * @return array<ForeignKeyInterface>
     */
    public function getSuppressedForeignKeys(): array;
}
