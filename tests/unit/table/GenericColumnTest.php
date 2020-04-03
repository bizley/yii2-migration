<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\Column;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKey;
use PHPUnit\Framework\TestCase;

final class GenericColumnTest extends TestCase
{
    /** @var Column */
    private $column;

    protected function setUp(): void
    {
        $this->column = new class extends Column implements ColumnInterface {
            public function getLength(string $schema = null, string $engineVersion = null)
            {
                return null;
            }

            public function setLength($value, string $schema = null, string $engineVersion = null): void
            {
            }

            public function getDefinition(): string
            {
                return '';
            }
        };
    }

    public function providerForColumnInPrimaryKey(): array
    {
        return [
            'yes 1' => ['test', ['test'], true],
            'yes 2' => ['test', ['other', 'test'], true],
            'no' => ['test', ['different'], false]
        ];
    }

    /**
     * @test
     * @dataProvider providerForColumnInPrimaryKey
     * @param string $name
     * @param array $columns
     * @param bool $expected
     */
    public function shouldCheckIfColumnIsPartOfPrimaryKey(string $name, array $columns, bool $expected): void
    {
        $this->column->setName($name);
        $primaryKey = new PrimaryKey();
        $primaryKey->setColumns($columns);
        $this->assertSame($expected, $this->column->isColumnInPrimaryKey($primaryKey));
    }

    public function providerForPrimaryKeyInfoAppended(): array
    {
        return [
            'empty append' => ['', '', false],
            'mssql proper' => ['IDENTITY PRIMARY KEY', Schema::MSSQL, true],
            'mssql wrong 1' => ['IDENTITY', Schema::MSSQL, false],
            'mssql wrong 2' => ['PRIMARY KEY', Schema::MSSQL, false],
            'mssql wrong 3' => ['something', Schema::MSSQL, false],
            'no mssql proper' => ['PRIMARY KEY', '', true],
            'no mssql wrong' => ['something', '', false],
        ];
    }

    /**
     * @test
     * @dataProvider providerForPrimaryKeyInfoAppended
     * @param string $append
     * @param string $schema
     * @param bool $expected
     */
    public function shouldCheckIfPrimaryKeyInfoIsAppended(string $append, string $schema, bool $expected): void
    {
        $this->column->setAppend($append);
        $this->assertSame($expected, $this->column->isPrimaryKeyInfoAppended($schema));
    }

