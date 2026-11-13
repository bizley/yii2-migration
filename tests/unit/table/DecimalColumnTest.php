<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\DecimalColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('table')]
#[Group('decimalcolumn')]
final class DecimalColumnTest extends TestCase
{
    /** @var DecimalColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new DecimalColumn();
    }

    #[Test]
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('decimal({renderLength})', $this->column->getDefinition());
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function providerForGettingLengthWithoutScale(): array
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

    #[DataProvider('providerForGettingLengthWithoutScale')]
    #[Test]
    public function shouldReturnProperLengthWithoutScale(string $schema, ?string $expected): void
    {
        $this->column->setPrecision(1);
        self::assertSame($expected, $this->column->getLength($schema));
    }

    /**
     * @return array<string, array{string, string|null}>
     */
    public static function providerForGettingLengthWithScale(): array
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

    #[DataProvider('providerForGettingLengthWithScale')]
    #[Test]
    public function shouldReturnProperLengthWithScale(string $schema, ?string $expected): void
    {
        $this->column->setPrecision(1);
        $this->column->setScale(1);
        self::assertSame($expected, $this->column->getLength($schema));
    }

    /**
     * @return array<string, array{string, int|null, int|null}>
     */
    public static function providerForSettingLengthWithoutScale(): array
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

    #[DataProvider('providerForSettingLengthWithoutScale')]
    #[Test]
    public function shouldSetProperLengthWith1IntElementArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength([1], $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @return array<string, array{string, int|null, int|null}>
     */
    public static function providerForSettingLengthWithoutScaleAndPrecision(): array
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

    #[DataProvider('providerForSettingLengthWithoutScaleAndPrecision')]
    #[Test]
    public function shouldSetProperLengthWith0ElementArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength([], $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[DataProvider('providerForSettingLengthWithoutScale')]
    #[Test]
    public function shouldSetProperLengthWith1StringElementArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength(['1'], $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @return array<string, array{string, int|null, int|null}>
     */
    public static function providerForSettingLengthWithScale(): array
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

    #[DataProvider('providerForSettingLengthWithScale')]
    #[Test]
    public function shouldSetProperLengthWith2IntElementsArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength([1, 1], $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[DataProvider('providerForSettingLengthWithScale')]
    #[Test]
    public function shouldSetProperLengthWith2StringElementsArray(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength(['1', '1'], $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[DataProvider('providerForSettingLengthWithoutScale')]
    #[Test]
    public function shouldSetProperLengthWith1StringElement(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1', $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[DataProvider('providerForSettingLengthWithScale')]
    #[Test]
    public function shouldSetProperLengthWith2StringElementsVariant1(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1,1', $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[DataProvider('providerForSettingLengthWithScale')]
    #[Test]
    public function shouldSetProperLengthWith2StringElementsVariant2(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1, 1', $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[DataProvider('providerForSettingLengthWithScale')]
    #[Test]
    public function shouldSetProperLengthWith2StringElementsVariant3(
        string $schema,
        ?int $expectedPrecision,
        ?int $expectedScale
    ): void {
        $this->column->setLength('1 , 1', $schema);
        self::assertSame($expectedScale, $this->column->getScale());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    #[Test]
    public function shouldSetPrecisionAndScaleFromDifferentValues(): void
    {
        $this->column->setLength('10, 2', Schema::PGSQL);
        self::assertSame(10, $this->column->getPrecision());
        self::assertSame(2, $this->column->getScale());
    }

    #[Test]
    public function shouldResetPrecisionAndScaleWhenLengthIsEmpty(): void
    {
        $this->column->setLength('10, 2', Schema::PGSQL);
        $this->column->setLength('', Schema::PGSQL);
        self::assertNull($this->column->getPrecision());
        self::assertNull($this->column->getScale());
    }
}
