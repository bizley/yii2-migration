<?php

declare(strict_types=1);

namespace bizley\migration\table;

use InvalidArgumentException;
use yii\db\Schema;

final class ColumnFactory
{
    /**
     * Builds table column object based on the type.
     * @param string $type
     * @return ColumnInterface
     */
    public static function build(string $type): ColumnInterface
    {
        switch ($type) {
            case Schema::TYPE_PK:
                $column = new PrimaryKeyColumn();
                break;

            case Schema::TYPE_UPK:
                $column = new UnsignedPrimaryKeyColumn();
                break;

            case Schema::TYPE_BIGPK:
                $column = new BigPrimaryKeyColumn();
                break;

            case Schema::TYPE_UBIGPK:
                $column = new BigUnsignedPrimaryKeyColumn();
                break;

            case Schema::TYPE_CHAR:
                $column = new CharacterColumn();
                break;

            case Schema::TYPE_STRING:
                $column = new StringColumn();
                break;

            case Schema::TYPE_TEXT:
                $column = new TextColumn();
                break;

            case Schema::TYPE_TINYINT:
                $column = new TinyIntegerColumn();
                break;

            case Schema::TYPE_SMALLINT:
                $column = new SmallIntegerColumn();
                break;

            case Schema::TYPE_INTEGER:
                $column = new IntegerColumn();
                break;

            case Schema::TYPE_BIGINT:
                $column = new BigIntegerColumn();
                break;

            case Schema::TYPE_BINARY:
                $column = new BinaryColumn();
                break;

            case Schema::TYPE_FLOAT:
                $column = new FloatColumn();
                break;

            case Schema::TYPE_DOUBLE:
                $column = new DoubleColumn();
                break;

            case Schema::TYPE_DATETIME:
                $column = new DateTimeColumn();
                break;

            case Schema::TYPE_TIMESTAMP:
                $column = new TimestampColumn();
                break;

            case Schema::TYPE_TIME:
                $column = new TimeColumn();
                break;

            case Schema::TYPE_DATE:
                $column = new DateColumn();
                break;

            case Schema::TYPE_DECIMAL:
                $column = new DecimalColumn();
                break;

            case Schema::TYPE_BOOLEAN:
                $column = new BooleanColumn();
                break;

            case Schema::TYPE_MONEY:
                $column = new MoneyColumn();
                break;

            case Schema::TYPE_JSON:
                $column = new JsonColumn();
                break;

            default:
                throw new InvalidArgumentException("Unsupported schema type '$type' for ColumnFactory.");
        }

        $column->setType($type);

        return $column;
    }
}
