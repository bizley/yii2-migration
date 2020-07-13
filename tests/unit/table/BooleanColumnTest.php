<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\BooleanColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group booleancolumn
 */
final class BooleanColumnTest extends TestCase
{
    /** @var BooleanColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new BooleanColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('boolean()', $this->column->getDefinition());
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
        self::assertSame($expected, $this->column->getLength($schema));
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
        self::assertSame($expectedSize, $this->column->getSize());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
