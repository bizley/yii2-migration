<?php

declare(strict_types=1);

namespace bizley\migration;

use yii\db\Schema as YiiSchema;

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
     * @param YiiSchema $schema
     * @return string
     */
    public static function identifySchema(YiiSchema $schema): string
    {
        switch (get_class($schema)) {
            case 'yii\db\mssql\Schema':
                return self::MSSQL;

            case 'yii\db\oci\Schema':
                return self::OCI;

            case 'yii\db\pgsql\Schema':
                return self::PGSQL;

            case 'yii\db\sqlite\Schema':
                return self::SQLITE;

            case 'yii\db\cubrid\Schema':
                return self::CUBRID;

            case 'yii\db\mysql\Schema':
                return self::MYSQL;

            default:
                return 'unsupported';
        }
    }

    public static function isSQLite(YiiSchema $schema): bool
    {
        return static::identifySchema($schema) === self::SQLITE;
    }
}
