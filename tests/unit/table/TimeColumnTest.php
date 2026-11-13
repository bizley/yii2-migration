<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\TimeColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('table')]
#[Group('timecolumn')]
final class TimeColumnTest extends TestCase
{
    /** @var TimeColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new TimeColumn();
    }

    #[Test]
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('time({renderLength})', $this->column->getDefinition());
    }

    /**
     * @return array<string, array{0: string, 1: int|null, 2?: string|null}>
     */
    public static function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'mysql none' => [Schema::MYSQL, null],
            'mysql old' => [Schema::MYSQL, null, '5.5.0'],
            'mysql new' => [Schema::MYSQL, 1, '5.7.1'],
            'mariadb 10' => [Schema::MYSQL, 1, '5.5.5-10.6.12-MariaDB'],
            'mariadb 11' => [Schema::MYSQL, 1, '11.4.2-MariaDB'],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, 1],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    #[DataProvider('providerForGettingLength')]
    #[Test]
    public function shouldReturnProperLength(string $schema, ?int $expected, ?string $engineVersion = null): void
    {
        $this->column->setPrecision(1);
        self::assertSame($expected, $this->column->getLength($schema, $engineVersion));
    }

    /**
     * @return array<string, array{0: string, 1: int|null, 2: int|null, 3?: string|null}>
     */
    public static function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'mysql none' => [Schema::MYSQL, null, null],
            'mysql old' => [Schema::MYSQL, null, null, '5.6.0'],
            'mysql new' => [Schema::MYSQL, null, 1, '5.6.4'],
            'mariadb 10' => [Schema::MYSQL, null, 1, '5.5.5-10.6.12-MariaDB'],
            'oci' => [Schema::OCI, null, null],
            'pgsql' => [Schema::PGSQL, null, 1],
            'sqlite' => [Schema::SQLITE, null, null],
        ];
    }

    #[DataProvider('providerForSettingLength')]
    #[Test]
    public function shouldSetProperLength(
        string $schema,
        ?int $expectedSize,
        ?int $expectedPrecision,
        ?string $engineVersion = null
    ): void {
        $this->column->setLength(1, $schema, $engineVersion);
        self::assertSame($expectedSize, $this->column->getSize());
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
