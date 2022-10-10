<?php

declare(strict_types=1);

namespace bizley\tests\stubs;

use bizley\migration\dummy\MigrationSqlInterface;

final class SqlExtractMigration implements MigrationSqlInterface
{
    public function up()
    {
    }

    public function down()
    {
    }

    public function getStatements(): array
    {
        return ['sql1', 'sql2'];
    }
}
