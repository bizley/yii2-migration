<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\SchemaEnum;
use bizley\migration\table\CharacterColumn;
use PHPUnit\Framework\TestCase;

class CharacterColumnTest extends TestCase
{
    /** @var CharacterColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new CharacterColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('char({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, 1],
            'mssql' => [SchemaEnum::MSSQL, 1],
            'mysql' => [SchemaEnum::MYSQL, 1],
            'oci' => [SchemaEnum::OCI, 1],
            'pgsql' => [SchemaEnum::PGSQL, 1],
            'sqlite' => [SchemaEnum::SQLITE, 1],
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
            'cubrid' => [SchemaEnum::CUBRID, 1, 1],
            'mssql' => [SchemaEnum::MSSQL, 1, 1],
            'mysql' => [SchemaEnum::MYSQL, 1, 1],
            'oci' => [SchemaEnum::OCI, 1, 1],
            'pgsql' => [SchemaEnum::PGSQL, 1, 1],
            'sqlite' => [SchemaEnum::SQLITE, 1, 1],
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
