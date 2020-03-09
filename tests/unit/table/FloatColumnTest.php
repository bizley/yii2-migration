<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\SchemaEnum;
use bizley\migration\table\FloatColumn;
use PHPUnit\Framework\TestCase;

class FloatColumnTest extends TestCase
{
    /** @var FloatColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new FloatColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('float({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, 1],
            'mssql' => [SchemaEnum::MSSQL, null],
            'mysql' => [SchemaEnum::MYSQL, null],
            'oci' => [SchemaEnum::OCI, null],
            'pgsql' => [SchemaEnum::PGSQL, null],
            'sqlite' => [SchemaEnum::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     * @param string $schema
     * @param int|null $expected
     */
    public function shouldReturnProperLength(string $schema, ?int $expected): void
    {
        $this->column->setPrecision(1);
        $this->assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, 1],
            'mssql' => [SchemaEnum::MSSQL, null],
            'mysql' => [SchemaEnum::MYSQL, null],
            'oci' => [SchemaEnum::OCI, null],
            'pgsql' => [SchemaEnum::PGSQL, null],
            'sqlite' => [SchemaEnum::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     * @param string $schema
     * @param int|null $expectedPrecision
     */
    public function shouldSetProperLength(string $schema, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
