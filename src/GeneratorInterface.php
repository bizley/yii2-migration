<?php

declare(strict_types=1);

namespace bizley\migration;

interface GeneratorInterface
{
    /**
     * @param string $tableName
     * @param string $migrationName
     * @param bool $generalSchema
     * @param string|null $namespace
     * @return string
     */
    public function generateFor(
        string $tableName,
        string $migrationName,
        bool $generalSchema = true,
        string $namespace = null
    ): string;
}
