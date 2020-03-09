<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\SchemaEnum;
use bizley\migration\table\MoneyColumn;
use PHPUnit\Framework\TestCase;

class MoneyColumnTest extends TestCase
{
    /** @var MoneyColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new MoneyColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        $this->assertSame('money({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLengthWithoutScale(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, '1'],
            'mssql' => [SchemaEnum::MSSQL, '1'],
            'mysql' => [SchemaEnum::MYSQL, '1'],
            'oci' => [SchemaEnum::OCI, '1'],
            'pgsql' => [SchemaEnum::PGSQL, '1'],
            'sqlite' => [SchemaEnum::SQLITE, '1'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLengthWithoutScale
     * @param string $schema
     * @param string|null $expected
     */
    public function shouldReturnProperLengthWithoutScale(string $schema, ?string $expected): void
    {
        $this->column->setPrecision(1);
        $this->assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForGettingLengthWithScale(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, '1, 1'],
            'mssql' => [SchemaEnum::MSSQL, '1, 1'],
            'mysql' => [SchemaEnum::MYSQL, '1, 1'],
            'oci' => [SchemaEnum::OCI, '1, 1'],
            'pgsql' => [SchemaEnum::PGSQL, '1, 1'],
            'sqlite' => [SchemaEnum::SQLITE, '1, 1'],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLengthWithScale
     * @param string $schema
     * @param string|null $expected
     */
    public function shouldReturnProperLengthWithScale(string $schema, ?string $expected): void
    {
        $this->column->setPrecision(1);
        $this->column->setScale(1);
        $this->assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForSettingLengthWithoutScale(): array
    {
        return [
            'cubrid' => [SchemaEnum::CUBRID, 1, null],
            'mssql' => [SchemaEnum::MSSQL, 1, null],
            'mysql' => [SchemaEnum::MYSQL, 1, null],
            'oci' => [SchemaEnum::OCI, 1, null],
            'pgsql' => [SchemaEnum::PGSQL, 1, null],
            'sqlite' => [SchemaEnum::SQLITE, 1, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithoutScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith1IntElementArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength([1], $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithoutScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith1StringElementArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength(['1'], $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    public function providerForSettingLengthWithScale(): array
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
     * @dataProvider providerForSettingLengthWithScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith2IntElementsArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength([1, 1], $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith2StringElementsArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength(['1', '1'], $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithoutScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith1StringElement(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1', $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith2StringElementsVariant1(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1,1', $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith2StringElementsVariant2(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1, 1', $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithScale
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith2StringElementsVariant3(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1 , 1', $schema);
        $this->assertSame($expectedScale, $this->column->getScale());
        $this->assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
