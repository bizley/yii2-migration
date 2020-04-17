<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\BigIntegerColumn;
use PHPUnit\Framework\TestCase;

final class BigIntegerColumnTest extends TestCase
{
    /** @var BigIntegerColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new BigIntegerColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('bigInteger({renderLength})', $this->column->getDefinition());
    }

    /**
     * @test
     */
    public function shouldReturnProperPrimaryKeyDefinition(): void
    {
        $this->assertSame('bigPrimaryKey({renderLength})', $this->column->getPrimaryKeyDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'mysql' => [Schema::MYSQL, 1],
            'oci' => [Schema::OCI, 1],
            'pgsql' => [Schema::PGSQL, null],
            'sqlite' => [Schema::SQLITE, null],
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
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'mysql' => [Schema::MYSQL, 1, 1],
            'oci' => [Schema::OCI, 1, 1],
            'pgsql' => [Schema::PGSQL, null, null],
            'sqlite' => [Schema::SQLITE, null, null],
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
