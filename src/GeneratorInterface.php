<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ForeignKeyInterface;
use yii\base\NotSupportedException;

interface GeneratorInterface
{
    /**
     * Generates migration for the table.
     * @param string $tableName
     * @param string $migrationName
     * @param array<string> $referencesToPostpone
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
     * @throws TableMissingException
     * @throws NotSupportedException
     */
    public function generateForTable(
        string $tableName,
        string $migrationName,
        array $referencesToPostpone = [],
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string;

    /**
     * Generates the migration for the foreign keys.
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param string $migrationName
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
     */
    public function generateForForeignKeys(
        array $foreignKeys,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string;

    /**
     * Returns the suppressed foreign keys that needs to be added later when generating migrations.
     * @return array<ForeignKeyInterface>
     */
    public function getSuppressedForeignKeys(): array;
}
