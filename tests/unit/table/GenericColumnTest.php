<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\SchemaEnum;
use bizley\migration\table\Column;
use bizley\migration\table\ColumnInterface;
use bizley\migration\table\PrimaryKey;
use PHPUnit\Framework\TestCase;

class GenericColumnTest extends TestCase
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
            'mssql proper' => ['IDENTITY PRIMARY KEY', SchemaEnum::MSSQL, true],
            'mssql wrong 1' => ['IDENTITY', SchemaEnum::MSSQL, false],
            'mssql wrong 2' => ['PRIMARY KEY', SchemaEnum::MSSQL, false],
            'mssql wrong 3' => ['something', SchemaEnum::MSSQL, false],
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
            'mssql' => [SchemaEnum::MSSQL, false, false, null],
            'mssql pk' => [SchemaEnum::MSSQL, true, false, 'IDENTITY PRIMARY KEY'],
            'mssql ai' => [SchemaEnum::MSSQL, false, true, null],
            'mssql pk+ai' => [SchemaEnum::MSSQL, true, true, 'IDENTITY PRIMARY KEY'],
            'mysql' => [SchemaEnum::MYSQL, false, false, null],
            'mysql pk' => [SchemaEnum::MYSQL, true, false, 'PRIMARY KEY'],
            'mysql ai' => [SchemaEnum::MYSQL, false, true, 'AUTO_INCREMENT'],
            'mysql pk+ai' => [SchemaEnum::MYSQL, true, true, 'AUTO_INCREMENT PRIMARY KEY'],
            'oci' => [SchemaEnum::OCI, false, false, null],
            'oci pk' => [SchemaEnum::OCI, true, false, 'PRIMARY KEY'],
            'oci ai' => [SchemaEnum::OCI, false, true, null],
            'oci pk+ai' => [SchemaEnum::OCI, true, true, 'PRIMARY KEY'],
            'pgsql' => [SchemaEnum::PGSQL, false, false, null],
            'pgsql pk' => [SchemaEnum::PGSQL, true, false, 'PRIMARY KEY'],
            'pgsql ai' => [SchemaEnum::PGSQL, false, true, null],
            'pgsql pk+ai' => [SchemaEnum::PGSQL, true, true, 'PRIMARY KEY'],
            'sqlite' => [SchemaEnum::SQLITE, false, false, null],
            'sqlite pk' => [SchemaEnum::SQLITE, true, false, 'PRIMARY KEY'],
            'sqlite ai' => [SchemaEnum::SQLITE, false, true, 'AUTOINCREMENT'],
            'sqlite pk+ai' => [SchemaEnum::SQLITE, true, true, 'PRIMARY KEY AUTOINCREMENT'],
            'cubrid' => [SchemaEnum::CUBRID, false, false, null],
            'cubrid pk' => [SchemaEnum::CUBRID, true, false, 'PRIMARY KEY'],
            'cubrid ai' => [SchemaEnum::CUBRID, false, true, 'AUTO_INCREMENT'],
            'cubrid pk+ai' => [SchemaEnum::CUBRID, true, true, 'AUTO_INCREMENT PRIMARY KEY'],
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
        $this->assertSame($expected, $this->column->prepareSchemaAppend($schema, $primaryKey, $autoIncrement));
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
            'mssql' => [SchemaEnum::MSSQL, 'abc', 'abc'],
            'mssql pk' => [SchemaEnum::MSSQL, 'IDENTITY PRIMARY KEY', null],
            'mssql pk+' => [SchemaEnum::MSSQL, 'IDENTITY PRIMARY KEY abc', 'abc'],
            'mssql pk trim' => [SchemaEnum::MSSQL, ' IDENTITY  PRIMARY key  ', null],
            'mssql id pk' => [SchemaEnum::MSSQL, 'IDENtity PRIMARY KEY', null],
            'mssql pk id trim' => [SchemaEnum::MSSQL, '  PRIMARY KEY  IDENTITY ', null],
            'oci' => [SchemaEnum::OCI, 'abc', 'abc'],
            'oci pk' => [SchemaEnum::OCI, 'PRIMARY KEY', null],
            'oci pk+' => [SchemaEnum::OCI, 'PRImaRY KEY aaa', 'aaa'],
            'oci pk trim' => [SchemaEnum::OCI, ' PRIMARY KEY  ', null],
            'pgsql' => [SchemaEnum::PGSQL, 'abc', 'abc'],
            'pgsql pk' => [SchemaEnum::PGSQL, 'PRIMARY KEY', null],
            'pgsql pk+' => [SchemaEnum::PGSQL, 'PRImaRY KEY aaa', 'aaa'],
            'pgsql pk trim' => [SchemaEnum::PGSQL, ' PRIMARY KEY  ', null],
            'sqlite' => [SchemaEnum::SQLITE, 'abc', 'abc'],
            'sqlite pk' => [SchemaEnum::SQLITE, 'PRIMARY KEY', null],
            'sqlite pk+' => [SchemaEnum::SQLITE, 'PRIMARY KEY abc', 'abc'],
            'sqlite pk trim' => [SchemaEnum::SQLITE, ' PRIMARY KEY  ', null],
            'sqlite pk ai' => [SchemaEnum::SQLITE, 'PRIMARY KEY AUTOINCREMENT', null],
            'sqlite ai pk' => [SchemaEnum::SQLITE, 'AUTOINCREMENT  PRIMARY KEY', null],
            'sqlite pk ai trim' => [SchemaEnum::SQLITE, '   PRIMARY KEY  AUTOINCREMENT ', null],
            'cubrid' => [SchemaEnum::CUBRID, 'abc', 'abc'],
            'cubrid pk' => [SchemaEnum::CUBRID, 'PRIMARY KEY', null],
            'cubrid pk+' => [SchemaEnum::CUBRID, 'PRIMARY KEY abc', 'abc'],
            'cubrid pk trim' => [SchemaEnum::CUBRID, ' PRIMARY KEY  ', null],
            'cubrid pk ai' => [SchemaEnum::CUBRID, 'PRIMARY KEY AUTO_INCREMENT', null],
            'cubrid ai pk' => [SchemaEnum::CUBRID, 'AUTO_INCREMENT  PRIMARY KEY', null],
            'cubrid pk ai trim' => [SchemaEnum::CUBRID, '   PRIMARY KEY  AUTO_INCREMENT ', null],
            'mysql' => [SchemaEnum::MYSQL, 'abc', 'abc'],
            'mysql pk' => [SchemaEnum::MYSQL, 'PRIMARY KEY', null],
            'mysql pk+' => [SchemaEnum::MYSQL, 'PRIMARY KEY abc', 'abc'],
            'mysql pk trim' => [SchemaEnum::MYSQL, ' PRIMARY KEY  ', null],
            'mysql pk ai' => [SchemaEnum::MYSQL, 'PRIMARY KEY AUTO_INCREMENT', null],
            'mysql ai pk' => [SchemaEnum::MYSQL, 'AUTO_INCREMENT  PRIMARY KEY', null],
            'mysql pk ai trim' => [SchemaEnum::MYSQL, '   PRIMARY KEY  AUTO_INCREMENT ', null],
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
}
