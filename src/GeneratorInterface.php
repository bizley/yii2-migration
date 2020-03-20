<?php

declare(strict_types=1);

namespace bizley\migration;

interface GeneratorInterface
{
    /**
     * @param string $tableName
     * @param string $migrationName
     * @param array $referencesToPostpone
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
     * @param array $foreignKeys
     * @param string $migrationName
     * @param string|null $namespace
     * @return string
     */
    public function generateForForeignKeys(
        array $foreignKeys,
        string $migrationName,
        string $namespace = null
    ): string;

    public function getSuppressedForeignKeys(): array;
}
