<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\TimestampColumn;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;

#[Group('table')]
#[Group('timestampcolumn')]
final class TimestampColumnTest extends TestCase
{
    /** @var TimestampColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new TimestampColumn();
    }

    #[Test]
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('timestamp({renderLength})', $this->column->getDefinition());
    }

    /**
     * @return array<string, array{string|int|Expression, string|int|Expression}>
     */
    public static function providerForDefaults(): array
    {
        return [
            'string' => ['abc', 'abc'],
            'int' => [9, 9],
            'expression' => [new Expression('NOW()'), new Expression('NOW()')],
            'current_timestamp' => ['current_timestamp', 'current_timestamp'],
            'current_timestamp(10)' => ['current_timestamp(10)', new Expression('current_timestamp(10)')],
            'CURRENT_TIMESTAMP(2)' => ['CURRENT_TIMESTAMP(2)', new Expression('CURRENT_TIMESTAMP(2)')],
            'CURRENT_TIMESTAMP()' => ['CURRENT_TIMESTAMP()', new Expression('CURRENT_TIMESTAMP()')],
        ];
    }

    /**
     * @param string|int|Expression $defaultToSet
     * @param string|int|Expression $expected
     */
    #[DataProvider('providerForDefaults')]
    #[Test]
    public function shouldReturnProperDefault($defaultToSet, $expected): void
    {
        $this->column->setDefault($defaultToSet);
        if ($expected instanceof Expression) {
            self::assertSame($expected->expression, $this->column->getDefault()->expression);
        } else {
            self::assertSame($expected, $this->column->getDefault());
        }
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
