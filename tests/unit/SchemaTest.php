<?php

declare(strict_types=1);

namespace bizley\tests\unit;

use bizley\migration\Schema;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Connection;
use yii\db\Schema as YiiSchema;

/** @group schema */
final class SchemaTest extends TestCase
{
    public function providerForSchema(): array
    {
        return [
            'cubrid' => [\yii\db\cubrid\Schema::class, Schema::CUBRID],
            'mssql' => [\yii\db\mssql\Schema::class, Schema::MSSQL],
            'mysql' => [\yii\db\mysql\Schema::class, Schema::MYSQL],
            'oci' => [\yii\db\oci\Schema::class, Schema::OCI],
            'pgsql' => [\yii\db\pgsql\Schema::class, Schema::PGSQL],
            'sqlite' => [\yii\db\sqlite\Schema::class, Schema::SQLITE],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSchema
     * @param string $schemaClass
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
        $this->assertSame($expected, Schema::identifySchema($schema));
    }

    /**
     * @test
     * @throws InvalidConfigException
     */
    public function shouldCheckIfSchemaIsSqlite(): void
    {
        $this->assertTrue(Schema::isSQLite(Yii::createObject(\yii\db\sqlite\Schema::class)));
        $this->assertFalse(Schema::isSQLite(new stdClass()));
    }

    /** @test */
    public function shouldReturnNoDefaultLengthForNoSchema(): void
    {
        $this->assertNull(Schema::getDefaultLength(null, YiiSchema::TYPE_PK));
    }

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
    public function shouldReturnProperDefaultLengthForCubrid(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::CUBRID, $type));
    }

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
    public function shouldReturnProperDefaultLengthForMSSQL(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::MSSQL, $type));
    }

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
    public function shouldReturnProperDefaultLengthForMySQL(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::MYSQL, $type));
    }

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
    public function shouldReturnProperDefaultLengthForMySQLPlus(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::MYSQL, $type, '5.6.4'));
    }

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
    public function shouldReturnProperDefaultLengthForOCI(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::OCI, $type));
    }

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
    public function shouldReturnProperDefaultLengthForPgSQL(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::PGSQL, $type));
    }

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
    public function shouldReturnProperDefaultLengthForSQLite(string $type, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getDefaultLength(Schema::SQLITE, $type));
    }

    /** @test */
    public function shouldReturnNoAliasForNoSchema(): void
    {
        $this->assertNull(Schema::getAlias(null, YiiSchema::TYPE_PK, '11'));
    }

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
    public function shouldReturnProperAliasForCubrid(string $type, string $length, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getAlias(Schema::CUBRID, $type, $length));
    }

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
    public function shouldReturnProperAliasForMSSQL(string $type, string $length, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getAlias(Schema::MSSQL, $type, $length));
    }

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
    public function shouldReturnProperAliasForMySQL(string $type, string $length, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getAlias(Schema::MYSQL, $type, $length));
    }

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
    public function shouldReturnProperAliasForOCI(string $type, string $length, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getAlias(Schema::OCI, $type, $length));
    }

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
    public function shouldReturnProperAliasForPgSQL(string $type, string $length, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getAlias(Schema::PGSQL, $type, $length));
    }

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
    public function shouldReturnProperAliasForSQLite(string $type, string $length, string $expected = null): void
    {
        $this->assertSame($expected, Schema::getAlias(Schema::PGSQL, $type, $length));
    }
}
