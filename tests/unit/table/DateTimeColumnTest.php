<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\Schema;
use bizley\migration\table\DateTimeColumn;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;

/**
 * @group table
 * @group datetimecolumn
 */
final class DateTimeColumnTest extends TestCase
{
    /** @var DateTimeColumn */
    private $column;

    protected function setUp(): void
    {
        $this->column = new DateTimeColumn();
    }

    /**
     * @test
     */
    public function shouldReturnProperDefinition(): void
    {
        self::assertSame('dateTime({renderLength})', $this->column->getDefinition());
    }

    public function providerForDefaults(): array
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
     * @test
     * @dataProvider providerForDefaults
     * @param $defaultToSet
     * @param $expected
     */
    public function shouldReturnProperDefault($defaultToSet, $expected): void
    {
        $this->column->setDefault($defaultToSet);
        if ($expected instanceof Expression) {
            self::assertSame($expected->expression, $this->column->getDefault()->expression);
        } else {
            self::assertSame($expected, $this->column->getDefault());
        }
    }

    public function providerForGettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null],
            'mssql' => [Schema::MSSQL, null],
            'mysql none' => [Schema::MYSQL, null],
            'mysql old' => [Schema::MYSQL, null, '5.5.0'],
            'mysql new' => [Schema::MYSQL, 1, '5.7.1'],
            'oci' => [Schema::OCI, null],
            'pgsql' => [Schema::PGSQL, 1],
            'sqlite' => [Schema::SQLITE, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForGettingLength
     * @param string $schema
     * @param int|null $expected
     * @param string|null $engineVersion
     */
    public function shouldReturnProperLength(string $schema, ?int $expected, ?string $engineVersion = null): void
    {
        $this->column->setPrecision(1);
        self::assertSame($expected, $this->column->getLength($schema, $engineVersion));
    }

    public function providerForSettingLength(): array
    {
        return [
            'cubrid' => [Schema::CUBRID, null, null],
            'mssql' => [Schema::MSSQL, null, null],
            'mysql none' => [Schema::MYSQL, null, null],
            'mysql old' => [Schema::MYSQL, null, null, '5.6.0'],
            'mysql new' => [Schema::MYSQL, null, 1, '5.6.4'],
            'oci' => [Schema::OCI, null, null],
            'pgsql' => [Schema::PGSQL, null, 1],
            'sqlite' => [Schema::SQLITE, null, null],
        ];
    }

    /**
     * @test
     * @dataProvider providerForSettingLength
     * @param string $schema
     * @param int|null $expectedSize
     * @param int|null $expectedPrecision
     * @param string|null $engineVersion
     */
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