    public function providerForPrepareSchemaAppend(): array
    {
        return [
            'mssql' => [Schema::MSSQL, false, false, null],
            'mssql pk' => [Schema::MSSQL, true, false, 'IDENTITY PRIMARY KEY'],
            'mssql ai' => [Schema::MSSQL, false, true, null],
            'mssql pk+ai' => [Schema::MSSQL, true, true, 'IDENTITY PRIMARY KEY'],
            'mysql' => [Schema::MYSQL, false, false, null],
            'mysql pk' => [Schema::MYSQL, true, false, 'PRIMARY KEY'],
            'mysql ai' => [Schema::MYSQL, false, true, 'AUTO_INCREMENT'],
            'mysql pk+ai' => [Schema::MYSQL, true, true, 'AUTO_INCREMENT PRIMARY KEY'],
            'oci' => [Schema::OCI, false, false, null],
            'oci pk' => [Schema::OCI, true, false, 'PRIMARY KEY'],
            'oci ai' => [Schema::OCI, false, true, null],
            'oci pk+ai' => [Schema::OCI, true, true, 'PRIMARY KEY'],
            'pgsql' => [Schema::PGSQL, false, false, null],
            'pgsql pk' => [Schema::PGSQL, true, false, 'PRIMARY KEY'],
            'pgsql ai' => [Schema::PGSQL, false, true, null],
            'pgsql pk+ai' => [Schema::PGSQL, true, true, 'PRIMARY KEY'],
            'sqlite' => [Schema::SQLITE, false, false, null],
            'sqlite pk' => [Schema::SQLITE, true, false, 'PRIMARY KEY'],
            'sqlite ai' => [Schema::SQLITE, false, true, 'AUTOINCREMENT'],
            'sqlite pk+ai' => [Schema::SQLITE, true, true, 'PRIMARY KEY AUTOINCREMENT'],
            'cubrid' => [Schema::CUBRID, false, false, null],
            'cubrid pk' => [Schema::CUBRID, true, false, 'PRIMARY KEY'],
            'cubrid ai' => [Schema::CUBRID, false, true, 'AUTO_INCREMENT'],
            'cubrid pk+ai' => [Schema::CUBRID, true, true, 'AUTO_INCREMENT PRIMARY KEY'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForPrepareSchemaAppend
     * @param string $schema
     * @param bool $primaryKey
     * @param bool $autoIncrement
     * @param string|null $expected
     */
    public function shouldPrepareSchemaAppend(
        string $schema,
        bool $primaryKey,
        bool $autoIncrement,
        ?string $expected
    ): void {
        $this->assertSame($expected, $this->column->prepareSchemaAppend($primaryKey, $autoIncrement, $schema));
    }

    public function providerForEscapingQuotes(): array
    {
        return [
            ['abc', 'abc'],
            ["'abc'", '\\\'abc\\\''],
        ];
    }

    /**
     * @test
     * @dataProvider providerForEscapingQuotes
     * @param string $value
     * @param string $expected
     */
    public function shouldEscapeQuotes(string $value, string $expected): void
    {
        $this->assertSame($expected, $this->column->escapeQuotes($value));
    }

    public function providerForRemovingPrimaryKeyInfo(): array
    {
        return [
            'mssql' => [Schema::MSSQL, 'abc', 'abc'],
            'mssql pk' => [Schema::MSSQL, 'IDENTITY PRIMARY KEY', null],
            'mssql pk+' => [Schema::MSSQL, 'IDENTITY PRIMARY KEY abc', 'abc'],
            'mssql pk trim' => [Schema::MSSQL, ' IDENTITY  PRIMARY key  ', null],
            'mssql id pk' => [Schema::MSSQL, 'IDENtity PRIMARY KEY', null],
            'mssql pk id trim' => [Schema::MSSQL, '  PRIMARY KEY  IDENTITY ', null],
            'oci' => [Schema::OCI, 'abc', 'abc'],
            'oci pk' => [Schema::OCI, 'PRIMARY KEY', null],
            'oci pk+' => [Schema::OCI, 'PRImaRY KEY aaa', 'aaa'],
            'oci pk trim' => [Schema::OCI, ' PRIMARY KEY  ', null],
            'pgsql' => [Schema::PGSQL, 'abc', 'abc'],
            'pgsql pk' => [Schema::PGSQL, 'PRIMARY KEY', null],
            'pgsql pk+' => [Schema::PGSQL, 'PRImaRY KEY aaa', 'aaa'],
            'pgsql pk trim' => [Schema::PGSQL, ' PRIMARY KEY  ', null],
            'sqlite' => [Schema::SQLITE, 'abc', 'abc'],
            'sqlite pk' => [Schema::SQLITE, 'PRIMARY KEY', null],
            'sqlite pk+' => [Schema::SQLITE, 'PRIMARY KEY abc', 'abc'],
            'sqlite pk trim' => [Schema::SQLITE, ' PRIMARY KEY  ', null],
            'sqlite pk ai' => [Schema::SQLITE, 'PRIMARY KEY AUTOINCREMENT', null],
            'sqlite ai pk' => [Schema::SQLITE, 'AUTOINCREMENT  PRIMARY KEY', null],
            'sqlite pk ai trim' => [Schema::SQLITE, '   PRIMARY KEY  AUTOINCREMENT ', null],
            'cubrid' => [Schema::CUBRID, 'abc', 'abc'],
            'cubrid pk' => [Schema::CUBRID, 'PRIMARY KEY', null],
            'cubrid pk+' => [Schema::CUBRID, 'PRIMARY KEY abc', 'abc'],
            'cubrid pk trim' => [Schema::CUBRID, ' PRIMARY KEY  ', null],
            'cubrid pk ai' => [Schema::CUBRID, 'PRIMARY KEY AUTO_INCREMENT', null],
            'cubrid ai pk' => [Schema::CUBRID, 'AUTO_INCREMENT  PRIMARY KEY', null],
            'cubrid pk ai trim' => [Schema::CUBRID, '   PRIMARY KEY  AUTO_INCREMENT ', null],
            'mysql' => [Schema::MYSQL, 'abc', 'abc'],
            'mysql pk' => [Schema::MYSQL, 'PRIMARY KEY', null],
            'mysql pk+' => [Schema::MYSQL, 'PRIMARY KEY abc', 'abc'],
            'mysql pk trim' => [Schema::MYSQL, ' PRIMARY KEY  ', null],
            'mysql pk ai' => [Schema::MYSQL, 'PRIMARY KEY AUTO_INCREMENT', null],
            'mysql ai pk' => [Schema::MYSQL, 'AUTO_INCREMENT  PRIMARY KEY', null],
            'mysql pk ai trim' => [Schema::MYSQL, '   PRIMARY KEY  AUTO_INCREMENT ', null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRemovingPrimaryKeyInfo
     * @param string $schema
     * @param string $append
     * @param string|null $expected
     */
    public function shouldRemoveAppendedPrimaryKeyInfo(string $schema, string $append, ?string $expected): void
    {
        $this->column->setAppend($append);
        $this->assertSame($expected, $this->column->removeAppendedPrimaryKeyInfo($schema));
    }

    /** @test */
    public function shouldProperlySetType(): void
    {
        $this->column->setType('test');
        $this->assertSame('test', $this->column->getType());
    }

    /** @test */
    public function shouldProperlySetDefaultMapping(): void
    {
        $this->column->setDefaultMapping('test');
        $this->assertSame('test', $this->column->getDefaultMapping());
    }

    public function providerForSizePrecisionScale(): array
    {
        return [
            ['1', 1],
            [2, 2],
            [null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSizePrecisionScale
     * @param int|string|null $size
     * @param int|null $expected
     */
    public function shouldProperlySetSize($size, ?int $expected): void
    {
        $this->column->setSize($size);
        $this->assertSame($expected, $this->column->getSize());
    }

    /**
     * @test
     * @dataProvider providerForSizePrecisionScale
     * @param int|string|null $precision
     * @param int|null $expected
     */
    public function shouldProperlySetPrecision($precision, ?int $expected): void
    {
        $this->column->setPrecision($precision);
        $this->assertSame($expected, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSizePrecisionScale
     * @param int|string|null $scale
     * @param int|null $expected
     */
    public function shouldProperlySetScale($scale, ?int $expected): void
    {
        $this->column->setScale($scale);
        $this->assertSame($expected, $this->column->getScale());
    }
}
