<?php

declare(strict_types=1);

namespace bizley\migration;

use bizley\migration\table\ForeignKeyInterface;

interface GeneratorInterface
{
    /**
     * @param string $tableName
     * @param string $migrationName
     * @param array<string> $referencesToPostpone
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
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
     * @param array<ForeignKeyInterface> $foreignKeys
     * @param string $tableName
     * @param string $migrationName
     * @param bool $usePrefix
     * @param string $dbPrefix
     * @param string|null $namespace
     * @return string
     */
    public function generateForForeignKeys(
        array $foreignKeys,
        string $tableName,
        string $migrationName,
        bool $usePrefix = true,
        string $dbPrefix = '',
        string $namespace = null
    ): string;

    /**
     * @return array<ForeignKeyInterface>
     */
    public function getSuppressedForeignKeys(): array;
}
