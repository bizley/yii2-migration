<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\BigIntegerColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('table')]
#[Group('bigintegercolumn')]
final class BigIntegerColumnTest extends TestCase
{
    /** @var BigIntegerColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new BigIntegerColumn();
    }

    #[Test]
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('bigInteger({renderLength})', $this->column->getDefinition());
    }

    #[Test]
    public function shouldReturnProperPrimaryKeyDefinition(): void
    {
        self::assertSame('bigPrimaryKey({renderLength})', $this->column->getPrimaryKeyDefinition());
    }

    /**
     * @return array<string, array{string, int|null, string|null}>
     */
    public static function providerForGettingLength(): array
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

    #[DataProvider('providerForGettingLength')]
    #[Test]
    public function shouldReturnProperLength(string $schema, ?int $expected, ?string $engineVersion): void
    {
        $this->column->setSize(1);
        self::assertSame($expected, $this->column->getLength($schema, $engineVersion));
    }

    /**
     * @return array<string, array{string, int|null, int|null, string|null}>
     */
    public static function providerForSettingLength(): array
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

    #[DataProvider('providerForSettingLength')]
    #[Test]
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
