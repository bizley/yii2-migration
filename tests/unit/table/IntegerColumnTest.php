<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\IntegerColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group integercolumn
 */
final class IntegerColumnTest extends TestCase
{
    /** @var IntegerColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new IntegerColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('integer({renderLength})', $this->column->getDefinition());
    }

    /**
     * @test
     */
    public function shouldReturnProperPrimaryKeyDefinition(): void
    {
        self::assertSame('primaryKey({renderLength})', $this->column->getPrimaryKeyDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'mysql?' => [Schema::MYSQL, null, null],
            'mysql5' => [Schema::MYSQL, 1, '5.7.20'],
            'mysql8<' => [Schema::MYSQL, 1, '8.0.0'],
            'mysql8>' => [Schema::MYSQL, null, '8.0.20'],
            'oci' => [Schema::OCI, 1, null],
            'pgsql' => [Schema::PGSQL, null, null],
            'sqlite' => [Schema::SQLITE, null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     */
    public function shouldReturnProperLength(string $schema, ?int $expected, ?string $engineVersion): void
    {
        $this->column->setSize(1);
        self::assertSame($expected, $this->column->getLength($schema, $engineVersion));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null, null],
            'mssql' => [Schema::MSSQL, null, null, null],
            'mysql?' => [Schema::MYSQL, null, null, null],
            'mysql5' => [Schema::MYSQL, 1, 1, '5.7.20'],
            'mysql8<' => [Schema::MYSQL, 1, 1, '8.0.0'],
            'mysql8>' => [Schema::MYSQL, null, null, '8.0.20'],
            'oci' => [Schema::OCI, 1, 1, null],
            'pgsql' => [Schema::PGSQL, null, null, null],
            'sqlite' => [Schema::SQLITE, null, null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     */
    public function shouldSetProperLength(
        string $schema,
        ?int $expectedSize,
        ?int $expectedPrecision,
        ?string $engineVersion
    ): void {
        $this->column->setLength(1, $schema, $engineVersion);
        self::assertSame($expectedSize, $this->column->getSize());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
