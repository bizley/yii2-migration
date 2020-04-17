<?php

declare(strict_types=1);

namespace bizley\tests\unit\table;

use bizley\migration\table\BigIntegerColumn;
use bizley\migration\table\BigPrimaryKeyColumn;
use bizley\migration\table\BigUnsignedPrimaryKeyColumn;
use bizley\migration\table\BinaryColumn;
use bizley\migration\table\BooleanColumn;
use bizley\migration\table\CharacterColumn;
use bizley\migration\table\ColumnFactory;
use bizley\migration\table\DateColumn;
use bizley\migration\table\DateTimeColumn;
use bizley\migration\table\DecimalColumn;
use bizley\migration\table\DoubleColumn;
use bizley\migration\table\FloatColumn;
use bizley\migration\table\IntegerColumn;
use bizley\migration\table\JsonColumn;
use bizley\migration\table\MoneyColumn;
use bizley\migration\table\PrimaryKeyColumn;
use bizley\migration\table\SmallIntegerColumn;
use bizley\migration\table\StringColumn;
use bizley\migration\table\TextColumn;
use bizley\migration\table\TimeColumn;
use bizley\migration\table\TimestampColumn;
use bizley\migration\table\TinyIntegerColumn;
use bizley\migration\table\UnsignedPrimaryKeyColumn;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use yii\db\Schema;

final class ColumnFactoryTest extends TestCase
{
    public function providerForTypes(): array
    {
        return [
            Schema::TYPE_PK => [Schema::TYPE_PK, PrimaryKeyColumn::class],
            Schema::TYPE_UPK => [Schema::TYPE_UPK, UnsignedPrimaryKeyColumn::class],
            Schema::TYPE_BIGPK => [Schema::TYPE_BIGPK, BigPrimaryKeyColumn ::class],
            Schema::TYPE_UBIGPK => [Schema::TYPE_UBIGPK, BigUnsignedPrimaryKeyColumn ::class],
            Schema::TYPE_CHAR => [Schema::TYPE_CHAR, CharacterColumn ::class],
            Schema::TYPE_STRING => [Schema::TYPE_STRING, StringColumn ::class],
            Schema::TYPE_TEXT => [Schema::TYPE_TEXT, TextColumn ::class],
            Schema::TYPE_TINYINT => [Schema::TYPE_TINYINT, TinyIntegerColumn ::class],
            Schema::TYPE_SMALLINT => [Schema::TYPE_SMALLINT, SmallIntegerColumn ::class],
            Schema::TYPE_INTEGER => [Schema::TYPE_INTEGER, IntegerColumn ::class],
            Schema::TYPE_BIGINT => [Schema::TYPE_BIGINT, BigIntegerColumn ::class],
            Schema::TYPE_BINARY => [Schema::TYPE_BINARY, BinaryColumn ::class],
            Schema::TYPE_FLOAT => [Schema::TYPE_FLOAT, FloatColumn ::class],
            Schema::TYPE_DOUBLE => [Schema::TYPE_DOUBLE, DoubleColumn ::class],
            Schema::TYPE_DATETIME => [Schema::TYPE_DATETIME, DateTimeColumn ::class],
            Schema::TYPE_TIMESTAMP => [Schema::TYPE_TIMESTAMP, TimestampColumn ::class],
            Schema::TYPE_TIME => [Schema::TYPE_TIME, TimeColumn ::class],
            Schema::TYPE_DATE => [Schema::TYPE_DATE, DateColumn ::class],
            Schema::TYPE_DECIMAL => [Schema::TYPE_DECIMAL, DecimalColumn ::class],
            Schema::TYPE_BOOLEAN => [Schema::TYPE_BOOLEAN, BooleanColumn ::class],
            Schema::TYPE_MONEY => [Schema::TYPE_MONEY, MoneyColumn ::class],
            Schema::TYPE_JSON => [Schema::TYPE_JSON, JsonColumn ::class],
        ];
    }

    /**
     * @test
     * @dataProvider providerForTypes
     * @param string $type
     * @param string $expected
     */
    public function shouldReturnProperColumn(string $type, string $expected): void
    {
        $column = ColumnFactory::build($type);
        $this->assertInstanceOf($expected, $column);
        $this->assertSame($type, $column->getType());
    }

    /**
     * @test
     */
    public function shouldThrowExceptionOnUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ColumnFactory::build('unknown');
    }
}
