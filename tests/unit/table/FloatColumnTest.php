<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\FloatColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('table')]
#[Group('floatcolumn')]
final class FloatColumnTest extends TestCase
{
    /** @var FloatColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new FloatColumn();
    }

    #[Test]
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('float({renderLength})', $this->column->getDefinition());
    }

    /**
     * @return array<string, array{string, int|null}>
     */
    public static function providerForGettingLength(): array
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

    #[DataProvider('providerForGettingLength')]
    #[Test]
    public function shouldReturnProperLength(string $schema, ?int $expected): void
    {
        $this->column->setPrecision(1);
        self::assertSame($expected, $this->column->getLength($schema));
    }

    /**
     * @return array<string, array{string, int|null}>
     */
    public static function providerForSettingLength(): array
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
     * @param string $schema
     * @param int|null $expectedPrecision
     */
    #[DataProvider('providerForSettingLength')]
    #[Test]
    public function shouldSetProperLength(string $schema, ?int $expectedPrecision): void
    {
        $this->column->setLength(1, $schema);
        self::assertSame($expectedPrecision, $this->column->getPrecision());
    }
}
