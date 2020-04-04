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

    /** @var array */
    private static $defaultLength = [
        self::CUBRID => [
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_FLOAT => '7',
            YiiSchema::TYPE_DOUBLE => '15',
            YiiSchema::TYPE_DECIMAL => '10, 0',
            YiiSchema::TYPE_MONEY => '19, 4',
        ],
        self::MSSQL => [
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_TEXT => 'max',
            YiiSchema::TYPE_DECIMAL => '18, 0',
            YiiSchema::TYPE_BINARY => 'max',
            YiiSchema::TYPE_MONEY => '19, 4',
        ],
        self::MYSQL => [
            YiiSchema::TYPE_PK => '11',
            YiiSchema::TYPE_UPK => '10',
            YiiSchema::TYPE_BIGPK => '20',
            YiiSchema::TYPE_UBIGPK => '20',
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_TINYINT => '3',
            YiiSchema::TYPE_SMALLINT => '6',
            YiiSchema::TYPE_INTEGER => '11',
            YiiSchema::TYPE_BIGINT => '20',
            YiiSchema::TYPE_DECIMAL => '10, 0',
            YiiSchema::TYPE_BOOLEAN => '1',
            YiiSchema::TYPE_MONEY => '19, 4',
        ],
        self::MYSQL . '+' => [
            YiiSchema::TYPE_PK => '11',
            YiiSchema::TYPE_UPK => '10',
            YiiSchema::TYPE_BIGPK => '20',
            YiiSchema::TYPE_UBIGPK => '20',
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_TINYINT => '3',
            YiiSchema::TYPE_SMALLINT => '6',
            YiiSchema::TYPE_INTEGER => '11',
            YiiSchema::TYPE_BIGINT => '20',
            YiiSchema::TYPE_DECIMAL => '10, 0',
            YiiSchema::TYPE_BOOLEAN => '1',
            YiiSchema::TYPE_MONEY => '19, 4',
            YiiSchema::TYPE_DATETIME => '0',
            YiiSchema::TYPE_TIMESTAMP => '0',
            YiiSchema::TYPE_TIME => '0',
        ],
        self::OCI => [
            YiiSchema::TYPE_PK => '10',
            YiiSchema::TYPE_UPK => '10',
            YiiSchema::TYPE_BIGPK => '20',
            YiiSchema::TYPE_UBIGPK => '20',
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_TINYINT => '3',
            YiiSchema::TYPE_SMALLINT => '5',
            YiiSchema::TYPE_INTEGER => '10',
            YiiSchema::TYPE_BIGINT => '20',
            YiiSchema::TYPE_BOOLEAN => '1',
            YiiSchema::TYPE_MONEY => '19, 4',
        ],
        self::PGSQL => [
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_DECIMAL => '10, 0',
            YiiSchema::TYPE_DATETIME => '0',
            YiiSchema::TYPE_TIMESTAMP => '0',
            YiiSchema::TYPE_TIME => '0',
            YiiSchema::TYPE_MONEY => '19, 4',
        ],
        self::SQLITE => [
            YiiSchema::TYPE_CHAR => '1',
            YiiSchema::TYPE_STRING => '255',
            YiiSchema::TYPE_DECIMAL => '10, 0',
            YiiSchema::TYPE_MONEY => '19, 4',
        ],
    ];

    /** @var array */
    private static $aliases = [
        self::CUBRID => [
            YiiSchema::TYPE_STRING => ['' => 'text()'],
            YiiSchema::TYPE_DECIMAL => ['19, 4' => 'money()'],
        ],
        self::MSSQL => [
            YiiSchema::TYPE_STRING => ['max' => 'text()'],
            YiiSchema::TYPE_DECIMAL => ['19, 4' => 'money()'],
        ],
        self::MYSQL => [
            YiiSchema::TYPE_TINYINT => ['1' => 'boolean()'],
            YiiSchema::TYPE_DECIMAL => ['19, 4' => 'money()'],
        ],
        self::OCI => [
            YiiSchema::TYPE_INTEGER => [
                '1' => 'boolean()',
                '3' => 'tinyInteger()',
                '5' => 'smallInteger()',
                '20' => 'bigInteger()',
            ],
            YiiSchema::TYPE_DECIMAL => ['19, 4' => 'money()'],
        ],
        self::PGSQL => [
            YiiSchema::TYPE_DECIMAL => ['19, 4' => 'money()'],
        ],
        self::SQLITE => [
            YiiSchema::TYPE_DECIMAL => ['19, 4' => 'money()'],
        ],
    ];

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
     * Checks whether the schema is SQLite.
     * @param mixed $schema
     * @return bool
     */
    public static function isSQLite($schema): bool
    {
        return static::identifySchema($schema) === self::SQLITE;
    }

    /**
     * Returns default length based on the schema and column type.
     * For MySQL >= 5.6.4 additional default sizes are available.
     * @param string $schema
     * @param string $type
     * @param string|null $engineVersion
     * @return string|null
     */
    public static function getDefaultLength(string $schema, string $type, string $engineVersion = null): ?string
    {
        if ($engineVersion && $schema === self::MYSQL && version_compare($engineVersion, '5.6.4', '>=')) {
            $schema = self::MYSQL . '+';
        }

        return static::$defaultLength[$schema][$type] ?? null;
    }

    /**
     * Returns alias definition based on the schema, column type, and length.
     * @param string $schema
     * @param string $type
     * @param string $length
     * @return string|null
     */
    public static function getAlias(string $schema, string $type, string $length): ?string
    {
        return static::$aliases[$schema][$type][$length] ?? null;
    }
}
