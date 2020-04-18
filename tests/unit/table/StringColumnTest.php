<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\StringColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group stringcolumn
 */
final class StringColumnTest extends TestCase
{
    /** @var StringColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new StringColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('string({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, 1],
            'mssql' => [Schema::MSSQL, 1],
            'mysql' => [Schema::MYSQL, 1],
            'oci' => [Schema::OCI, 1],
            'pgsql' => [Schema::PGSQL, 1],
            'sqlite' => [Schema::SQLITE, 1],
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
            'cubrid' => [Schema::CUBRID, 1, 1],
            'mssql' => [Schema::MSSQL, 1, 1],
            'mysql' => [Schema::MYSQL, 1, 1],
            'oci' => [Schema::OCI, 1, 1],
            'pgsql' => [Schema::PGSQL, 1, 1],
            'sqlite' => [Schema::SQLITE, 1, 1],
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
