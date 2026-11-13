<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\TinyIntegerColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('table')]
#[Group('tinyintegercolumn')]
final class TinyIntegerColumnTest extends TestCase
{
    /** @var TinyIntegerColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new TinyIntegerColumn();
    }

    #[Test]
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('tinyInteger({renderLength})', $this->column->getDefinition());
    }

    /**
     * @return array<string, array{string, int|null}>
     */
    public static function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'oci' => [Schema::OCI, 1],
            'pgsql' => [Schema::PGSQL, null],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    #[DataProvider('providerForGettingLength')]
    #[Test]
    public function shouldReturnProperLength(string $schema, ?int $expected): void
    {
        $this->column->setSize(1);
        self::assertSame($expected, $this->column->getLength($schema));
    }

    /**
     * @return list<array{int, int|null, string|null}>
     */
    public static function providerForGettingLengthForMySQL(): array
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

    #[DataProvider('providerForGettingLengthForMySQL')]
    #[Test]
    public function shouldReturnProperLengthForMySQL(int $size, ?int $expected, ?string $engineVersion): void
    {
        $this->column->setSize($size);
        self::assertSame($expected, $this->column->getLength(Schema::MYSQL, $engineVersion));
    }

    /**
     * @return array<string, array{string, int|null, int|null}>
     */
    public static function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'oci' => [Schema::OCI, 1, 1],
            'pgsql' => [Schema::PGSQL, null, null],
            'sqlite' => [Schema::SQLITE, null, null],
        ];
    }

    #[DataProvider('providerForSettingLength')]
    #[Test]
    public function shouldSetProperLength(string $schema, ?int $expectedSize, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        self::assertSame($expectedSize, $this->column->getSize());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }

    /**
     * @return list<array{int, int|null, int|null, string|null}>
     */
    public static function providerForSettingLengthForMySQL(): array
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

    #[DataProvider('providerForSettingLengthForMySQL')]
    #[Test]
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
