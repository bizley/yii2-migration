<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\dummy\MigrationSqlInterface;

final class SqlExtractMigration implements MigrationSqlInterface
{
    public function up(): bool
    {
        return true;
    }

    public function down(): bool
    {
        return true;
    }

    public function getStatements(): array
    {
        return ['sql1', 'sql2'];
    }
}
