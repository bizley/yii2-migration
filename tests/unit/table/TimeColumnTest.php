<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\TimeColumn;
use PHPUnit\Framework\TestCase;

class TimeColumnTest extends TestCase
{
    /** @var TimeColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new TimeColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('time({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'mysql none' => [Schema::MYSQL, null],
            'mysql old' => [Schema::MYSQL, null, '5.5.0'],
            'mysql new' => [Schema::MYSQL, 1, '5.7.1'],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, 1],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     * @param string $schema
     * @param int|null $expected
     * @param string|null $engineVersion
     */
    public function shouldReturnProperLength(string $schema, ?int $expected, string $engineVersion = null): void
    {
        $this->column->setPrecision(1);
        $this->assertSame($expected, $this->column->getLength($schema, $engineVersion));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'mysql none' => [Schema::MYSQL, null, null],
            'mysql old' => [Schema::MYSQL, null, null, '5.6.0'],
            'mysql new' => [Schema::MYSQL, null, 1, '5.6.4'],
            'oci' => [Schema::OCI, null, null],
            'pgsql' => [Schema::PGSQL, null, 1],
            'sqlite' => [Schema::SQLITE, null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     * @param string $schema
     * @param int|null $expectedSize
     * @param int|null $expectedPrecision
     * @param string|null $engineVersion
     */
    public function shouldSetProperLength(
        string $schema,
        ?int $expectedSize,
        ?int $expectedPrecision,
        string $engineVersion = null
    ): void {
        $this->column->setLength(1, $schema, $engineVersion);
        $this->assertSame($expectedSize, $this->column->getSize());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
