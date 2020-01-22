<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\BooleanColumn;
use bizley\migration\table\Structure;

class BooleanColumnTest extends ColumnTestCase
{
    protected static $column = BooleanColumn::class;

    public function providerForSettingLength(): array
    {
        return [
            'mssql' => [Structure::SCHEMA_MSSQL, null, null],
            'oci' => [Structure::SCHEMA_OCI, 1, 1],
            'pgsql' => [Structure::SCHEMA_PGSQL, null, null],
            'sqlite' => [Structure::SCHEMA_SQLITE, null, null],
            'cubrid' => [Structure::SCHEMA_CUBRID, null, null],
            'mysql' => [Structure::SCHEMA_MYSQL, 1, 1]
        ];
    }

    public function providerForGettingLength(): array
    {
        return [
            'mssql' => [Structure::SCHEMA_MSSQL, null],
            'oci' => [Structure::SCHEMA_OCI, 1],
            'pgsql' => [Structure::SCHEMA_PGSQL, null],
            'sqlite' => [Structure::SCHEMA_SQLITE, null],
            'cubrid' => [Structure::SCHEMA_CUBRID, null],
            'mysql' => [Structure::SCHEMA_MYSQL, 1]
        ];
    }
}
