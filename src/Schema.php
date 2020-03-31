<?php

declare(strict_types=1);

namespace bizley\migration;

final class Schema
{
    public const CUBRID = 'cubrid';
    public const MSSQL = 'mssql';
    public const MYSQL = 'mysql';
    public const OCI = 'oci';
    public const PGSQL = 'pgsql';
    public const SQLITE = 'sqlite';

    /**
     * Returns schema code based on its class name.
     * @param mixed $schema
     * @return string
     */
    public static function identifySchema($schema): string
    {
        switch (true) {
            case $schema instanceof \yii\db\mysql\Schema:
                return self::MYSQL;

            case $schema instanceof \yii\db\pgsql\Schema:
                return self::PGSQL;

            case $schema instanceof \yii\db\sqlite\Schema:
                return self::SQLITE;

            case $schema instanceof \yii\db\mssql\Schema:
                return self::MSSQL;

            case $schema instanceof \yii\db\oci\Schema:
                return self::OCI;

            case $schema instanceof \yii\db\cubrid\Schema:
                return self::CUBRID;

            default:
                return 'unsupported';
        }
    }

    /**
     * @param mixed $schema
     * @return bool
     */
    public static function isSQLite($schema): bool
    {
        return static::identifySchema($schema) === self::SQLITE;
    }
}
