<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\FloatColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group floatcolumn
 */
final class FloatColumnTest extends TestCase
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
        self::assertSame('float({renderLength})', $this->column->getDefinition());
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
     * @param string $schema
     * @param int|null $expected
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
     * @param string $schema
     * @param int|null $expectedPrecision
     */
    public function shouldSetProperLength(string $schema, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
