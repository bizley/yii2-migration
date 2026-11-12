<?php

declare(strict_types=1);

namespace bizley\migration\table;

use InvalidArgumentException;
use yii\db\Schema;

final class ColumnFactory
{
    /**
     * Builds table column object based on the type.
     */
    public static function build(string $type): ColumnInterface
    {
        $column = match ($type) {
            Schema::TYPE_PK => new PrimaryKeyColumn(),
            Schema::TYPE_UPK => new UnsignedPrimaryKeyColumn(),
            Schema::TYPE_BIGPK => new BigPrimaryKeyColumn(),
            Schema::TYPE_UBIGPK => new BigUnsignedPrimaryKeyColumn(),
            Schema::TYPE_CHAR => new CharacterColumn(),
            Schema::TYPE_STRING => new StringColumn(),
            Schema::TYPE_TEXT => new TextColumn(),
            Schema::TYPE_TINYINT => new TinyIntegerColumn(),
            Schema::TYPE_SMALLINT => new SmallIntegerColumn(),
            Schema::TYPE_INTEGER => new IntegerColumn(),
            Schema::TYPE_BIGINT => new BigIntegerColumn(),
            Schema::TYPE_BINARY => new BinaryColumn(),
            Schema::TYPE_FLOAT => new FloatColumn(),
            Schema::TYPE_DOUBLE => new DoubleColumn(),
            Schema::TYPE_DATETIME => new DateTimeColumn(),
            Schema::TYPE_TIMESTAMP => new TimestampColumn(),
            Schema::TYPE_TIME => new TimeColumn(),
            Schema::TYPE_DATE => new DateColumn(),
            Schema::TYPE_DECIMAL => new DecimalColumn(),
            Schema::TYPE_BOOLEAN => new BooleanColumn(),
            Schema::TYPE_MONEY => new MoneyColumn(),
            Schema::TYPE_JSON => new JsonColumn(),
            default => throw new InvalidArgumentException("Unsupported schema type '$type' for ColumnFactory."),
        };

        $column->setType($type);

        return $column;
    }
}
