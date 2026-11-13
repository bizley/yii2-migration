<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Schema;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Schema as YiiSchema;

/** @group schema */
final class SchemaTest extends TestCase
{
    /**
     * @return array<string, array{class-string, string}>
     */
    public function providerForSchema(): array
    {
        return [
            'cubrid' => [\yii\db\cubrid\Schema::class, Schema::CUBRID],
            'mssql' => [\yii\db\mssql\Schema::class, Schema::MSSQL],
            'mysql' => [\yii\db\mysql\Schema::class, Schema::MYSQL],
            'oci' => [\yii\db\oci\Schema::class, Schema::OCI],
            'pgsql' => [\yii\db\pgsql\Schema::class, Schema::PGSQL],
            'sqlite' => [\yii\db\sqlite\Schema::class, Schema::SQLITE],
            'unsupported' => [\stdClass::class, 'unsupported'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchema
     * @param class-string $schemaClass
     * @param string $expected
     * @throws InvalidConfigException
     */
    public function shouldReturnProperSchemaType(string $schemaClass, string $expected): void
    {
        $schema = Yii::createObject(
            [
                'class' => $schemaClass,
                'db' => $this->createMock(Connection::class)
            ]
        );
        self::assertSame($expected, Schema::identifySchema($schema));
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldCheckIfSchemaIsSqlite(): void
    {
        self::assertTrue(Schema::isSQLite(Yii::createObject(\yii\db\sqlite\Schema::class)));
        self::assertFalse(Schema::isSQLite(new \stdClass()));
    }

    /** @test */
    public function shouldReturnNoDefaultLengthForNoSchema(): void
    {
        self::assertNull(self::withoutDeprecations(static fn () => Schema::getDefaultLength(null, YiiSchema::TYPE_PK)));
    }

    /**
     * Runs the callback and fails when it triggers a deprecation (i.e. null used as an array offset in PHP 8.5+).
     * @param callable(): mixed $callback
     * @return mixed
     */
    private static function withoutDeprecations(callable $callback)
    {
        $deprecations = [];
        \set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;
                return true;
            },
            \E_DEPRECATED | \E_USER_DEPRECATED
        );
        try {
            $result = $callback();
        } finally {
            \restore_error_handler();
        }
        self::assertSame([], $deprecations);

        return $result;
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForCubrid(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT, '7'],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE, '15'],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL, '10, 0'],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForCubrid
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForCubrid(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::CUBRID, $type));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForMSSQL(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT, 'max'],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL, '18, 0'],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY, 'max'],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForMSSQL
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForMSSQL(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::MSSQL, $type));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForMySQL(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '11'],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK, '10'],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK, '20'],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK, '20'],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT, '3'],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT, '6'],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER, '11'],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT, '20'],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL, '10, 0'],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN, '1'],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForMySQL
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForMySQL(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::MYSQL, $type));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForMySQLPlus(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '11'],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK, '10'],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK, '20'],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK, '20'],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT, '3'],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT, '6'],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER, '11'],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT, '20'],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL, '10, 0'],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME, '0'],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP, '0'],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME, '0'],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN, '1'],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForMySQLPlus
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForMySQLPlus(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::MYSQL, $type, '5.6.4'));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForOCI(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '10'],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK, '10'],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK, '20'],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK, '20'],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT, '3'],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT, '5'],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER, '10'],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT, '20'],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN, '1'],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForOCI
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForOCI(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::OCI, $type));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForPgSQL(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL, '10, 0'],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME, '0'],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP, '0'],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME, '0'],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForPgSQL
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForPgSQL(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::PGSQL, $type));
    }

    /**
     * @return array<string, array{0: string, 1?: string|null}>
     */
    public function providerForSchemaDefaultLengthForSQLite(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK],
            YiiSchema::TYPE_UPK => [YiiSchema::TYPE_UPK],
            YiiSchema::TYPE_BIGPK => [YiiSchema::TYPE_BIGPK],
            YiiSchema::TYPE_UBIGPK => [YiiSchema::TYPE_UBIGPK],
            YiiSchema::TYPE_CHAR => [YiiSchema::TYPE_CHAR, '1'],
            YiiSchema::TYPE_STRING => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_TEXT => [YiiSchema::TYPE_TEXT],
            YiiSchema::TYPE_TINYINT => [YiiSchema::TYPE_TINYINT],
            YiiSchema::TYPE_SMALLINT => [YiiSchema::TYPE_SMALLINT],
            YiiSchema::TYPE_INTEGER => [YiiSchema::TYPE_INTEGER],
            YiiSchema::TYPE_BIGINT => [YiiSchema::TYPE_BIGINT],
            YiiSchema::TYPE_FLOAT => [YiiSchema::TYPE_FLOAT],
            YiiSchema::TYPE_DOUBLE => [YiiSchema::TYPE_DOUBLE],
            YiiSchema::TYPE_DECIMAL => [YiiSchema::TYPE_DECIMAL, '10, 0'],
            YiiSchema::TYPE_DATETIME => [YiiSchema::TYPE_DATETIME],
            YiiSchema::TYPE_TIMESTAMP => [YiiSchema::TYPE_TIMESTAMP],
            YiiSchema::TYPE_TIME => [YiiSchema::TYPE_TIME],
            YiiSchema::TYPE_DATE => [YiiSchema::TYPE_DATE],
            YiiSchema::TYPE_BINARY => [YiiSchema::TYPE_BINARY],
            YiiSchema::TYPE_BOOLEAN => [YiiSchema::TYPE_BOOLEAN],
            YiiSchema::TYPE_MONEY => [YiiSchema::TYPE_MONEY, '19, 4'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaDefaultLengthForSQLite
     * @param string|null $expected
     * @param string $type
     */
    public function shouldReturnProperDefaultLengthForSQLite(string $type, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getDefaultLength(Schema::SQLITE, $type));
    }

    /** @test */
    public function shouldReturnNoAliasForNoSchema(): void
    {
        self::assertNull(self::withoutDeprecations(static fn () => Schema::getAlias(null, YiiSchema::TYPE_PK, '11')));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: string|null}>
     */
    public function providerForSchemaAliasesForCubrid(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '1'],
            YiiSchema::TYPE_STRING . '1' => [YiiSchema::TYPE_STRING, '', 'text()'],
            YiiSchema::TYPE_STRING . '2' => [YiiSchema::TYPE_STRING, '2'],
            YiiSchema::TYPE_DECIMAL . '1' => [YiiSchema::TYPE_DECIMAL, '19, 4', 'money()'],
            YiiSchema::TYPE_DECIMAL . '2' => [YiiSchema::TYPE_DECIMAL, '11, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaAliasesForCubrid
     * @param string $type
     * @param string $length
     * @param string|null $expected
     */
    public function shouldReturnProperAliasForCubrid(string $type, string $length, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getAlias(Schema::CUBRID, $type, $length));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: string|null}>
     */
    public function providerForSchemaAliasesForMSSQL(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '1'],
            YiiSchema::TYPE_STRING . '1' => [YiiSchema::TYPE_STRING, 'max', 'text()'],
            YiiSchema::TYPE_STRING . '2' => [YiiSchema::TYPE_STRING, '255'],
            YiiSchema::TYPE_DECIMAL . '1' => [YiiSchema::TYPE_DECIMAL, '19, 4', 'money()'],
            YiiSchema::TYPE_DECIMAL . '2' => [YiiSchema::TYPE_DECIMAL, '11, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaAliasesForMSSQL
     * @param string $type
     * @param string $length
     * @param string|null $expected
     */
    public function shouldReturnProperAliasForMSSQL(string $type, string $length, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getAlias(Schema::MSSQL, $type, $length));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: string|null}>
     */
    public function providerForSchemaAliasesForMySQL(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '1'],
            YiiSchema::TYPE_TINYINT . '1' => [YiiSchema::TYPE_TINYINT, '1', 'boolean()'],
            YiiSchema::TYPE_TINYINT . '2' => [YiiSchema::TYPE_TINYINT, '2'],
            YiiSchema::TYPE_DECIMAL . '1' => [YiiSchema::TYPE_DECIMAL, '19, 4', 'money()'],
            YiiSchema::TYPE_DECIMAL . '2' => [YiiSchema::TYPE_DECIMAL, '11, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaAliasesForMySQL
     * @param string $type
     * @param string $length
     * @param string|null $expected
     */
    public function shouldReturnProperAliasForMySQL(string $type, string $length, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getAlias(Schema::MYSQL, $type, $length));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: string|null}>
     */
    public function providerForSchemaAliasesForOCI(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '1'],
            YiiSchema::TYPE_INTEGER . '1' => [YiiSchema::TYPE_INTEGER, '1', 'boolean()'],
            YiiSchema::TYPE_INTEGER . '2' => [YiiSchema::TYPE_INTEGER, '3', 'tinyInteger()'],
            YiiSchema::TYPE_INTEGER . '3' => [YiiSchema::TYPE_INTEGER, '5', 'smallInteger()'],
            YiiSchema::TYPE_INTEGER . '4' => [YiiSchema::TYPE_INTEGER, '20', 'bigInteger()'],
            YiiSchema::TYPE_INTEGER . '5' => [YiiSchema::TYPE_INTEGER, '11'],
            YiiSchema::TYPE_DECIMAL . '1' => [YiiSchema::TYPE_DECIMAL, '19, 4', 'money()'],
            YiiSchema::TYPE_DECIMAL . '2' => [YiiSchema::TYPE_DECIMAL, '11, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaAliasesForOCI
     * @param string $type
     * @param string $length
     * @param string|null $expected
     */
    public function shouldReturnProperAliasForOCI(string $type, string $length, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getAlias(Schema::OCI, $type, $length));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: string|null}>
     */
    public function providerForSchemaAliasesForPgSQL(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '1'],
            YiiSchema::TYPE_DECIMAL . '1' => [YiiSchema::TYPE_DECIMAL, '19, 4', 'money()'],
            YiiSchema::TYPE_DECIMAL . '2' => [YiiSchema::TYPE_DECIMAL, '11, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaAliasesForPgSQL
     * @param string $type
     * @param string $length
     * @param string|null $expected
     */
    public function shouldReturnProperAliasForPgSQL(string $type, string $length, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getAlias(Schema::PGSQL, $type, $length));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: string|null}>
     */
    public function providerForSchemaAliasesForSQLite(): array
    {
        return [
            YiiSchema::TYPE_PK => [YiiSchema::TYPE_PK, '1'],
            YiiSchema::TYPE_DECIMAL . '1' => [YiiSchema::TYPE_DECIMAL, '19, 4', 'money()'],
            YiiSchema::TYPE_DECIMAL . '2' => [YiiSchema::TYPE_DECIMAL, '11, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchemaAliasesForSQLite
     * @param string $type
     * @param string $length
     * @param string|null $expected
     */
    public function shouldReturnProperAliasForSQLite(string $type, string $length, ?string $expected = null): void
    {
        self::assertSame($expected, Schema::getAlias(Schema::PGSQL, $type, $length));
    }

    /**
     * @return array<string, array{string|null, string|null, bool}>
     */
    public function providerForFractionalSeconds(): array
    {
        return [
            'no schema' => [null, '8.0.30', false],
            'pgsql' => [Schema::PGSQL, '15.0', false],
            'mysql no version' => [Schema::MYSQL, null, false],
            'mysql old' => [Schema::MYSQL, '5.6.3', false],
            'mysql 5.6.4' => [Schema::MYSQL, '5.6.4', true],
            'mysql 8' => [Schema::MYSQL, '8.0.30', true],
            'mariadb 10 with compatibility prefix' => [Schema::MYSQL, '5.5.5-10.6.12-MariaDB', true],
            'mariadb 11' => [Schema::MYSQL, '11.4.2-MariaDB', true],
            'mariadb lowercase' => [Schema::MYSQL, '5.5.5-10.11.6-mariadb-1:10.11.6+maria~ubu2204', true],
        ];
    }

    /**
     * @test
     * @dataProvider providerForFractionalSeconds
     */
    public function shouldCheckIfFractionalSecondsAreSupported(
        ?string $schema,
        ?string $engineVersion,
        bool $expected
    ): void {
        self::assertSame($expected, Schema::isFractionalSecondsSupported($schema, $engineVersion));
    }

    /** @test */
    public function shouldReturnFractionalSecondsDefaultLengthForMariaDB(): void
    {
        self::assertSame('0', Schema::getDefaultLength(Schema::MYSQL, 'datetime', '5.5.5-10.6.12-MariaDB'));
        self::assertNull(Schema::getDefaultLength(Schema::MYSQL, 'datetime', '5.5.5'));
    }
}
