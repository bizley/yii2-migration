<?php

declare(strict_types=1);

namespace bizley\migration;

final class SchemaEnum
{
    public const CUBRID = 'cubrid';
    public const MSSQL = 'mssql';
    public const MYSQL = 'mysql';
    public const OCI = 'oci';
    public const PGSQL = 'pgsql';
    public const SQLITE = 'sqlite';
}
