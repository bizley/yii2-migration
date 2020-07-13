<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\DecimalColumn;
use PHPUnit\Framework\TestCase;

/**
 * @group table
 * @group decimalcolumn
 */
final class DecimalColumnTest extends TestCase
{
    /** @var DecimalColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new DecimalColumn();
    }

    /** @test */
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('decimal({renderLength})', $this->column->getDefinition());
    }

    public function providerForGettingLengthWithoutScale(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, '1'],
            'mssql' => [Schema::MSSQL, '1'],
            'mysql' => [Schema::MYSQL, '1'],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, '1'],
            'sqlite' => [Schema::SQLITE, '1'],
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
        self::assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForGettingLengthWithScale(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, '1, 1'],
            'mssql' => [Schema::MSSQL, '1, 1'],
            'mysql' => [Schema::MYSQL, '1, 1'],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, '1, 1'],
            'sqlite' => [Schema::SQLITE, '1, 1'],
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
        self::assertSame($expected, $this->column->getLength($schema));
    }

    public function providerForSettingLengthWithoutScale(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, 1, null],
            'mssql' => [Schema::MSSQL, 1, null],
            'mysql' => [Schema::MYSQL, 1, null],
            'oci' => [Schema::OCI, null, null],
            'pgsql' => [Schema::PGSQL, 1, null],
            'sqlite' => [Schema::SQLITE, 1, null],
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    public function providerForSettingLengthWithoutScaleAndPrecision(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'mysql' => [Schema::MYSQL, null, null],
            'oci' => [Schema::OCI, null, null],
            'pgsql' => [Schema::PGSQL, null, null],
            'sqlite' => [Schema::SQLITE, null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLengthWithoutScaleAndPrecision
     * @param string $schema
     * @param int|null $expectedPrecision
     * @param int|null $expectedScale
     */
    public function shouldSetProperLengthWith0ElementArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength([], $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    public function providerForSettingLengthWithScale(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, 1, 1],
            'mssql' => [Schema::MSSQL, 1, 1],
            'mysql' => [Schema::MYSQL, 1, 1],
            'oci' => [Schema::OCI, null, null],
            'pgsql' => [Schema::PGSQL, 1, 1],
            'sqlite' => [Schema::SQLITE, 1, 1],
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
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
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
