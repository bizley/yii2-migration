<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\SchemaEnum;
use bizley\migration\table\BinaryColumn;
use PHPUnit\Framework\TestCase;

class BinaryColumnTest extends TestCase
{
    /** @var BinaryColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new BinaryColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('binary({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, null],
            'mssql' => [SchemaEnum::MSSQL, 1],
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
        $this->column->setSize(1);
        $this->assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, null, null],
            'mssql' => [SchemaEnum::MSSQL, 1, 1],
            'mysql' => [SchemaEnum::MYSQL, null, null],
            'oci' => [SchemaEnum::OCI, null, null],
            'pgsql' => [SchemaEnum::PGSQL, null, null],
            'sqlite' => [SchemaEnum::SQLITE, null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     * @param string $schema
     * @param int|null $expectedSize
     * @param int|null $expectedPrecision
     */
    public function shouldSetProperLength(string $schema, ?int $expectedSize, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        $this->assertSame($expectedSize, $this->column->getSize());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
