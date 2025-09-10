<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\DoubleColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group doublecolumn
 */
final class DoubleColumnTest extends TestCase
{
    /** @var DoubleColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new DoubleColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('double({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, 1],
            'mssql' => [Schema::MSSQL, null],
            'mysql' => [Schema::MYSQL, null],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, null],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     */
    public function shouldReturnProperLength(string $schema, ?int $expected): void
    {
        $this->column->setPrecision(1);
        self::assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, 1],
            'mssql' => [Schema::MSSQL, null],
            'mysql' => [Schema::MYSQL, null],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, null],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     */
    public function shouldSetProperLength(string $schema, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
