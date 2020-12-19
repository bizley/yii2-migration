<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\TinyIntegerColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group tinyintegercolumn
 */
final class TinyIntegerColumnTest extends TestCase
{
    /** @var TinyIntegerColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new TinyIntegerColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('tinyInteger({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
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
        self::assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForGettingLengthForMySQL(): array
    {
        return [
            [1, 1, null],
            [1, 1, '5.7.20'],
            [1, 1, '8.0.0'],
            [1, 1, '8.0.20'],
            [2, null, null],
            [2, 2, '5.7.20'],
            [2, 2, '8.0.0'],
            [2, null, '8.0.20'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLengthForMySQL
     * @param int $size
     * @param int|null $expected
     * @param string|null $engineVersion
     */
    public function shouldReturnProperLengthForMySQL(int $size, ?int $expected, ?string $engineVersion): void
    {
        $this->column->setSize($size);
        self::assertSame($expected, $this->column->getLength(Schema::MYSQL, $engineVersion));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
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
        self::assertSame($expectedSize, $this->column->getSize());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    public function providerForSettingLengthForMySQL(): array
    {
        return [
            [1, 1, 1, null],
            [1, 1, 1, '5.7.20'],
            [1, 1, 1, '8.0.0'],
            [1, 1, 1, '8.0.20'],
            [2, null, null, null],
            [2, 2, 2, '5.7.20'],
            [2, 2, 2, '8.0.0'],
            [2, null, null, '8.0.20'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthForMySQL
     * @param int $length
     * @param int|null $expectedSize
     * @param int|null $expectedPrecision
     * @param string|null $engineVersion
     */
    public function shouldSetProperLengthForMySQL(
        int $length,
        ?int $expectedSize,
        ?int $expectedPrecision,
        ?string $engineVersion
    ): void {
        $this->column->setLength($length, Schema::MYSQL, $engineVersion);
        self::assertSame($expectedSize, $this->column->getSize());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
