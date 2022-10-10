<?php

declare(strict_types=1);

namespace bizley\migration;

use ErrorException;

/**
 * This interface will be merged with ExtractorInterface and removed in 5.0.
 */
interface SqlExtractorInterface extends ExtractorInterface
{
    /**
     * Extracts migration SQL statements.
     * @param string $migration
     * @param array<string> $migrationPaths
     * @throws ErrorException
     */
    public function getSql(string $migration, array $migrationPaths, string $method): void;

    /**
     * Returns the SQL statements extracted from migrations.
     * @return string[]
     */
    public function getStatements(): array;
}
