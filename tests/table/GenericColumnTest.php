<?php

declare(strict_types=1);

namespace bizley\tests\table;

use bizley\migration\table\Column;
use bizley\migration\table\PrimaryKey;
use bizley\migration\table\Structure;
use PHPUnit\Framework\TestCase;

class GenericColumnTest extends TestCase
{
    private function getColumn(array $config = []): Column
    {
        return new class ($config) extends Column {
            public function setLength($value): void
            {
            }

            public function getLength()
            {
                return null;
            }

            protected function buildSpecificDefinition(Structure $table): void
            {
            }
        };
    }

    public function providerForColumnInPrimaryKey(): array
    {
        return [
            'yes' => ['test', ['test'], true],
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
        $column = $this->getColumn(['name' => $name]);
        $primaryKey = new PrimaryKey(['columns' => $columns]);
        $this->assertSame($expected, $column->isColumnInPrimaryKey($primaryKey));
    }

    public function providerForPrimaryKeyInfoAppended(): array
    {
        return [
            'empty append' => ['', '', false],
            'mssql proper' => ['IDENTITY PRIMARY KEY', Structure::SCHEMA_MSSQL, true],
            'mssql wrong 1' => ['IDENTITY', Structure::SCHEMA_MSSQL, false],
            'mssql wrong 2' => ['PRIMARY KEY', Structure::SCHEMA_MSSQL, false],
            'mssql wrong 3' => ['something', Structure::SCHEMA_MSSQL, false],
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
        $column = $this->getColumn([
            'append' => $append,
            'schema' => $schema
        ]);
        $this->assertSame($expected, $column->isPrimaryKeyInfoAppended());
    }

    public function providerForPrepareSchemaAppend(): array
    {
        return [
            'mssql' => [Structure::SCHEMA_MSSQL, false, false, null],
            'mssql pk' => [Structure::SCHEMA_MSSQL, true, false, 'IDENTITY PRIMARY KEY'],
            'mssql ai' => [Structure::SCHEMA_MSSQL, false, true, null],
            'mssql pk+ai' => [Structure::SCHEMA_MSSQL, true, true, 'IDENTITY PRIMARY KEY'],
            'mysql' => [Structure::SCHEMA_MYSQL, false, false, null],
            'mysql pk' => [Structure::SCHEMA_MYSQL, true, false, 'PRIMARY KEY'],
            'mysql ai' => [Structure::SCHEMA_MYSQL, false, true, 'AUTO_INCREMENT'],
            'mysql pk+ai' => [Structure::SCHEMA_MYSQL, true, true, 'AUTO_INCREMENT PRIMARY KEY'],
            'oci' => [Structure::SCHEMA_OCI, false, false, null],
            'oci pk' => [Structure::SCHEMA_OCI, true, false, 'PRIMARY KEY'],
            'oci ai' => [Structure::SCHEMA_OCI, false, true, null],
            'oci pk+ai' => [Structure::SCHEMA_OCI, true, true, 'PRIMARY KEY'],
            'pgsql' => [Structure::SCHEMA_PGSQL, false, false, null],
            'pgsql pk' => [Structure::SCHEMA_PGSQL, true, false, 'PRIMARY KEY'],
            'pgsql ai' => [Structure::SCHEMA_PGSQL, false, true, null],
            'pgsql pk+ai' => [Structure::SCHEMA_PGSQL, true, true, 'PRIMARY KEY'],
            'sqlite' => [Structure::SCHEMA_SQLITE, false, false, null],
            'sqlite pk' => [Structure::SCHEMA_SQLITE, true, false, 'PRIMARY KEY'],
            'sqlite ai' => [Structure::SCHEMA_SQLITE, false, true, 'AUTOINCREMENT'],
            'sqlite pk+ai' => [Structure::SCHEMA_SQLITE, true, true, 'PRIMARY KEY AUTOINCREMENT'],
            'cubrid' => [Structure::SCHEMA_CUBRID, false, false, null],
            'cubrid pk' => [Structure::SCHEMA_CUBRID, true, false, 'PRIMARY KEY'],
            'cubrid ai' => [Structure::SCHEMA_CUBRID, false, true, 'AUTO_INCREMENT'],
            'cubrid pk+ai' => [Structure::SCHEMA_CUBRID, true, true, 'AUTO_INCREMENT PRIMARY KEY'],
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
        $column = $this->getColumn(['schema' => $schema]);
        $this->assertSame($expected, $column->prepareSchemaAppend($primaryKey, $autoIncrement));
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
        $column = $this->getColumn();
        $this->assertSame($expected, $column->escapeQuotes($value));
    }

    public function providerForRemovingPrimaryKeyInfo(): array
    {
        return [
            'mssql' => [Structure::SCHEMA_MSSQL, 'abc', 'abc'],
            'mssql pk' => [Structure::SCHEMA_MSSQL, 'IDENTITY PRIMARY KEY', null],
            'mssql pk+' => [Structure::SCHEMA_MSSQL, 'IDENTITY PRIMARY KEY abc', 'abc'],
            'mssql pk trim' => [Structure::SCHEMA_MSSQL, ' IDENTITY  PRIMARY key  ', null],
            'mssql id pk' => [Structure::SCHEMA_MSSQL, 'IDENtity PRIMARY KEY', null],
            'mssql pk id trim' => [Structure::SCHEMA_MSSQL, '  PRIMARY KEY  IDENTITY ', null],
            'oci' => [Structure::SCHEMA_OCI, 'abc', 'abc'],
            'oci pk' => [Structure::SCHEMA_OCI, 'PRIMARY KEY', null],
            'oci pk+' => [Structure::SCHEMA_OCI, 'PRImaRY KEY aaa', 'aaa'],
            'oci pk trim' => [Structure::SCHEMA_OCI, ' PRIMARY KEY  ', null],
            'pgsql' => [Structure::SCHEMA_PGSQL, 'abc', 'abc'],
            'pgsql pk' => [Structure::SCHEMA_PGSQL, 'PRIMARY KEY', null],
            'pgsql pk+' => [Structure::SCHEMA_PGSQL, 'PRImaRY KEY aaa', 'aaa'],
            'pgsql pk trim' => [Structure::SCHEMA_PGSQL, ' PRIMARY KEY  ', null],
            'sqlite' => [Structure::SCHEMA_SQLITE, 'abc', 'abc'],
            'sqlite pk' => [Structure::SCHEMA_SQLITE, 'PRIMARY KEY', null],
            'sqlite pk+' => [Structure::SCHEMA_SQLITE, 'PRIMARY KEY abc', 'abc'],
            'sqlite pk trim' => [Structure::SCHEMA_SQLITE, ' PRIMARY KEY  ', null],
            'sqlite pk ai' => [Structure::SCHEMA_SQLITE, 'PRIMARY KEY AUTOINCREMENT', null],
            'sqlite ai pk' => [Structure::SCHEMA_SQLITE, 'AUTOINCREMENT  PRIMARY KEY', null],
            'sqlite pk ai trim' => [Structure::SCHEMA_SQLITE, '   PRIMARY KEY  AUTOINCREMENT ', null],
            'cubrid' => [Structure::SCHEMA_CUBRID, 'abc', 'abc'],
            'cubrid pk' => [Structure::SCHEMA_CUBRID, 'PRIMARY KEY', null],
            'cubrid pk+' => [Structure::SCHEMA_CUBRID, 'PRIMARY KEY abc', 'abc'],
            'cubrid pk trim' => [Structure::SCHEMA_CUBRID, ' PRIMARY KEY  ', null],
            'cubrid pk ai' => [Structure::SCHEMA_CUBRID, 'PRIMARY KEY AUTO_INCREMENT', null],
            'cubrid ai pk' => [Structure::SCHEMA_CUBRID, 'AUTO_INCREMENT  PRIMARY KEY', null],
            'cubrid pk ai trim' => [Structure::SCHEMA_CUBRID, '   PRIMARY KEY  AUTO_INCREMENT ', null],
            'mysql' => [Structure::SCHEMA_MYSQL, 'abc', 'abc'],
            'mysql pk' => [Structure::SCHEMA_MYSQL, 'PRIMARY KEY', null],
            'mysql pk+' => [Structure::SCHEMA_MYSQL, 'PRIMARY KEY abc', 'abc'],
            'mysql pk trim' => [Structure::SCHEMA_MYSQL, ' PRIMARY KEY  ', null],
            'mysql pk ai' => [Structure::SCHEMA_MYSQL, 'PRIMARY KEY AUTO_INCREMENT', null],
            'mysql ai pk' => [Structure::SCHEMA_MYSQL, 'AUTO_INCREMENT  PRIMARY KEY', null],
            'mysql pk ai trim' => [Structure::SCHEMA_MYSQL, '   PRIMARY KEY  AUTO_INCREMENT ', null],
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
        $column = $this->getColumn([
            'append' => $append,
            'schema' => $schema
        ]);
        $this->assertSame($expected, $column->removeAppendedPrimaryKeyInfo());
    }

    public function providerForRender(): array
    {
        return [
            [12, '            \'name\' => $this,'],
            [1, ' \'name\' => $this,'],
            [0, '\'name\' => $this,'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForRender
     * @param int $indent
     * @param string $expected
     */
    public function shouldProperlyRender(int $indent, string $expected): void
    {
        $column = $this->getColumn(['name' => 'name']);
        $this->assertSame($expected, $column->render(new Structure(), $indent));
    }
}
