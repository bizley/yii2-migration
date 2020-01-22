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
}
