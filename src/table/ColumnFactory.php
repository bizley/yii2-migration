<?php

declare(strict_types=1);

namespace bizley\migration\table;

use yii\base\InvalidConfigException;
use yii\db\Schema;

class ColumnFactory
{
    /**
     * Builds table column object based on the type.
     * @param string $type
     * @return Column
     * @throws InvalidConfigException
     */
    public static function build(string $type): Column
    {
        switch ($type) {
            case Schema::TYPE_PK:
                return new PrimaryKeyColumn();

            case Schema::TYPE_UPK:
                return new UnsignedPrimaryKeyColumn();

            case Schema::TYPE_BIGPK:
                return new BigPrimaryKeyColumn();

            case Schema::TYPE_UBIGPK:
                return new BigUnsignedPrimaryKeyColumn();

            case Schema::TYPE_CHAR:
                return new CharacterColumn();

            case Schema::TYPE_STRING:
                return new StringColumn();

            case Schema::TYPE_TEXT:
                return new TextColumn();

            case Schema::TYPE_TINYINT:
                return new TinyIntegerColumn();

            case Schema::TYPE_SMALLINT:
                return new SmallIntegerColumn();

            case Schema::TYPE_INTEGER:
                return new IntegerColumn();

            case Schema::TYPE_BIGINT:
                return new BigIntegerColumn();

            case Schema::TYPE_BINARY:
                return new BinaryColumn();

            case Schema::TYPE_FLOAT:
                return new FloatColumn();

            case Schema::TYPE_DOUBLE:
                return new DoubleColumn();

            case Schema::TYPE_DATETIME:
                return new DateTimeColumn();

            case Schema::TYPE_TIMESTAMP:
                return new TimestampColumn();

            case Schema::TYPE_TIME:
                return new TimeColumn();

            case Schema::TYPE_DATE:
                return new DateColumn();

            case Schema::TYPE_DECIMAL:
                return new DecimalColumn();

            case Schema::TYPE_BOOLEAN:
                return new BooleanColumn();

            case Schema::TYPE_MONEY:
                return new MoneyColumn();

            case Schema::TYPE_JSON:
                return new JsonColumn();

            default:
                throw new InvalidConfigException(
                    "Unsupported schema type '$type' for ColumnFactory."
                );
        }
    }
}
